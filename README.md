# Laravel Habbo Web API

[![Latest Version on Packagist](https://img.shields.io/packagist/v/habbofeelingnl/laravel-habbo-web-api.svg?style=flat-square)](https://packagist.org/packages/habbofeelingnl/laravel-habbo-web-api)
[![Tests](https://img.shields.io/github/actions/workflow/status/HabboFeelingNL/laravel-habbo-web-api/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/HabboFeelingNL/laravel-habbo-web-api/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/habbofeelingnl/laravel-habbo-web-api.svg?style=flat-square)](https://packagist.org/packages/habbofeelingnl/laravel-habbo-web-api)
[![PHP Version](https://img.shields.io/packagist/php-v/habbofeelingnl/laravel-habbo-web-api?style=flat-square)](https://packagist.org/packages/habbofeelingnl/laravel-habbo-web-api)
[![License](https://img.shields.io/packagist/l/habbofeelingnl/laravel-habbo-web-api.svg?style=flat-square)](LICENSE.md)

A Laravel client for the [public Habbo Web API](https://www.habbo.com/api/public/api-docs/).
Every response is hydrated into a typed DTO, and each hotel is reachable on its
own domain.

> The Wired Variables endpoints are part of Habbo's **beta** Web API — documented
> on the [sandbox](https://sandbox.habbo.com/api/public/api-docs/). Each room's
> read and write keys come from a **"Variable Global Add-on: Web API"** furni
> placed in that room, currently handed out to beta testers only.

## Requirements

- PHP 8.1+
- Laravel 10, 11 or 12

## Installation

```bash
composer require habbofeelingnl/laravel-habbo-web-api
```

The service provider and the `HabboAPI` facade are auto-discovered. Publish the
config if you want to change the default hotel:

```bash
php artisan vendor:publish --tag="habbo-web-api-config"
```

```env
HABBO_API_DOMAIN=www.habbo.com
HABBO_API_REQUEST_TIMEOUT=15
```

## Usage

Use the `HabboAPI` facade:

```php
use HabboFeeling\HabboWebApi\Facades\HabboAPI;

$user      = HabboAPI::userByName('whatwasit');           // ?UserData
$profile   = HabboAPI::userProfile($user->uniqueId);      // ?UserProfileData
$badges    = HabboAPI::userBadges($user->uniqueId);       // DataCollection<BadgeData>
$look      = HabboAPI::hotLooks();                         // DataCollection<HotLookData> (XML feed, parsed for you)
$market    = HabboAPI::marketplaceStats(['throne']);       // MarketplaceStatsData
```

…or resolve the client from the container:

```php
$habbo = app(\HabboFeeling\HabboWebApi\HabboApi::class);
$user  = $habbo->userByName('whatwasit');
```

> The service class is `HabboApi` and the facade is `HabboAPI` — PHP treats
> class names case-insensitively, so don't `use` both in the same file; import
> one and reference the other fully-qualified.

### Return contract

| Kind of endpoint                    | Return                                              |
|-------------------------------------|----------------------------------------------------|
| Single resource (`user`, `group`, …)| `?XData` — `null` only when the resource is missing (HTTP 404 / `not-found`) or a `304 Not Modified` |
| List (`userFriends`, `achievements`)| `Spatie\LaravelData\DataCollection` — empty when the resource is missing |
| `204 No Content` writes             | `bool` — `false` when the target did not exist |
| `marketplaceStats`                  | `MarketplaceStatsData`                              |
| `ping`                              | `bool` — never throws |
| `get()`                             | raw decoded `array` (escape hatch for endpoints without a DTO method) |

### Errors

A missing resource is not an error: 404 / `{"error":"not-found"}` and an
unchanged `304` are handled leniently as shown above. Anything else throws:

| Exception (`HabboFeeling\HabboWebApi\Exceptions\…`) | When |
|---|---|
| `HabboMaintenanceException` | `{"error":"maintenance"}` envelope |
| `HabboAuthException` | `401` / `403` — usually a bad or missing wired key |
| `HabboRateLimitException` | `429` — has `retryAfter(): ?int` |
| `HabboRequestException` | any other non-2xx — has `response` and `status()` |
| `HabboConnectionException` | the request never reached the hotel (DNS, refused, timeout) |

All extend `HabboApiException`, so `catch (HabboApiException $e)` covers every
failure; the four HTTP ones also extend `HabboRequestException` and expose the
raw `Illuminate\Http\Client\Response`.

```php
use HabboFeeling\HabboWebApi\Exceptions\HabboRateLimitException;
use HabboFeeling\HabboWebApi\Exceptions\HabboApiException;

try {
    $user = HabboAPI::userByName('whatwasit');
} catch (HabboRateLimitException $e) {
    sleep($e->retryAfter() ?? 1);
} catch (HabboApiException $e) {
    report($e);
}
```

### Talking to another hotel

`hotel()` returns a fresh instance bound to that hotel; the original is
untouched. It accepts any string or `Stringable`:

```php
HabboAPI::hotel('habbo.com.br')->user('hhbr-xxxx');
HabboAPI::hotel('https://www.habbo.es/')->group('g-...');
```

### Conditional requests

`user()` and `userByName()` take an ETag; pass a previously returned one to get
`null` back when nothing changed:

```php
$user = HabboAPI::user($id, $etag);
```

### Wired Variables (beta)

`$readKey` / `$writeKey` are the keys printed by the room's "Variable Global
Add-on: Web API" furni (see the note at the top).

```php
// Read
$score = HabboAPI::roomVariable($roomId, 'user', 'score', 'users', $entityId, $readKey);

// Create / replace / update / delete
HabboAPI::setRoomVariable($roomId, 'user', 'score', 'users', $entityId, 10, $writeKey);
HabboAPI::updateRoomVariable($roomId, 'user', 'score', 'users', $entityId, 11, $writeKey);
HabboAPI::deleteRoomVariable($roomId, 'user', 'score', 'users', $entityId, $writeKey);

// Global room variables
HabboAPI::globalRoomVariable($roomId, 'jackpot', $readKey);
HabboAPI::updateGlobalRoomVariable($roomId, 'jackpot', 500, $writeKey);

// Paged values, counts, bulk delete, batch, profiles
HabboAPI::listRoomVariableValues($roomId, 'user', 'score', 'users', $readKey, ['page' => 1, 'size' => 50]);
HabboAPI::countRoomVariableValues($roomId, 'user', 'score', 'users', $readKey);
HabboAPI::batchRoomVariable($roomId, 'user', 'score', $requests, $writeKey);
HabboAPI::userVariablesProfile($roomId, 'users', $entityId, $readKey);
```

**64-bit values.** Wired variable values are signed 64-bit integers. PHP's native
`int` is 64-bit on any 64-bit build and `json_decode()` keeps whole numbers up to
`PHP_INT_MAX` (the same ceiling), so values round-trip losslessly with no bigint
library — unlike JavaScript. 32-bit PHP builds (where large values overflow to
float) are not supported.

**Pagination.** `listRoomVariableValues()` takes `page` (1-based), `size`
(default 50, max 100), `order_by` (`value` / `creation_time` / `update_time`) and
`order_dir` (`asc` / `desc`).

**Rate limits.** Applied by the API per room, over a 60s window with a 10s burst
allowance: simple reads 300/min (burst 60), list endpoints and profile reads
120/min (burst 20), writes 120/min (burst 30), bulk deletes 10/min (burst 5),
batch requests 30/min plus a separate 500/min budget for batched writes. The
`…/count` endpoints are cached server-side for 20s–600s by count size. This
client neither throttles nor caches — pace your calls and cache profile/count
reads yourself if you expose them widely.

#### In-game vs API entity ids

The API uses "clean" identifiers. In-game wall-item ids are negative and Builders
Club ids are offset by a large constant.

```php
// If you already know the kind:
$entityId = (string) HabboApi::apiEntityId('wall-items', $inGameId);

// If you only have the raw in-game furni id, let the client work out the kind:
['kind' => $kind, 'id' => $id] = HabboApi::furniId($inGameId);
$score = HabboAPI::roomVariable($roomId, 'furni', 'score', $kind, (string) $id, $readKey);

// …and back again:
$inGameId = HabboApi::inGameFurniId($kind, $id);
```

#### Level-up add-on

If the room runs the level-up add-on, `LevelUpper` turns a raw XP total into a
level and progress. Pick the curve the add-on is configured with:

```php
use HabboFeeling\HabboWebApi\LevelUp\LevelUpper;

$curve = LevelUpper::linear(stepSize: 100, maxLevel: 10);
// or LevelUpper::interpolate([1 => 0, 5 => 1_000, 10 => 5_000]);
// or LevelUpper::exponential(initialXp: 100, strength: 50, maxLevel: 10);

$curve->currentLevel($xp);          // 1-based level
$curve->progress($xp);              // xp into the current level
$curve->progressPercentage($xp);    // 0-100
$curve->xpRemaining($xp);           // xp left to the next level
$curve->isMaxed($xp);               // bool
```

### Habbo Origins

Origins endpoints are prefixed `origins*` and key off a player's
`uniquePlayerId` rather than the regular Habbo unique id.

```php
$matchIds = HabboAPI::originsMatchIds($playerId, ['limit' => 20]);   // string[]
$match    = HabboAPI::originsMatch($matchIds[0]);                    // ?OriginsMatchData
$skill    = HabboAPI::originsSkill($playerId, 'FISHING');            // ?OriginsSkillData
$board    = HabboAPI::originsSkillLeaderboard('FISHING', page: 1);   // ?OriginsSkillLeaderboardData
$habboIds = HabboAPI::originsHabboIds($playerId);                    // string[]

// Fishing derby — takes an optional api_key query param
$derbyIds = HabboAPI::originsDerbyIds($playerId, ['api_key' => $key]);
$status   = HabboAPI::originsDerbyStatus(['api_key' => $key]);       // raw array (no schema in the spec)
$derby    = HabboAPI::originsDerby($derbyIds[0], ['api_key' => $key]); // raw array (no schema in the spec)
```

## Scope

Covers the full public API, including the Origins-tagged endpoints (matches,
fishing derby, skills, player-id mapping). The two fishing-derby *detail*
endpoints have no response schema in the API spec, so they return the raw
decoded array.

## Testing

```bash
composer test
```

## Laravel Boost

This package ships [Laravel Boost](https://laravel.com/docs/boost) resources, so if your
project uses Boost your AI agent gets guidance for this client automatically. Run
`php artisan boost:install` (or `php artisan boost:update`) after installing:

- a **core guideline** (`resources/boost/guidelines/core.blade.php`) — loaded upfront, the
  entry points, return contract and error model;
- the **`habbo-web-api-development` skill** (`resources/boost/skills/habbo-web-api-development/`) —
  loaded on demand, with the full method surface, Wired Variables, entity-id conversion,
  level-up curves and Origins.

## Other wrappers

Community wrappers for the same beta API exist for other stacks (e.g. WiredSpast's,
Ste's and Alynva's). This is the Laravel / PHP one.

## License

MIT.
