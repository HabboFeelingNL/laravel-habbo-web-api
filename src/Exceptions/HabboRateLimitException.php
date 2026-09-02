<?php

namespace HabboFeeling\HabboWebApi\Exceptions;

use Illuminate\Http\Client\Response;

/**
 * The hotel answered 429 Too Many Requests. See the Wired rate guidance in the
 * README: poll a single endpoint at most once every 0.5s.
 */
class HabboRateLimitException extends HabboRequestException
{
    public function __construct(Response $response)
    {
        parent::__construct($response, 'The Habbo Web API rate limit was hit (HTTP 429).');
    }

    /**
     * Seconds to wait before retrying, from the `Retry-After` header when present.
     */
    public function retryAfter(): ?int
    {
        $value = $this->response->header('Retry-After');

        return is_numeric($value) ? (int) $value : null;
    }
}
