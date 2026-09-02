<?php

namespace HabboFeeling\HabboWebApi\Data;

use Carbon\CarbonImmutable;
use HabboFeeling\HabboWebApi\Data\Casts\HabboDateTimeCast;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;

class AchievementDetailData extends Data
{
    public function __construct(
        public int $id,
        public ?string $name = null,
        #[WithCast(HabboDateTimeCast::class)]
        public ?CarbonImmutable $creationTime = null,
        public ?string $state = null,
        public ?string $category = null,
    ) {}
}
