---
name: habbo-web-api-development
description: Work with the habbofeelingnl/laravel-habbo-web-api client — user/group/room/marketplace lookups, per-hotel routing, error handling, Wired Variables (beta), in-game vs API entity ids, level-up curves, and Habbo Origins.
---

# Habbo Web API Development

## When to use this skill

Use this skill when calling the public Habbo Web API through
`habbofeelingnl/laravel-habbo-web-api`: fetching users, profiles, badges, groups, rooms,
marketplace stats or hot looks; talking to a non-default hotel; handling API failures;
reading or writing Wired Variables; converting in-game furni ids; computing level-up
progress; or querying Habbo Origins (matches, skills, fishing derby).

## Entry points

- Facade: `HabboFeeling\HabboWebApi\Facades\HabboAPI`
- Container: `app(\HabboFeeling\HabboWebApi\HabboApi::class)`

The service class is `HabboApi`, the facade is `HabboAPI`. PHP treats class names
case-insensitively, so do **not** `use` both in the same file — import one, reference the
other fully-qualified.

Config (`config/habbo-api.php`, publish tag `habbo-web-api-config`):
`HABBO_API_DOMAIN` (default `www.habbo.com`), `HABBO_API_REQUEST_TIMEOUT` (default 15).

## Return contract

| Endpoint kind | Return |
| --- | --- |
| Single resource (`user`, `group`, `room`, `userProfile`, …) | `?XData` — `null` only on 404 / `not-found` or `304 Not Modified` |
| List (`achievements`, `userFriends`, `groupMembers`, `hotLooks`, …) | `Spatie\LaravelData\DataCollection` — empty when the resource is missing |
| `204 No Content` writes (`deleteRoomVariable`, …) | `bool` — `false` when the target did not exist |
| `marketplaceStats` | `MarketplaceStatsData` |
| `ping` | `bool` — never throws |
| `get(string $path, array $query = [])` | raw decoded `array` — escape hatch for endpoints without a DTO method |

A missing resource is **not** an error. `304` responses also return `null` / empty.

## Error handling

Every failure other than a missing resource throws an exception under
`HabboFeeling\HabboWebApi\Exceptions\`. All extend `HabboApiException`, so
`catch (HabboApiException $e)` covers everything.

| Exception | When | Extra |
| --- | --- | --- |
| `HabboMaintenanceException` | `{"error":"maintenance"}` envelope | |
| `HabboAuthException` | `401` / `403` — usually a bad/missing wired key | |
| `HabboRateLimitException` | `429` | `retryAfter(): ?int` |
| `HabboRequestException` | any other non-2xx | `response`, `status()` |
| `HabboConnectionException` | request never reached the hotel (DNS, refused, timeout) | |

The four HTTP exceptions also extend `HabboRequestException` and expose the raw
`Illuminate\Http\Client\Response`.

```php
use HabboFeeling\HabboWebApi\Facades\HabboAPI;
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

## Common lookups

```php
$user     = HabboAPI::userByName('whatwasit');          // ?UserData
$user     = HabboAPI::user($id);                        // ?UserData (by unique id)
$profile  = HabboAPI::userProfile($user->uniqueId);     // ?UserProfileData
$friends  = HabboAPI::userFriends($user->uniqueId);     // DataCollection<FriendData>
$groups   = HabboAPI::userGroups($user->uniqueId);      // DataCollection<UserGroupData>
$rooms    = HabboAPI::userRooms($user->uniqueId);       // DataCollection<RoomData>
$badges   = HabboAPI::userBadges($user->uniqueId);      // DataCollection<BadgeData>
$achv     = HabboAPI::userAchievements($user->uniqueId);// DataCollection<AchievementData>

$group    = HabboAPI::group('g-...');                   // ?GroupData
$members  = HabboAPI::groupMembers('g-...');            // DataCollection<GroupMemberData>
$room     = HabboAPI::room(12345);                      // ?RoomData
$owners   = HabboAPI::badgeOwners('ACH_...');           // ?BadgeOwnersData

$market   = HabboAPI::marketplaceStats(['throne'], ['wallpaper_basic']); // MarketplaceStatsData
$looks    = HabboAPI::hotLooks();                       // DataCollection<HotLookData> (XML feed, parsed)
$all      = HabboAPI::achievements();                   // DataCollection<AchievementData>
```

### Conditional requests

`user()` and `userByName()` take an optional ETag; pass a previously returned one to get
`null` when nothing changed:

```php
$user = HabboAPI::user($id, $etag);
```

### Talking to another hotel

`hotel()` returns a fresh instance bound to that hotel; the original is untouched. Accepts
any string or `Stringable` (bare domain or full URL):

```php
HabboAPI::hotel('habbo.com.br')->user('hhbr-xxxx');
HabboAPI::hotel('https://www.habbo.es/')->group('g-...');
```

## Wired Variables (beta)

Beta Web API. `$readKey` / `$writeKey` come from the room's "Variable Global Add-on: Web
API" furni. This client does **not** throttle or cache — pace calls and cache
profile/count reads yourself.

