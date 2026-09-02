<?php

namespace HabboFeeling\HabboWebApi\Data\Wired;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

class WiredBatchResultsData extends Data
{
    /**
     * @param  array<int, WiredBatchOperationResultData>  $results
     */
    public function __construct(
        #[DataCollectionOf(WiredBatchOperationResultData::class)]
        public array $results = [],
    ) {}
}
