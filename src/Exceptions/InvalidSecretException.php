<?php

namespace Luchavez\SimpleSecrets\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Class InvalidSecretException
 *
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 */
class InvalidSecretException extends Exception
{
    /**
     * @param string $type
     */
    public function __construct(string $type)
    {
        $display = simpleSecrets()->getTypeByKey($type)->get('display_name');

        $display = Str::ucfirst($display);

        $message = "$display provided is incorrect.";

        parent::__construct($message, 401);
    }

    /**
     * Render the exception as an HTTP response.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function render(Request $request): JsonResponse
    {
        return customResponse()
            ->message($this->message)
            ->code($this->code)
            ->generate();
    }
}
