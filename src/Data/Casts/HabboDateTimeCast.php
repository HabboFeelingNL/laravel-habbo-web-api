<?php

namespace HabboFeeling\HabboWebApi\Data\Casts;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Casts\Uncastable;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

/**
 * The Habbo API mixes date formats across endpoints: bare `Y-m-d`, ISO 8601
 * with milliseconds and a `+0000` offset, and ISO 8601 with a `+00:00` offset.
 * Carbon parses all of them, so lean on it rather than juggling format lists.
 */
class HabboDateTimeCast implements Cast
{
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): CarbonImmutable|Uncastable|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return CarbonImmutable::instance($value);
        }

        if (! is_string($value)) {
            return Uncastable::create();
        }

        return CarbonImmutable::parse($value);
    }
}
