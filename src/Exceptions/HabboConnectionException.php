<?php

namespace HabboFeeling\HabboWebApi\Exceptions;

use Illuminate\Http\Client\ConnectionException;

/**
 * The request never reached the hotel (DNS failure, refused connection,
 * timeout). Wraps Laravel's {@see ConnectionException}.
 */
class HabboConnectionException extends HabboApiException {}
