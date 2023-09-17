<?php

namespace Luchavez\SimpleSecrets\Console\Commands;

use Illuminate\Console\Command;
use Luchavez\StarterKit\Traits\UsesCommandCustomMessagesTrait;

/**
 * Class InstallSimpleSecretsCommand
 *
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 */
class InstallSimpleSecretsCommand extends Command
{
    use UsesCommandCustomMessagesTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'secrets:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute package setup.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->call('vendor:publish', [
            '--tag' => 'simple-secrets.config',
        ]);

        return self::SUCCESS;
    }
}
