<?php

namespace Luchavez\SimpleSecrets\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\ItemNotFoundException;

/**
 * Class SecretAlreadyExistsException
 *
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 */
class SecretAlreadyExistsException extends Exception
{
    /**
     * @param  string  $accessor_name
     *
     * @throws ItemNotFoundException
     */
    public function __construct(string $accessor_name)
    {
        $type = simpleSecrets()->getTypeByAccessorName($accessor_name);

        $max_history_count = $type->get('max_history_count');
        $display_name = $type->get('display_name');

        $message = "The new $display_name must not be the same as the $max_history_count previous ones.";
        $code = 409; // Conflict

        parent::__construct($message, $code);
    }

    /**
     * Render the exception as an HTTP response.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function render(Request $request): JsonResponse
    {
        return simpleResponse()
            ->message($this->message)
            ->code($this->code)
            ->generate();
    }
}
