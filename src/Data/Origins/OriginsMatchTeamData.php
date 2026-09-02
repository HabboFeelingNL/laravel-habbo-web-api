<?php

namespace HabboFeeling\HabboWebApi\Data\Origins;

use Spatie\LaravelData\Data;

class OriginsMatchTeamData extends Data
{
    public function __construct(
        public int $teamId,
        public bool $win = false,
        public ?int $teamScore = null,
        public ?int $teamPlacement = null,
    ) {}
}
