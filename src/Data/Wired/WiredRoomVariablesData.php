<?php

namespace HabboFeeling\HabboWebApi\Data\Wired;

use Spatie\LaravelData\Data;

class WiredRoomVariablesData extends Data
{
    /**
     * @param  array<int, string>  $users
     * @param  array<int, string>  $furni
     * @param  array<int, string>  $global
     */
    public function __construct(
        public array $users = [],
        public array $furni = [],
        public array $global = [],
    ) {}
}
