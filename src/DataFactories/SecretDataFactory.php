<?php

namespace Luchavez\SimpleSecrets\DataFactories;

use Illuminate\Database\Eloquent\Builder;
use Luchavez\SimpleSecrets\Models\Secret;
use Luchavez\StarterKit\Abstracts\BaseDataFactory;

/**
 * Class SecretDataFactory
 *
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 */
class SecretDataFactory extends BaseDataFactory
{
    /**
     * @var int
     */
    public int $type;

    /**
     * @var string
     */
    public string $value;

    /**
     * @var string|null
     */
    public ?string $description;

    /**
     * @var bool
     */
    public bool $hashed;

    /**
     * @var int|null
     */
    public ?int $usage_left = null;

    /**
     * @return Builder
     *
     * @example User::query()
     */
    public function getBuilder(): Builder
    {
        return Secret::query();
    }

    /***** FROM BASEDATAFACTORY *****/

    /**
     * To avoid duplicate entries on database, checking if the model already exists by its unique keys is a must.
     *
     * @return array
     */
    public function getUniqueKeys(): array
    {
        return [
            //
        ];
    }

    /**
     * This is to avoid merging incorrect fields to Eloquent model. This is used on `mergeFieldsToModel()`.
     *
     * @return array
     */
    public function getExceptKeys(): array
    {
        return [
            //
        ];
    }

    /***** FROM BASEJSONSERIALIZABLE *****/

    /**
     * @return array
     */
    public function getFieldAliases(): array
    {
        return [];
    }
}
