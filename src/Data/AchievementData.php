<?php

namespace HabboFeeling\HabboWebApi\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

class AchievementData extends Data
{
    /**
     * @param  array<int, AchievementLevelRequirementData>  $levelRequirements
     */
    public function __construct(
        public AchievementDetailData $achievement,
        #[DataCollectionOf(AchievementLevelRequirementData::class)]
        public array $levelRequirements = [],
    ) {}
}
