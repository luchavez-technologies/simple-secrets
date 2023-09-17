<?php

namespace Luchavez\SimpleSecrets\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\ItemNotFoundException;

/**
 * Class NoActiveSecretException
 *
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 */
class NoActiveSecretException extends Exception
{
    /**
     * @param  string  ...$secret_type
     *
     * @throws ItemNotFoundException
     */
    public function __construct(string ...$secret_type)
    {
        $display_names = [];

        foreach ($secret_type as $item) {
            $display_names[] = simpleSecrets()->getTypeByKey($item)->get('display_name');
        }

        $display = collect($display_names)->join(', ', ', and ');

        $message = "User has no active $display.";

        parent::__construct($message, 401);
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
