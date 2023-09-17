<?php

namespace Luchavez\SimpleSecrets\Console\Commands;

use Illuminate\Console\Command;
use Luchavez\SimpleSecrets\Jobs\CheckSecretsExpirationJob;
use Luchavez\StarterKit\Traits\UsesCommandCustomMessagesTrait;

/**
 * Class CheckSecretsExpirationCommand
 *
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 */
class CheckSecretsExpirationCommand extends Command
{
    use UsesCommandCustomMessagesTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'secrets:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the expiration of user secrets.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        // Get all secret types that has an expiration and with broadcast schedule
        simpleSecrets()->getTypes()
            ->whereNotNull('expires_after')
            ->whereNotNull('broadcast_expiring_before')
            ->pluck('key')
            ->each(fn ($key) => CheckSecretsExpirationJob::dispatch(type: $key));

        $this->ongoing('Checking expiration of user secrets.');

        return self::SUCCESS;
    }
}
