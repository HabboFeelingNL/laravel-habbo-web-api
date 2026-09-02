## Laravel Habbo Web API

`habbofeelingnl/laravel-habbo-web-api` is a client for the public Habbo Web API. Every
response is hydrated into a typed `spatie/laravel-data` DTO, and each hotel is reachable
on its own domain.

### Conventions

- Call the API through the `HabboFeeling\HabboWebApi\Facades\HabboAPI` facade, or resolve
  `HabboFeeling\HabboWebApi\HabboApi` from the container.
- The service class is `HabboApi` and the facade is `HabboAPI`. PHP class names are
  case-insensitive, so never `use` both in one file — import one and reference the other
  fully-qualified.
- Return contract: single-resource methods return `?XData` (`null` only on 404 / `304`);
  list methods return `Spatie\LaravelData\DataCollection` (empty when missing); `204`
  writes return `bool`; `get()` returns the raw decoded `array`.
- A missing resource is not an error. Anything else throws an exception extending
  `HabboFeeling\HabboWebApi\Exceptions\HabboApiException` — catch that for a blanket
  handler, or the specific subclasses (`HabboRateLimitException` has `retryAfter()`,
  `HabboMaintenanceException`, `HabboAuthException`, `HabboRequestException`,
  `HabboConnectionException`).
- `HabboAPI::hotel('habbo.com.br')` returns a fresh instance bound to that hotel; the
  original is untouched. Default hotel comes from `config('habbo-api.domain')`
  (`HABBO_API_DOMAIN`).
- Wired Variables endpoints are beta and need per-room read/write keys from the room's
  "Variable Global Add-on: Web API" furni. Origins endpoints are prefixed `origins*` and
  key off `uniquePlayerId`, not the regular Habbo unique id.

@verbatim
<code-snippet name="Typical lookups" lang="php">
use HabboFeeling\HabboWebApi\Facades\HabboAPI;

$user    = HabboAPI::userByName('whatwasit');      // ?UserData
$profile = HabboAPI::userProfile($user->uniqueId); // ?UserProfileData
$badges  = HabboAPI::userBadges($user->uniqueId);  // DataCollection<BadgeData>
$market  = HabboAPI::marketplaceStats(['throne']); // MarketplaceStatsData
</code-snippet>
@endverbatim

For anything beyond the basics — Wired Variables, entity-id conversion, the level-up
curves, or Origins — use the `habbo-web-api-development` skill.
