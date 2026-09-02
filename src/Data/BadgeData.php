<?php

namespace HabboFeeling\HabboWebApi\Data;

use Spatie\LaravelData\Data;

class BadgeData extends Data
{
    public function __construct(
        public string $code,
        public ?string $name = null,
        public ?string $description = null,
    ) {}
}
