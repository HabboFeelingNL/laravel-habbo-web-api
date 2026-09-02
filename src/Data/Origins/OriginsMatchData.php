<?php

namespace HabboFeeling\HabboWebApi\Data\Origins;

use Spatie\LaravelData\Data;

/**
 * A single Origins minigame match (`/matches/v1/{uniqueMatchId}`).
 */
class OriginsMatchData extends Data
{
    public function __construct(
        public OriginsMatchMetadataData $metadata,
        public OriginsMatchInfoData $info,
    ) {}
}
