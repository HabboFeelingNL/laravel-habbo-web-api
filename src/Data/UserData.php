<?php

namespace HabboFeeling\HabboWebApi\Data;

use Carbon\CarbonImmutable;
use HabboFeeling\HabboWebApi\Data\Casts\HabboDateTimeCast;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;

class UserData extends Data
{
    /**
     * @param  array<int, string>  $selectedBadges
     */
    public function __construct(
        public string $uniqueId,
        public string $name,
        public ?string $figureString = null,
        public ?string $motto = null,
        public bool $online = false,
        #[WithCast(HabboDateTimeCast::class)]
        public ?CarbonImmutable $lastAccessTime = null,
        #[WithCast(HabboDateTimeCast::class)]
        public ?CarbonImmutable $memberSince = null,
        public bool $profileVisible = false,
        public ?int $currentLevel = null,
        public ?int $currentLevelCompletePercent = null,
        public ?int $totalExperience = null,
        public ?int $starGemCount = null,
        public array $selectedBadges = [],
    ) {}
}
