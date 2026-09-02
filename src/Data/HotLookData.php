<?php

namespace HabboFeeling\HabboWebApi\Data;

use HabboFeeling\HabboWebApi\HabboApi;
use Spatie\LaravelData\Data;

/**
 * One entry of the `/lists/hotlooks` feed. That endpoint answers with XML
 * (`<habbo gender="m" figure="..." hash="..."/>`), which {@see HabboApi}
 * parses before hydrating this DTO.
 */
class HotLookData extends Data
{
    public function __construct(
        public string $figure,
        public ?string $gender = null,
        public ?string $hash = null,
    ) {}
}
