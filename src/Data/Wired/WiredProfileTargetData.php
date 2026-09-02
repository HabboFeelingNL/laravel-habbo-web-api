<?php

namespace HabboFeeling\HabboWebApi\Data\Wired;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class WiredProfileTargetData extends Data
{
    public function __construct(
        public int $id,
        public ?string $name = null,
        public ?string $uniqueId = null,
    ) {}
}
