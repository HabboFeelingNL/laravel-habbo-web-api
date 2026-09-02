<?php

namespace HabboFeeling\HabboWebApi\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

class MarketplaceItemStatsData extends Data
{
    /**
     * @param  array<int, MarketplaceHistoryEntryData>  $history
     */
    public function __construct(
        public string $item,
        public ?string $statsDate = null,
        #[DataCollectionOf(MarketplaceHistoryEntryData::class)]
        public array $history = [],
        public ?int $soldItemCount = null,
        public ?int $creditSum = null,
        public ?int $averagePrice = null,
        public ?int $totalOpenOffers = null,
        public ?int $currentOpenOffers = null,
        public ?int $currentPrice = null,
        public ?int $historyLimitInDays = null,
    ) {}
}
