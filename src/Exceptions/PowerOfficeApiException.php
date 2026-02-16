<?php

namespace Tor2r\PowerOfficeApi\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;
use Throwable;

class PowerOfficeApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly Response $response,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
