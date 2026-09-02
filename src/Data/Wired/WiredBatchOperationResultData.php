<?php

namespace HabboFeeling\HabboWebApi\Data\Wired;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class WiredBatchOperationResultData extends Data
{
    public function __construct(
        public int $status,
        public ?string $opId = null,
        public ?WiredVariableData $body = null,
        public ?WiredBatchOperationErrorData $error = null,
    ) {}
}
