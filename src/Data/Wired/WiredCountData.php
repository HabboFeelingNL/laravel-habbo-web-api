<?php

namespace HabboFeeling\HabboWebApi\Data\Wired;

use Spatie\LaravelData\Data;

class WiredCountData extends Data
{
    public function __construct(
        public int $count,
    ) {}
}
