<?php

namespace HabboFeeling\HabboWebApi\Data;

use Carbon\CarbonImmutable;
use HabboFeeling\HabboWebApi\Data\Casts\HabboDateTimeCast;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;

class GroupMemberData extends Data
{
    public function __construct(
        public string $uniqueId,
        public string $name,
        public bool $online = false,
        public ?string $gender = null,
        public ?string $motto = null,
        public ?string $habboFigure = null,
        #[WithCast(HabboDateTimeCast::class)]
        public ?CarbonImmutable $memberSince = null,
        public bool $isAdmin = false,
    ) {}
}
