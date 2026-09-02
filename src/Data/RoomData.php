<?php

namespace HabboFeeling\HabboWebApi\Data;

use Carbon\CarbonImmutable;
use HabboFeeling\HabboWebApi\Data\Casts\HabboDateTimeCast;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;

class RoomData extends Data
{
    /**
     * @param  array<int, string>  $tags
     * @param  array<int, string>  $categories
     */
    public function __construct(
        public int $id,
        public ?string $name = null,
        public ?string $description = null,
        #[WithCast(HabboDateTimeCast::class)]
        public ?CarbonImmutable $creationTime = null,
        public ?string $habboGroupId = null,
        public array $tags = [],
        public ?int $maximumVisitors = null,
        public bool $showOwnerName = false,
        public ?string $ownerName = null,
        public ?string $ownerUniqueId = null,
        public array $categories = [],
        public ?string $thumbnailUrl = null,
        public ?string $imageUrl = null,
        public ?int $rating = null,
        public ?string $uniqueId = null,
    ) {}
}
