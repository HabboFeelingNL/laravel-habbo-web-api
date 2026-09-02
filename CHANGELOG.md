# Changelog

All notable changes to `laravel-habbo-web-api` will be documented in this file.

## Unreleased

- Initial extraction from the HabboFeeling application.
- Supports PHP 8.1+ and Laravel 10, 11 and 12.
- `HabboApi` client covering the full public Habbo Web API, with per-hotel
  domain routing via `hotel()`.
- Habbo Origins endpoints (`origins*`): minigame match ids and details, fishing
  derby ids/details/status, skill progress and leaderboards, and
  `uniquePlayerId` → Habbo unique id mapping.
- Typed `HabboFeeling\HabboWebApi\Data` DTOs for every response; list endpoints
  return `Spatie\LaravelData\DataCollection`.
- Wired Variables support (beta): room variables, global variables, paged value
  listing, counts, bulk delete, batch operations and variable profiles.
- `HabboApi::apiEntityId()` helper to convert in-game entity ids (negated
  wall-item ids, offset Builders Club ids) to their API form.
- `Habbo` facade.
