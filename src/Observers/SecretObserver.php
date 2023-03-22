<?php

namespace Luchavez\SimpleSecrets\Observers;

use Luchavez\SimpleSecrets\Exceptions\NoActiveSecretException;
use Luchavez\SimpleSecrets\Models\Secret;

/**
 * Class SecretObserver
 *
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 */
class SecretObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public bool $afterCommit = true;

    /**
     * Handle the Secret "created" event.
     *
     * @param  Secret  $secret
     * @return void
     */
    public function created(Secret $secret): void
    {
        $expires_at = simpleSecrets()->getTypeByKey($secret->type)->get('expires_after');
        $secret->expire($expires_at);
    }

    /**
     * Handle the Secret "updated" event.
     *
     * @param Secret $secret
     * @return void
     * @throws NoActiveSecretException
     */
    public function updated(Secret $secret): void
    {
        $model = $secret->owner;
        $type = $secret->type;

        $accessor_name = simpleSecrets()->getTypeByKey($type)->get('accessor_name');

        simpleSecrets()->getSecrets(user: $model, accessor_name: $accessor_name, rehydrate: true);
    }
}
