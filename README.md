# Laravel Habbo Web API

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
HabboAPI::listRoomVariableValues($roomId, 'user', 'score', 'users', $readKey, ['page' => 0, 'size' => 50]);
HabboAPI::countRoomVariableValues($roomId, 'user', 'score', 'users', $readKey);
HabboAPI::batchRoomVariable($roomId, 'user', 'score', $requests, $writeKey);
HabboAPI::userVariablesProfile($roomId, 'users', $entityId, $readKey);
```

#### In-game vs API entity ids

The API uses "clean" identifiers. In-game wall-item ids are negative and
Builders Club ids are offset by a large constant. Convert before passing an
`entityId`:

```php
$entityId = (string) HabboApi::apiEntityId('wall-items', $inGameId);
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

## License

MIT.
