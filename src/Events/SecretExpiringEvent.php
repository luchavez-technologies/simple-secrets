<?php

namespace Luchavez\SimpleSecrets\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Luchavez\SimpleSecrets\Models\Secret;

/**
 * Class SecretExpiringEvent
 *
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 */
class SecretExpiringEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public Secret $secret)
    {
        //
    }
}
