<?php

namespace HabboFeeling\HabboWebApi\Exceptions;

use Illuminate\Http\Client\Response;

/**
 * The hotel answered with an `{"error": "maintenance"}` envelope.
 */
class HabboMaintenanceException extends HabboRequestException
{
    public function __construct(Response $response)
    {
        parent::__construct($response, 'The Habbo hotel is in maintenance.');
    }
}
