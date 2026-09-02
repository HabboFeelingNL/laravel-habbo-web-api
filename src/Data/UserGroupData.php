<?php

namespace HabboFeeling\HabboWebApi\Data;

use Spatie\LaravelData\Data;

class UserGroupData extends Data
{
    public function __construct(
        public string $id,
        public ?string $name = null,
        public ?string $description = null,
        public ?string $type = null,
        public ?string $roomId = null,
        public ?string $badgeCode = null,
        public ?string $primaryColour = null,
        public ?string $secondaryColour = null,
        public bool $online = false,
        public bool $isAdmin = false,
    ) {}
}
