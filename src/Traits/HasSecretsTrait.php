<?php

namespace Luchavez\SimpleSecrets\Traits;

use Luchavez\SimpleSecrets\Casts\AsSecretCast;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;

/**
 * Trait HasSecretsTrait
 *
 * @method static HasMany secrets() Get related secrets
 *
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 */
trait HasSecretsTrait
{
    /**
     * @return void
     */
    public static function bootHasSecretsTrait(): void
    {
        // Unset all dynamically set secrets (e.g., $user->password = 'password')
        static::saving(function (User $model) {
            simpleSecrets()->getTypes()->each(function ($item, $key) use ($model) {
                if ($accessor_name = $item->get('accessor_name')) {
                    unset($model->$accessor_name);
                }
            });
        });

        // Persist all temporarily set relationships into actual valid relationship
        static::saved(function (User $model) {
            simpleSecrets()->getTypes()->keys()->each(function ($type) use ($model) {
                // Prepare relationship name
                $relation_name = "secret_$type";

                // Get copy of relationship value from Eloquent model
                if ($relation = $model->getRelationValue($relation_name)) {
                    // Disregard the keys
                    $relation = array_values($relation);

                    // Unset dummy relationship from Eloquent model
                    $model->unsetRelation($relation_name);

                    // Save dummy as actual secrets
                    $model->secrets()->saveMany($relation);

                    // Clear cache to remove old secret
                    simpleSecrets()->forgetSecret(user: $model, type: $type);
                }
            });
        });
    }

    /**
     * Initialize the expiring trait for an instance.
     *
     * @return void
     */
    public function initializeHasSecretsTrait(): void
    {
        simpleSecrets()->getTypes()->each(function (Collection $type) {
            $accessor_name = $type->get('accessor_name');
            $hidden = $type->get('hidden', true);
            $append = $type->get('append', false);

            if (! isset($this->casts[$accessor_name])) {
                $this->casts[$accessor_name] = AsSecretCast::class;
            }

            if (! isset($this->hidden[$accessor_name]) && $hidden) {
                $this->hidden[] = $accessor_name;
            }

            if (! isset($this->appends[$accessor_name]) && $append) {
                $this->appends[] = $accessor_name;
            }
        });
    }
}
