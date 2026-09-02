<?php

namespace HabboFeeling\HabboWebApi\Data;

use Spatie\LaravelData\Data;

class AchievementLevelRequirementData extends Data
{
    public function __construct(
        public int $level,
        public int $requiredScore,
    ) {}
}
