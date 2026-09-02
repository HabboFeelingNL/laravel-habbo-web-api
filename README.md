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

The service provider and the `Habbo` facade are auto-discovered. Publish the
config if you want to change the default hotel:

```bash
php artisan vendor:publish --tag="habbo-web-api-config"
```

```env
HABBO_API_DOMAIN=www.habbo.com
HABBO_API_TIMEOUT=15
```

## Usage

Resolve `HabboApi` from the container or use the `Habbo` facade:

```php
use HabboFeeling\HabboWebApi\HabboApi;
use HabboFeeling\HabboWebApi\Facades\Habbo;

$user = app(HabboApi::class)->userByName('whatwasit');   // ?UserData
$user = Habbo::userByName('whatwasit');

$profile   = Habbo::userProfile($user->uniqueId);       // ?UserProfileData
$badges    = Habbo::userBadges($user->uniqueId);        // DataCollection<BadgeData>
$look      = Habbo::hotLooks();                          // DataCollection<HotLookData> (XML feed, parsed for you)
$market    = Habbo::marketplaceStats(['throne']);        // MarketplaceStatsData
```

### Return contract

| Kind of endpoint                    | Return                                              |
|-------------------------------------|----------------------------------------------------|
| Single resource (`user`, `group`, …)| `?XData` — `null` on HTTP 404, an error/maintenance envelope, or a `304 Not Modified` |
| List (`userFriends`, `achievements`)| `Spatie\LaravelData\DataCollection` — empty on error |
| `204 No Content` writes             | `bool`                                              |
| `marketplaceStats`                  | `MarketplaceStatsData`                              |
| `ping`                              | `bool`                                              |
| `get()`                             | raw decoded `array` (escape hatch for endpoints without a DTO method) |

### Talking to another hotel

`hotel()` returns a fresh instance bound to that hotel; the original is
untouched. It accepts any string or `Stringable`:

```php
Habbo::hotel('habbo.com.br')->user('hhbr-xxxx');
Habbo::hotel('https://www.habbo.es/')->group('g-...');
```

### Conditional requests

`user()` and `userByName()` take an ETag; pass a previously returned one to get
`null` back when nothing changed:

```php
$user = Habbo::user($id, $etag);
```

### Wired Variables (beta)

`$readKey` / `$writeKey` are the keys printed by the room's "Variable Global
Add-on: Web API" furni (see the note at the top).

```php
// Read
$score = Habbo::roomVariable($roomId, 'user', 'score', 'users', $entityId, $readKey);

// Create / replace / update / delete
Habbo::setRoomVariable($roomId, 'user', 'score', 'users', $entityId, 10, $writeKey);
Habbo::updateRoomVariable($roomId, 'user', 'score', 'users', $entityId, 11, $writeKey);
Habbo::deleteRoomVariable($roomId, 'user', 'score', 'users', $entityId, $writeKey);

// Global room variables
Habbo::globalRoomVariable($roomId, 'jackpot', $readKey);
Habbo::updateGlobalRoomVariable($roomId, 'jackpot', 500, $writeKey);

// Paged values, counts, bulk delete, batch, profiles
Habbo::listRoomVariableValues($roomId, 'user', 'score', 'users', $readKey, ['page' => 0, 'size' => 50]);
Habbo::countRoomVariableValues($roomId, 'user', 'score', 'users', $readKey);
Habbo::batchRoomVariable($roomId, 'user', 'score', $requests, $writeKey);
Habbo::userVariablesProfile($roomId, 'users', $entityId, $readKey);
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
$matchIds = Habbo::originsMatchIds($playerId, ['limit' => 20]);   // string[]
$match    = Habbo::originsMatch($matchIds[0]);                    // ?OriginsMatchData
$skill    = Habbo::originsSkill($playerId, 'FISHING');            // ?OriginsSkillData
$board    = Habbo::originsSkillLeaderboard('FISHING', page: 1);   // ?OriginsSkillLeaderboardData
$habboIds = Habbo::originsHabboIds($playerId);                    // string[]

// Fishing derby — takes an optional api_key query param
$derbyIds = Habbo::originsDerbyIds($playerId, ['api_key' => $key]);
$status   = Habbo::originsDerbyStatus(['api_key' => $key]);       // raw array (no schema in the spec)
$derby    = Habbo::originsDerby($derbyIds[0], ['api_key' => $key]); // raw array (no schema in the spec)
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
