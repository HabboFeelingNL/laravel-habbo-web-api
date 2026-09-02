<?php

namespace HabboFeeling\HabboWebApi\Exceptions;

use Illuminate\Http\Client\Response;

/**
 * The hotel rejected the request with 401 or 403 — usually a missing or wrong
 * wired read/write key.
 */
class HabboAuthException extends HabboRequestException
{
    public function __construct(Response $response)
    {
        parent::__construct($response, sprintf(
            'The Habbo Web API rejected the request (HTTP %d). Check the wired read/write key.',
            $response->status(),
        ));
    }
}
