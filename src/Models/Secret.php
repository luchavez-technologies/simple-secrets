<?php

namespace Luchavez\SimpleSecrets\Models;

use Luchavez\SimpleSecrets\Traits\HasSecretFactoryTrait;
use Luchavez\StarterKit\Traits\ModelDisablingTrait;
use Luchavez\StarterKit\Traits\ModelExpiringTrait;
use Luchavez\StarterKit\Traits\ModelOwnedTrait;
use Luchavez\StarterKit\Traits\ModelUsedTrait;
use Luchavez\StarterKit\Traits\UsesUUIDTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Secret
 *
 * @method static static|Builder|\Illuminate\Database\Query\Builder type(string $type)
 * @method static static|Builder|\Illuminate\Database\Query\Builder passwords()
 *
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 */
class Secret extends Model
{
    use SoftDeletes;
    use UsesUUIDTrait;
    use ModelUsedTrait;
    use ModelOwnedTrait;
    use ModelExpiringTrait;
    use ModelDisablingTrait;
    use HasSecretFactoryTrait;

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        // write here...
        'deleted_at',
    ];

    /**
     * @var string[]
     */
    protected $hidden = [
        'value'
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'hashed' => 'boolean',
    ];

    protected $appends = [
        'display'
    ];

    /***** RELATIONSHIPS *****/

    //

    /***** SCOPES *****/

    /**
     * @param  Builder  $builder
     * @return Builder|mixed
     */
    public function scopePasswords(Builder $builder): mixed
    {
        return $builder->scopes(['type' => 'password']);
    }

    /**
     * @param  Builder  $builder
     * @param  string  $type
     * @return Builder
     */
    public function scopeType(Builder $builder, string $type): Builder
    {
        $type = simpleSecrets()->getTypeByKey($type);

        return $builder->where('type', $type->get('code'));
    }

    /**
     * @param  Builder  $builder
     * @param  bool  $bool
     * @return Builder
     */
    public function scopeHidden(Builder $builder, bool $bool = true): Builder
    {
        return $builder->whereIn('type', simpleSecrets()->getTypes()->where('hidden', $bool)->pluck('code'));
    }

    /***** ACCESSORS & MUTATORS *****/

    /**
     * @param  int  $code
     * @return mixed
     */
    public function getTypeAttribute(int $code): mixed
    {
        return simpleSecrets()->getTypeByCode($code)->get('key');
    }

    /**
     * @return mixed
     */
    public function getDisplayAttribute(): mixed
    {
        $type = simpleSecrets()->getTypeByKey($this->type);
        $closure = $type->get('masking_method');
        $hidden = $type->get('hidden', true);
        $actual = $this->value;

        $display = [];

        if ($closure) {
            $display['masked'] = $closure($actual);
        }

        if (! $hidden) {
            $display['actual'] = $actual;
        }

        return $display;
    }

    /**
     * @param  string|int  $key
     * @return void
     */
    public function setTypeAttribute(string|int $key): void
    {
        $type = is_int($key) ? simpleSecrets()->getTypeByCode($key) : simpleSecrets()->getTypeByKey($key);

        $this->attributes['type'] = $type->get('code');
    }
}
