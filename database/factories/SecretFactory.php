<?php

namespace Luchavez\SimpleSecrets\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Luchavez\SimpleSecrets\Models\Secret;

/**
 * Class Secret
 *
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 */
class SecretFactory extends Factory
{
    protected $model = Secret::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            //
        ];
    }
}
