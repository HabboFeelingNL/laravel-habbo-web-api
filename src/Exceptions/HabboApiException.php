<?php

namespace HabboFeeling\HabboWebApi\Exceptions;

use RuntimeException;

/**
 * Base type for every failure raised by the client. Catch this to handle any
 * Habbo Web API problem (connection or HTTP) in one place.
 */
class HabboApiException extends RuntimeException {}
