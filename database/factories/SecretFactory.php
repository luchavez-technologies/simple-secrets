<?php

namespace Luchavez\SimpleSecrets\Database\Factories;

// Model
use Luchavez\SimpleSecrets\Models\Secret;
use Illuminate\Database\Eloquent\Factories\Factory;

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
