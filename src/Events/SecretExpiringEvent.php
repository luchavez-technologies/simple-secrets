<?php

namespace Luchavez\SimpleSecrets\Events;

use Luchavez\SimpleSecrets\Models\Secret;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

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
