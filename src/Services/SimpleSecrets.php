<?php

namespace Luchavez\SimpleSecrets\Services;

use Closure;
use Exception;
use Luchavez\SimpleSecrets\DataFactories\SecretDataFactory;
use Luchavez\SimpleSecrets\Exceptions\NoActiveSecretException;
use Luchavez\SimpleSecrets\Jobs\PurgeStaleSecretsJob;
use Luchavez\SimpleSecrets\Models\Secret;
use Luchavez\SimpleSecrets\Traits\HasSecretsTrait;
use Luchavez\StarterKit\Services\StarterKit;
use Luchavez\StarterKit\Traits\HasTaggableCacheTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Validation\ValidationException;

/**
 * Class SimpleSecrets
 *
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 */
class SimpleSecrets
{
    use HasTaggableCacheTrait;

    /**
     * @var bool
     */
    protected bool $throw_no_active_secret_exception = false;

    /**
     * @var bool
     */
    protected bool $is_has_secrets_trait_used = false;

    /**
     * @var bool
     */
    protected bool $is_validation_enabled = true;

    /**
     * @var array
     */
    protected array $validation_rules = [];

    /**
     * @var array
     */
    protected array $masking_callbacks = [];

    /**
     * @param StarterKit $starter_kit
     */
    public function __construct(public StarterKit $starter_kit)
    {
        $this->is_has_secrets_trait_used = class_uses_trait($this->starter_kit->getUserModel(), HasSecretsTrait::class);
    }

    /***** THROW NO ACTIVE SECRET EXCEPTION RELATED *****/

    /**
     * @return bool
     */
    public function shouldThrowNoActiveSecretException(): bool
    {
        return $this->throw_no_active_secret_exception;
    }

    /**
     * @param  bool|null  $bool
     * @return void
     */
    public function toggleThrowNoActiveSecretException(bool $bool = null): void
    {
        $this->throw_no_active_secret_exception = is_null($bool) ? ! $this->throw_no_active_secret_exception : $bool;
    }

    /***** VALIDATION RELATED *****/

    /**
     * @return bool
     */
    public function isValidationEnabled(): bool
    {
        return $this->is_validation_enabled;
    }

    /**
     * @return void
     */
    public function enableValidation(): void
    {
        $this->is_validation_enabled = true;
    }

    /**
     * @return void
     */
    public function disableValidation(): void
    {
        $this->is_validation_enabled = false;
    }

    /**
     * @param string|null $type
     * @return array|null
     */
    public function getValidationRules(string $type = null): array|null
    {
        if ($type) {
            return Arr::get($this->validation_rules, $type);
        }

        return $this->validation_rules;
    }

    /**
     * @param string $type
     * @param array $validation_rules
     */
    public function setValidationRules(string $type, array $validation_rules): void
    {
        $this->validation_rules[$type] = $validation_rules;
    }

    /***** MASKING RELATED *****/

    /**
     * @param string $type
     * @return Closure|null
     */
    public function getMaskingMethod(string $type): Closure|null
    {
        return Arr::get($this->masking_callbacks, $type);
    }

    /**
     * @param string $type
     * @param Closure $closure
     */
    public function setMaskingMethod(string $type, Closure $closure): void
    {
        $this->masking_callbacks[$type] = $closure;
    }

    /***** CONFIG RELATED *****/

    /**
     * @return Collection
     */
    public function getTypes(): Collection
    {
        return collect(config('simple-secrets.types'))->map(
            fn ($value, $key) => collect($value)->merge([
                'key' => $key,
                'validation_rules' => $this->getValidationRules($key),
                'masking_method' => $this->getMaskingMethod($key),
            ])
        );
    }

    /**
     * @param  int  $code
     * @return Collection
     *
     * @throws ItemNotFoundException
     */
    public function getTypeByCode(int $code): Collection
    {
        return $this->getType('code', $code);
    }

    /**
     * @param  string  $key
     * @return Collection
     *
     * @throws ItemNotFoundException
     */
    public function getTypeByKey(string $key): Collection
    {
        return $this->getType('key', $key);
    }

    /**
     * @param string $accessor_name
     * @return Collection
     *
     */
    public function getTypeByAccessorName(string $accessor_name): Collection
    {
        return $this->getType('accessor_name', $accessor_name);
    }

    /**
     * @param  string  $by
     * @param  int|string  $search
     * @return Collection
     */
    private function getType(string $by, int|string $search): Collection
    {
        return $this->getTypes()
            ->where($by, $search)
            ->firstOrFail();
    }

    /**
     * @return string
     */
    public function getGlobalMiddleware(): string
    {
        $name = config('simple-secrets.global_middleware.name', 'secrets_active_or');
        $types = collect(config('simple-secrets.global_middleware.types', ['password']))->join(',');

        return $name.':'.$types;
    }

    /**
     * @return array
     */
    public function getGlobalMiddlewareExceptRoutes(): array
    {
        return config('simple-secrets.global_middleware.except_routes', []);
    }

    /***** CACHING RELATED *****/

