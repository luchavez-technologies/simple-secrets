<?php

namespace Luchavez\SimpleSecrets\Jobs;

use Luchavez\SimpleSecrets\Events\SecretExpiringEvent;
use Luchavez\SimpleSecrets\Models\Secret;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Class CheckSecretsExpirationJob
 *
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 */
class CheckSecretsExpirationJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public string $type)
    {
        //
    }

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId(): string
    {
        return $this->type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $type = simpleSecrets()->getTypeByKey($this->type);

        $broadcast_expiring_before = $type->get('broadcast_expiring_before');
        $expires_after = $type->get('expires_after');
        $key = $type->get('key');

        if (! is_null($expires_after) && ! is_null($broadcast_expiring_before)) {
            $broadcast_start = now()->add($broadcast_expiring_before);
            Secret::query()->scopes(['type' => $key])
                ->whereDate(Secret::getExpiresAtColumn(), '<=', $broadcast_start)
                ->lazyById()
                ->each(fn (Secret $secret) => event(new SecretExpiringEvent($secret)));
        }
    }
}
