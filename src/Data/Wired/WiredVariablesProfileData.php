<?php

namespace HabboFeeling\HabboWebApi\Data\Wired;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * A variables profile for any target kind. The API returns exactly one of the
 * target properties populated (or none, for the global profile), alongside the
 * `variables` map of name => {@see WiredVariableData}.
 */
#[MapInputName(SnakeCaseMapper::class)]
class WiredVariablesProfileData extends Data
{
    /**
     * @param  array<string, WiredVariableData>  $variables
     */
    public function __construct(
        #[DataCollectionOf(WiredVariableData::class)]
        public array $variables = [],
        public ?WiredProfileTargetData $user = null,
        public ?WiredProfileTargetData $pet = null,
        public ?WiredProfileTargetData $bot = null,
        public ?WiredProfileTargetData $furni = null,
        public ?WiredProfileTargetData $furniBc = null,
        public ?WiredProfileTargetData $wallItem = null,
        public ?WiredProfileTargetData $wallItemBc = null,
    ) {}
}