    /**
     * @return string
     */
    public function getMainTag(): string
    {
        return 'secrets';
    }

    /**
     * @param User $user
     * @param string $accessor_name
     * @param bool $rehydrate
     * @param bool $value_only
     * @return \Illuminate\Database\Eloquent\Collection|Model|array|string|null
     * @throws NoActiveSecretException
     */
    public function getSecrets(User $user, string $accessor_name, bool $rehydrate = false, bool $value_only = false): \Illuminate\Database\Eloquent\Collection|Model|array|string|null
    {
        if (! $user->exists) {
            return null;
        }

        $type = $this->getTypeByAccessorName($accessor_name);

        $key = $type->get('key');
        $max_active_count = $type->get('max_active_count');

        $table = $user->getTable();
        $tags = [$table, $user->id];

        $closure = function (&$ttl = null) use ($user, $key, $max_active_count) {
            if ($this->is_has_secrets_trait_used) {
                $query = $user->secrets()->scopes(['type' => $key]);
                $expires_at_column = Secret::getExpiresAtColumn();

                if (! $query->count()) {
                    if ($this->shouldThrowNoActiveSecretException()) {
                        throw new NoActiveSecretException($key);
                    }

                    // Only fetch the value and expires_at
                    $query = $query
                        ->withExpired()
                        ->withDisabled()
                        ->withUsed()
                        ->withTrashed()
                    ;

                    // Set TTL to 0 so it won't be saved to the cache
                    $ttl = 0;
                }

                if ($max_active_count > 1) {
                    $result = $query->limit($max_active_count)->get();

                    // Set TTL from the oldest expires_at else 0
                    $ttl = $result->pluck($expires_at_column)->filter()->sort()->first() ?? 0;

                    return $result;
                }

                $result = $query->first();

                // Set TTL from model's expires_at else 0
                $ttl = $result?->$expires_at_column ?? 0;

                return $result;
            }

            $ttl = 0;

            return null;
        };

        $secrets = $this->getCache(tags: $tags, key: $key, closure: $closure, rehydrate: $rehydrate);

        if ($secrets && $value_only) {
            $secrets = $secrets instanceof \Illuminate\Database\Eloquent\Collection ?
                $secrets->pluck('value')->toArray() :
                $secrets->value;
        }

        return $secrets;
    }

    /**
     * @param  User $user
     * @param  string  $type
     * @return bool
     */
    public function forgetSecret(User $user, string $type): bool
    {
        if (! $user->exists) {
            return false;
        }

        $type = $this->getTypeByKey($type);
        $key = $type->get('key');

        $table = $user->getTable();
        $tags = [$table, $user->id];

        return $this->forgetCache($tags, $key);
    }

    /***** OTHER FUNCTIONS *****/

    /**
     * @param string $accessor_name
     * @param string|array $value
     * @return void
     * @throws ValidationException
     */
    public function validateSecret(string $accessor_name, string|array $value): void
    {
        // Run only if validation is enabled
        if (! $this->isValidationEnabled()) {
            return;
        }

        $type = $this->getTypeByAccessorName($accessor_name);

        $display_name = $type->get('display_name');
        $validation_rules = $type->get('validation_rules');

        // Get validation rules

        if (is_null($validation_rules)) {
            return;
        }

        $value = $this->getValueFromInput($value);

        if (is_array($value)) {
            if (! is_string($value[0])) {
                $value = Arr::pluck($value, 'value');
            }
        }

        // Always add required to avoid empty strings
        $validation_rules = Arr::prepend($validation_rules, 'required');

        // Prepare validation rules array
        $rules[is_array($value) ? $accessor_name.'.*' : $accessor_name] = $validation_rules;

        // Prepare validator
        $validator = Validator::make(
            data: [$accessor_name => $value],
            rules: $rules,
            customAttributes: [$accessor_name => $display_name]
        );

        // Execute validation
        $validator->validate();
    }

