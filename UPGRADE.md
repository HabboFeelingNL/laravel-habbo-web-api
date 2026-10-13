# Upgrade Guide

## Upgrading to 2.0.0 from 1.x

2.0.0 has one breaking change. It only affects **Wired Variables**. Users, groups,
rooms, marketplace, Origins and the LevelUp helpers are unchanged.

```bash
composer require habbofeelingnl/laravel-habbo-web-api:^2.0
```

### Wired variable values are now strings

The Habbo Web API docs now define every Wired variable value as a signed 64-bit
integer **encoded as a decimal string** (`"42"`, `"-9223372036854775808"`). This
applies to both responses and request bodies. The client now uses that format
instead of PHP `int`.

| Where | 1.x | 2.0 |
| --- | --- | --- |
| `WiredVariableData::$value` | `int` | `string` |
| `setRoomVariable(..., $value, ...)` | `int $value` | `string $value` |
| `updateRoomVariable(..., $value, ...)` | `int $value` | `string $value` |
| `updateGlobalRoomVariable(..., $value, ...)` | `int $value` | `string $value` |
| `patchUserVariablesProfile()` / `patchFurniVariablesProfile()` map | `array<string, int\|null>` | `array<string, string\|null>` |
| `patchGlobalVariablesProfile()` map | `array<string, int>` | `array<string, string>` |
| `batchRoomVariable()` request `body.value` | `int` | `string` |

Because `WiredVariableData` is shared, these also return strings:

- `WiredVariablesProfileData::$variables[...]->value`
- `WiredBatchOperationResultData::$body->value`

### Reading values

`$value` is now a string. Cast it before doing arithmetic or a strict comparison:

```php
// 1.x
$score = HabboAPI::roomVariable($roomId, 'user', 'score', 'users', $id, $readKey)->value;
if ($score === 100) { ... }

// 2.0
$score = (int) HabboAPI::roomVariable($roomId, 'user', 'score', 'users', $id, $readKey)->value;
if ($score === 100) { ... }
```

`(int)` is lossless on 64-bit PHP. For arbitrary-precision maths, or on 32-bit
builds, keep the string and use `bcmath` or `brick/math`:

```php
$next = bcadd($variable->value, '1');
```

Watch out for code that silently changes meaning instead of erroring:

- `$a->value === $b->value` still works, because both are strings.
- `$variable->value === 0` is now always `false`. Compare against `'0'` or cast first.
- `->sortBy('value')` and `max()` / `min()` compare strings, so `'10' < '9'`. Cast to
  `int` first, or sort with `bccomp()`.
- `json_encode($data)` now outputs `"value":"42"` rather than `"value":42`. Update any
  frontend or API consumer that expects a number.

### Writing values

Pass integer strings:

```php
// 1.x
HabboAPI::setRoomVariable($roomId, 'user', 'score', 'users', $id, 10, $writeKey);
HabboAPI::updateGlobalRoomVariable($roomId, 'jackpot', $jackpot, $writeKey);
HabboAPI::patchUserVariablesProfile($roomId, 'users', $id, ['score' => 10, 'lives' => null], $writeKey);

// 2.0
HabboAPI::setRoomVariable($roomId, 'user', 'score', 'users', $id, '10', $writeKey);
HabboAPI::updateGlobalRoomVariable($roomId, 'jackpot', (string) $jackpot, $writeKey);
HabboAPI::patchUserVariablesProfile($roomId, 'users', $id, ['score' => '10', 'lives' => null], $writeKey);
```

Batch requests take the same form:

```php
HabboAPI::batchRoomVariable($roomId, 'user', 'score', [
    ['op_id' => 'a', 'method' => 'PUT', 'path' => 'users/1', 'body' => ['value' => '5']],
    ['op_id' => 'b', 'method' => 'DELETE', 'path' => 'users/2'],
], $writeKey);
```

**Do I have to change my write calls?**

- **Without `declare(strict_types=1)`** in the calling file: no. PHP converts an `int`
  argument to a string, so `setRoomVariable(..., 10, ...)` keeps working. Profile-patch
  maps and batch `body.value` entries are converted too, whatever your strict-types
  setting. Updating to strings is still recommended.
- **With `declare(strict_types=1)`**: yes. Passing an `int` to `setRoomVariable()`,
  `updateRoomVariable()` or `updateGlobalRoomVariable()` throws a `TypeError`. Wrap
  the value in `(string)`.

### New: values are validated before sending

Every Wired write now checks each value against `^-?\d+$`. A value that fails
throws an `InvalidArgumentException` **before any HTTP request is made**. In 1.x
these values were sent to the API, which returned a 400 (`HabboRequestException`).

These are now rejected locally: `'1.5'`, `'1e3'`, `'abc'`, `''`, `' 10'`, and floats
such as `1.5`, which PHP turns into `'1.5'`.

If you catch `HabboRequestException` to handle bad user input, also catch
`InvalidArgumentException`, or validate the input yourself first:

```php
try {
    HabboAPI::setRoomVariable($roomId, 'user', 'score', 'users', $id, $input, $writeKey);
} catch (InvalidArgumentException $e) {
    // not an integer string
}
```

### Backwards-compatible responses

If the API ever returns a plain JSON number (`{"value": 42}`), as it did before the
docs change, the client converts it to `'42'`. You don't need to handle both forms.

### Checklist

- [ ] Search your code for `->value` on `WiredVariableData`, profile variables and
      batch result bodies. Cast it, or switch to string or bcmath operations.
- [ ] Look for `=== <int>` comparisons, sorting and `max()`/`min()` on wired values.
- [ ] Update `json_encode` or API consumers that expect a numeric `value`.
- [ ] In files with `declare(strict_types=1)`, wrap write values in `(string)`.
- [ ] Handle `InvalidArgumentException` wherever user input goes into a Wired write.
