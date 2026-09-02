<?php

namespace HabboFeeling\HabboWebApi\Exceptions;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;

/**
 * The hotel answered with a status the client cannot turn into a result
 * (anything other than 2xx, 304, or a plain 404 / not-found). The raw
 * {@see Response} is available for inspection.
 */
class HabboRequestException extends HabboApiException
{
    public function __construct(
        public readonly Response $response,
        ?string $message = null,
    ) {
        parent::__construct($message ?? sprintf(
            'Habbo Web API request failed with HTTP %d: %s',
            $response->status(),
            Str::limit(trim($response->body()), 200) ?: '(empty body)',
        ));
    }

    public function status(): int
    {
        return $this->response->status();
    }
}
