<?php

namespace HabboFeeling\HabboWebApi\Data\Wired;

use Spatie\LaravelData\Data;

class WiredBatchOperationErrorData extends Data
{
    public function __construct(
        public string $code,
        public string $message,
    ) {}
}
