<?php

namespace HabboFeeling\HabboWebApi\Data\Origins;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

/**
 * A page of an Origins skill leaderboard (`/skills/leaderboard`).
 */
class OriginsSkillLeaderboardData extends Data
{
    /**
     * @param  array<int, OriginsSkillLeaderboardEntryData>  $entries
     */
    public function __construct(
        #[DataCollectionOf(OriginsSkillLeaderboardEntryData::class)]
        public array $entries = [],
        public ?int $totalPages = null,
        public ?int $currentPage = null,
        public ?int $pageSize = null,
    ) {}
}
