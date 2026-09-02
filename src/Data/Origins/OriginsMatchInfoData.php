<?php

namespace HabboFeeling\HabboWebApi\Data\Origins;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

class OriginsMatchInfoData extends Data
{
    /**
     * @param  array<int, OriginsMatchParticipantData>  $participants
     * @param  array<int, OriginsMatchTeamData>  $teams
     */
    public function __construct(
        public ?int $gameCreation = null,
        public ?int $gameDuration = null,
        public ?int $gameEnd = null,
        public ?string $gameMode = null,
        public ?int $mapId = null,
        public bool $ranked = false,
        #[DataCollectionOf(OriginsMatchParticipantData::class)]
        public array $participants = [],
        #[DataCollectionOf(OriginsMatchTeamData::class)]
        public array $teams = [],
    ) {}
}
