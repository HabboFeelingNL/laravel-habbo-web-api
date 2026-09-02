<?php

namespace HabboFeeling\HabboWebApi\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

class MarketplaceStatsData extends Data
{
    /**
     * @param  array<int, MarketplaceItemStatsData>  $roomItemData
     * @param  array<int, MarketplaceItemStatsData>  $wallItemData
     */
    public function __construct(
        public ?string $status = null,
        #[DataCollectionOf(MarketplaceItemStatsData::class)]
        public array $roomItemData = [],
        #[DataCollectionOf(MarketplaceItemStatsData::class)]
        public array $wallItemData = [],
    ) {}
}
