<?php

namespace HabboFeeling\HabboWebApi\Data;

use Spatie\LaravelData\Data;

/**
 * Habbo returns every field here as a string.
 */
class MarketplaceHistoryEntryData extends Data
{
    public function __construct(
        public ?string $dayOffset = null,
        public ?string $averagePrice = null,
        public ?string $totalSoldItems = null,
        public ?string $totalCreditSum = null,
        public ?string $totalOpenOffers = null,
    ) {}
}
