<?php

namespace HabboFeeling\HabboWebApi\Data\Origins;

use Spatie\LaravelData\Data;

class OriginsSkillLeaderboardEntryData extends Data
{
    public function __construct(
        public string $uniqueId,
        public int $level,
        public int $experience,
    ) {}
}
