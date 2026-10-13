<?php

namespace HabboFeeling\HabboWebApi\Data\Casts;

use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Casts\Uncastable;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

/**
 * Wired variable values are signed 64-bit integers that the API encodes as
 * decimal strings to preserve precision. Older responses sent a bare JSON
 * number, so normalise both to the string form.
 */
class WiredValueCast implements Cast
{
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): string|Uncastable
    {
        if (is_int($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            return $value;
        }

        return Uncastable::create();
    }
}
