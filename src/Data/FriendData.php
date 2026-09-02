<?php

namespace HabboFeeling\HabboWebApi\Data;

use Spatie\LaravelData\Data;

class FriendData extends Data
{
    public function __construct(
        public string $uniqueId,
        public string $name,
        public ?string $motto = null,
        public bool $online = false,
        public ?string $figureString = null,
    ) {}
}
