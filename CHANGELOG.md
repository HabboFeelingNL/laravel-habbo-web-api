# Changelog

All notable changes to `laravel-habbo-web-api` will be documented in this file.

## Unreleased

- `HabboApi::furniId()` / `HabboApi::inGameFurniId()` to split an in-game furni
  id into the API `{kind, id}` pair and back, auto-detecting the wall-item and
  Builders Club encoding (a port of the API's reference `toFurniId`/`fromFurniId`).
- `HabboFeeling\HabboWebApi\LevelUp\LevelUpper` — XP → level calculator for the
  level-up add-on, with linear, interpolated and exponential curves (a port of
  the maintainers' reference `level-upper.ts`).
- Documented that Wired variable values are signed 64-bit integers and round-trip
  losslessly on 64-bit PHP.

## 1.0.0 - 2026-09-02

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
- `HabboAPI` facade.
