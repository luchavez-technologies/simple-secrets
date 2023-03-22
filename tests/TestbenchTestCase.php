<?php

namespace Luchavez\SimpleSecrets\Tests;

use Orchestra\Testbench\TestCase as Testbench;

/**
 * Class TestbenchTestCase
 *
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 */
abstract class TestbenchTestCase extends Testbench
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @param $app
     * @return string[]
     */
    protected function getPackageProviders($app): array
    {
        return ['Luchavez\SimpleSecrets\SimpleSecretsServiceProvider'];
    }
}
