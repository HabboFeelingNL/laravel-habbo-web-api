<?php

namespace HabboFeeling\HabboWebApi\Data\Wired;

use Carbon\CarbonImmutable;
use HabboFeeling\HabboWebApi\Data\Casts\HabboDateTimeCast;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class WiredVariableData extends Data
{
    public function __construct(
        public int $value,
        #[WithCast(HabboDateTimeCast::class)]
        public ?CarbonImmutable $creationTime = null,
        #[WithCast(HabboDateTimeCast::class)]
        public ?CarbonImmutable $updateTime = null,
    ) {}
}
