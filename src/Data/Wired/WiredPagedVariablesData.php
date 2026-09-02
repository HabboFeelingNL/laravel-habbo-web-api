<?php

namespace HabboFeeling\HabboWebApi\Data\Wired;

use Spatie\LaravelData\Data;

class WiredPagedVariablesData extends Data
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function __construct(
        public array $items = [],
        public ?int $page = null,
        public ?int $size = null,
    ) {}
}