```php
// Per-entity variable: (roomId, scope, variableName, targetKind, entityId, key)
$var = HabboAPI::roomVariable($roomId, 'user', 'score', 'users', $entityId, $readKey);           // ?WiredVariableData
HabboAPI::setRoomVariable($roomId, 'user', 'score', 'users', $entityId, 10, $writeKey);           // ?WiredVariableData
HabboAPI::updateRoomVariable($roomId, 'user', 'score', 'users', $entityId, 11, $writeKey);        // ?WiredVariableData
HabboAPI::deleteRoomVariable($roomId, 'user', 'score', 'users', $entityId, $writeKey);            // bool

// Global room variables
HabboAPI::globalRoomVariable($roomId, 'jackpot', $readKey);                                       // ?WiredVariableData
HabboAPI::updateGlobalRoomVariable($roomId, 'jackpot', 500, $writeKey);                           // ?WiredVariableData

// Paged values / counts / bulk delete / batch
HabboAPI::listRoomVariableValues($roomId, 'user', 'score', 'users', $readKey, ['page' => 1, 'size' => 50]); // ?WiredPagedVariablesData
HabboAPI::countRoomVariableValues($roomId, 'user', 'score', 'users', $readKey);                   // ?WiredCountData
HabboAPI::bulkDeleteRoomVariables($roomId, ['score', 'combo'], $writeKey);                        // bool
HabboAPI::batchRoomVariable($roomId, 'user', 'score', $requests, $writeKey);                      // ?WiredBatchResultsData
HabboAPI::roomVariables($roomId, $readKey);                                                       // ?WiredRoomVariablesData

// Variable profiles (all of an entity's variables at once)
HabboAPI::userVariablesProfile($roomId, 'users', $entityId, $readKey);                            // ?WiredVariablesProfileData
HabboAPI::userVariablesProfileByName($roomId, $readKey, name: 'whatwasit');
HabboAPI::patchUserVariablesProfile($roomId, 'users', $entityId, ['score' => 10], $writeKey);
HabboAPI::deleteUserVariablesProfile($roomId, 'users', $entityId, $writeKey);                     // bool
HabboAPI::furniVariablesProfile($roomId, $kind, $entityId, $readKey);
HabboAPI::patchFurniVariablesProfile($roomId, $kind, $entityId, ['hits' => 1], $writeKey);
HabboAPI::globalVariablesProfile($roomId, $readKey);
HabboAPI::patchGlobalVariablesProfile($roomId, ['jackpot' => 500], $writeKey);
```

- **Pagination** (`listRoomVariableValues`): `page` (1-based), `size` (default 50, max
  100), `order_by` (`value` / `creation_time` / `update_time`), `order_dir`
  (`asc` / `desc`).
- **64-bit values**: signed 64-bit ints round-trip losslessly on 64-bit PHP builds. No
  bigint library needed. 32-bit PHP builds are not supported.
- **Rate limits** are enforced by the API per room over a 60s window (reads 300/min, list
  & profile reads 120/min, writes 120/min, bulk deletes 10/min, batch 30/min).
  `HabboRateLimitException` (`429`) exposes `retryAfter()`.

### In-game vs API entity ids

The API uses "clean" ids. In-game wall-item ids are negative and Builders Club ids are
offset by a large constant. Use the static helpers on `HabboApi`:

```php
use HabboFeeling\HabboWebApi\HabboApi;

// If you know the kind:
$entityId = (string) HabboApi::apiEntityId('wall-items', $inGameId);

// If you only have the raw in-game furni id:
['kind' => $kind, 'id' => $id] = HabboApi::furniId($inGameId);
$score = HabboAPI::roomVariable($roomId, 'furni', 'score', $kind, (string) $id, $readKey);

// …and back to an in-game id:
$inGameId = HabboApi::inGameFurniId($kind, $id);
```

## Level-up add-on

`LevelUpper` turns a raw XP total into a level and progress. Pick the curve the room's
add-on is configured with:

```php
use HabboFeeling\HabboWebApi\LevelUp\LevelUpper;

$curve = LevelUpper::linear(stepSize: 100, maxLevel: 10);
// LevelUpper::interpolate([1 => 0, 5 => 1_000, 10 => 5_000]);
// LevelUpper::exponential(initialXp: 100, strength: 50, maxLevel: 10);

$curve->currentLevel($xp);        // 1-based level
$curve->progress($xp);            // xp into the current level
$curve->progressPercentage($xp);  // 0-100
$curve->xpRemaining($xp);         // xp to the next level
$curve->isMaxed($xp);             // bool
$curve->maxLevel();               // int
$curve->maxXp();                  // int
```

## Habbo Origins

Origins endpoints are prefixed `origins*` and key off a player's `uniquePlayerId`, not the
regular Habbo unique id.

```php
$matchIds = HabboAPI::originsMatchIds($playerId, ['limit' => 20]);   // string[]
$match    = HabboAPI::originsMatch($matchIds[0]);                    // ?OriginsMatchData
$skill    = HabboAPI::originsSkill($playerId, 'FISHING');            // ?OriginsSkillData
$board    = HabboAPI::originsSkillLeaderboard('FISHING', page: 1);   // ?OriginsSkillLeaderboardData
$habboIds = HabboAPI::originsHabboIds($playerId);                    // string[]

// Fishing derby — optional api_key query param. Detail endpoints have no schema → raw array.
$derbyIds = HabboAPI::originsDerbyIds($playerId, ['api_key' => $key]);   // string[]
$status   = HabboAPI::originsDerbyStatus(['api_key' => $key]);           // array
$derby    = HabboAPI::originsDerby($derbyIds[0], ['api_key' => $key]);   // array
```

## Testing against this client

Fake HTTP with Laravel's `Http::fake()` / `Http::preventStrayRequests()`; the client uses
`Illuminate\Support\Facades\Http` under the hood. Match on the hotel domain plus the API
path when asserting.
