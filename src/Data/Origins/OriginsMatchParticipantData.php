<?php

namespace HabboFeeling\HabboWebApi\Data\Origins;

use Spatie\LaravelData\Data;

class OriginsMatchParticipantData extends Data
{
    public function __construct(
        public string $gamePlayerId,
        public ?int $gameScore = null,
        public ?int $playerPlacement = null,
        public ?int $teamId = null,
        public ?int $teamPlacement = null,
        public ?int $timesStunned = null,
        public ?int $powerUpPickups = null,
        public ?int $powerUpActivations = null,
        public ?int $tilesCleaned = null,
        public ?int $tilesColoured = null,
        public ?int $tilesStolen = null,
        public ?int $tilesLocked = null,
        public ?int $tilesColouredForOpponents = null,
    ) {}
}