    /**
     * @param string $accessor_name
     * @param string|array $value
     * @param User|null $user
     * @return bool
     * @throws Exception
     */
    public function isSecretUnique(string $accessor_name, string|array $value, User $user = null): bool
    {
        // A user that does not exist yet should be skipped since their secret will be unique
        if ($user && ! $user->exists) {
            return true;
        }

        $type = $this->getTypeByAccessorName($accessor_name);

        $key = $type->get('key');
        $max_history_count = $type->get('max_history_count');
        $max_active_count = $type->get('max_active_count');
        $max = max($max_history_count, $max_active_count);

        // Related to "unique_for_all" implementation
        $hashed = $type->get('hashed');
        $unique_for_all = $type->get('unique_for_all');

        // Get user's previous secrets based on type
        if ($this->is_has_secrets_trait_used && $max > 0) {
            // All user secrets with specific type
            $query = Secret::query()->scopes(['type' => $key]);

            if ($unique_for_all && ! $hashed) {
                return ! $query->clone()
                    ->withExpired()
                    ->withDisabled()
                    ->withUsed()
                    ->withTrashed()
                    ->when(
                        is_array($value),
                        fn (Builder $q) => $q->whereIn('value', $value),
                        fn (Builder $q) => $q->where('value', $value)
                    )
                    ->exists();
            }

            if (is_null($user)) {
                return false;
            }

            // User-specific secrets with specific type
            $query = $query
                ->latest('id')
                ->select(['id', 'value', 'hashed'])
                ->owned($user);

            // Get active secrets
            $secrets = $query->clone()->limit($max)->get();
            $max -= $secrets->count();

            // Get remaining secrets from stale
            if ($max > 0) {
                $secrets = $secrets->merge(
                    $query->clone()
                        ->whereNotIn('id', $secrets->pluck('id'))
                        ->withExpired()
                        ->withDisabled()
                        ->withUsed()
                        ->withTrashed()
                        ->limit($max)
                        ->get()
                );
            }

            if ($this->checkSecret(input: $value, secret: $secrets)) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * @param User $user
     * @param string $accessor_name
     * @param string|array $value
     * @return string|array|null
     */
    public function addNewSecret(User $user, string $accessor_name, string|array $value): string|array|null
    {
        $type = $this->getTypeByAccessorName($accessor_name);

        $description = null;

        if (is_array($value) && Arr::isAssoc($value) && isset($value['description'])) {
            $description = $value['description'];
        }

        $value = $this->getValueFromInput($value);

        if (is_null($value)) {
            return null;
        }

        if (is_array($value)) {
            $result = [];

            foreach ($value as $v) {
                if (! is_null($res = $this->addNewSecret(user: $user, accessor_name: $accessor_name, value: $v))) {
                    $result[] = $res;
                }
            }

            return $result;
        }

        $code = $type->get('code');
        $key = $type->get('key');
        $max_usage_count = $type->get('max_usage_count');
        $hashed = $type->get('hashed', true);

        // Hash the value or not
        $hashed_or_not = $hashed ? Hash::make($value) : $value;

        // Create a data factory instance for Secret
        $factory = new SecretDataFactory();
        $factory->type = $code;
        $factory->value = $hashed_or_not;
        $factory->hashed = $hashed;
        $factory->usage_left = $max_usage_count;
        $factory->description = $description;

        // Make a Secret instance and set a temporary relationship with the model
        $relation_name = "secret_$key";
        $relation_value = $user->getRelationValue($relation_name);
        $relation_value[$value] = $factory->make();
        $user->setRelation($relation_name, $relation_value);

        return $hashed_or_not;
    }

    /**
     * @return bool
     */
    public function isHasSecretsTraitUsed(): bool
    {
        return $this->is_has_secrets_trait_used;
    }

    /**
     * @param string|array $input
     * @param \Illuminate\Database\Eloquent\Collection<Secret>|Secret $secret
     * @param bool $return_as_model
     * @return Secret|bool|null
     * @throws Exception
     */
    public function checkSecret(string|array $input, \Illuminate\Database\Eloquent\Collection|Secret $secret, bool $return_as_model = false): Secret|bool|null
    {
        $input = $this->getValueFromInput(input: $input);

        if (is_null($input)) {
            throw new Exception('Input must either be a string, or array of string, or an array with "value" as key, or an array of arrays with "value" as key.');
        }

        if (is_array($input)) {
            foreach ($input as $i) {
                if (is_array($i) && Arr::isAssoc($i) && isset($i['value'])) {
                    $i = $i['value'];
                }
                if ($result = $this->checkSecret(input: $i, secret: $secret, return_as_model: $return_as_model)) {
                    return $result;
                }
            }
        }

        if ($secret instanceof \Illuminate\Database\Eloquent\Collection) {
            $result = null;

            foreach ($secret as $s) {
                if ($result = $this->checkSecret(input: $input, secret: $s, return_as_model: $return_as_model)) {
                    return $result;
                }
            }

            return $result;
        }

        $hashed = $secret->hashed;
        $value = $secret->value;

        $result = $hashed ? Hash::check($input, $value) : $input == $value;

        if ($return_as_model) {
            return $result ? $secret : null;
        }

        return $result;
    }

    /**
     * Checks if the input is a string, then it should return the input.
     * If an array with "value" as key, return the value's value.
     * If an array consists of arrays with "value" as key, return the original array.
     *
     * @param array|string $input
     * @return array|string|null
     */
    protected function getValueFromInput(array|string $input): array|string|null
    {
        if (is_string($input)) {
            return $input;
        }

        if (is_array($input) && count($input)) {
            if (Arr::isAssoc($input) && isset($input['value'])) {
                return $input['value'];
            } elseif (($arr = $input[0]) && (is_string($arr) || (is_array($arr) && isset($arr['value'])))) {
                return $input;
            }
        }

        return null;
    }

    /**
     * @param User $user
     * @return PendingDispatch
     */
    public function purgeUserStaleSecrets(User $user): PendingDispatch
    {
        return PurgeStaleSecretsJob::dispatch(user: $user)
            ->delay(now()->add(config('simple-secrets.purge_stale_after')));
    }
}
