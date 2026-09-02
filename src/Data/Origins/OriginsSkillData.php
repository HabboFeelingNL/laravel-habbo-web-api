<?php

namespace HabboFeeling\HabboWebApi\Data\Origins;

use Spatie\LaravelData\Data;

/**
 * A player's progress in one Origins skill (`/skills/{uniquePlayerId}`).
 */
class OriginsSkillData extends Data
{
    public function __construct(
        public int $level,
        public int $experience,
    ) {}
}
