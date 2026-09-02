<?php

namespace HabboFeeling\HabboWebApi\Data\Origins;

use Spatie\LaravelData\Data;

class OriginsMatchMetadataData extends Data
{
    /**
     * @param  array<int, string>  $participantPlayerIds
     */
    public function __construct(
        public string $matchId,
        public array $participantPlayerIds = [],
    ) {}
}
