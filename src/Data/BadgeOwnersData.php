<?php

namespace HabboFeeling\HabboWebApi\Data;

use Spatie\LaravelData\Data;

class BadgeOwnersData extends Data
{
    public function __construct(
        public int $ownerCount,
        public ?string $name = null,
        public ?string $description = null,
    ) {}
}
