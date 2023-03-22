<?php

namespace Luchavez\SimpleSecrets\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Class PurgeStaleSecretsJob
 *
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 */
class PurgeStaleSecretsJob implements ShouldQueue, ShouldBeUnique
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
    public function __construct(public User $user)
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
        return $this->user->id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        if (! simpleSecrets()->isHasSecretsTraitUsed()) {
            return;
        }

        // Get all secret types
        simpleSecrets()->getTypes()
            ->filter(fn (Collection $type) => $type->get('max_history_count') || $type->get('max_active_count'))
            ->each(fn (Collection $type) => $this->purgeSecretsByType($type));
    }

    protected function purgeSecretsByType(Collection $type)
    {
        $max_history_count = $type->get('max_history_count');
        $max_active_count = $type->get('max_active_count');
        $key = $type->get('key');

        $max = max($max_history_count, $max_active_count);

        // Main query
        $query = $this->user->secrets()->scopes(['type' => $key]);

        // Get active secrets
        $secrets = $query->clone()->limit($max)->pluck('id');
        $max -= $secrets->count();

        $query->clone()
            // Skip the active secrets
            ->whereNotIn('id', $secrets)
            // Skip the additional stale secrets to complete max
            ->when($max > 0, fn (Builder $q) => $q->skip($max)->limit(PHP_INT_MAX))
            // Include all expired, disabled, used, and trashed during forceDelete
            ->withExpired()
            ->withDisabled()
            ->withUsed()
            ->withTrashed()
            ->forceDelete();
    }
}
