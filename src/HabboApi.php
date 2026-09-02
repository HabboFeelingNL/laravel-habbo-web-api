<?php

namespace HabboFeeling\HabboWebApi;

use Closure;
use HabboFeeling\HabboWebApi\Data\AchievementData;
use HabboFeeling\HabboWebApi\Data\BadgeData;
use HabboFeeling\HabboWebApi\Data\BadgeOwnersData;
use HabboFeeling\HabboWebApi\Data\FriendData;
use HabboFeeling\HabboWebApi\Data\GroupData;
use HabboFeeling\HabboWebApi\Data\GroupMemberData;
use HabboFeeling\HabboWebApi\Data\HotLookData;
use HabboFeeling\HabboWebApi\Data\MarketplaceStatsData;
use HabboFeeling\HabboWebApi\Data\Origins\OriginsMatchData;
use HabboFeeling\HabboWebApi\Data\Origins\OriginsSkillData;
use HabboFeeling\HabboWebApi\Data\Origins\OriginsSkillLeaderboardData;
use HabboFeeling\HabboWebApi\Data\RoomData;
use HabboFeeling\HabboWebApi\Data\UserData;
use HabboFeeling\HabboWebApi\Data\UserGroupData;
use HabboFeeling\HabboWebApi\Data\UserProfileData;
use HabboFeeling\HabboWebApi\Data\Wired\WiredBatchResultsData;
use HabboFeeling\HabboWebApi\Data\Wired\WiredCountData;
use HabboFeeling\HabboWebApi\Data\Wired\WiredPagedVariablesData;
use HabboFeeling\HabboWebApi\Data\Wired\WiredRoomVariablesData;
use HabboFeeling\HabboWebApi\Data\Wired\WiredVariableData;
use HabboFeeling\HabboWebApi\Data\Wired\WiredVariablesProfileData;
use HabboFeeling\HabboWebApi\Exceptions\HabboApiException;
use HabboFeeling\HabboWebApi\Exceptions\HabboAuthException;
use HabboFeeling\HabboWebApi\Exceptions\HabboConnectionException;
use HabboFeeling\HabboWebApi\Exceptions\HabboMaintenanceException;
use HabboFeeling\HabboWebApi\Exceptions\HabboRateLimitException;
use HabboFeeling\HabboWebApi\Exceptions\HabboRequestException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Spatie\LaravelData\DataCollection;

/**
 * Client for the public Habbo Web API (`https://www.habbo.<tld>/api/public`).
 *
 * Every hotel exposes the same API on its own domain, so the service is bound
 * to one domain at a time (habbo.com by default) and {@see self::hotel()} returns
 * a copy pointed at another hotel. Habbo Origins endpoints (matches, fishing
 * derby, skills, player-id mapping) are prefixed `origins*`.
 *
 * Every response is hydrated into a `HabboFeeling\HabboWebApi\Data` DTO.
 *
 * Failure handling:
 *  - a genuinely missing resource (HTTP 404 / `{"error":"not-found"}`) and an
 *    unchanged conditional read (HTTP 304) stay lenient: single reads return
 *    `null`, list reads an empty {@see DataCollection}, `bool` writes `false`;
 *  - anything else raises a
 *    {@see HabboApiException} —
 *    {@see HabboMaintenanceException} for `{"error":"maintenance"}`,
 *    {@see HabboAuthException} for 401/403 (wired key), {@see HabboRateLimitException}
 *    for 429, {@see HabboRequestException} for other non-2xx, and
 *    {@see HabboConnectionException} when the request never reached the hotel.
 */
class HabboApi
{
    /**
     * Smallest in-game furni id that denotes a Builders Club item; BC ids sit in
     * a high band offset by this constant (`0x7FFFFFFF - 0xFFFF`).
     */
    private const MIN_CLUB_FURNI_ID = 0x7FFFFFFF - 0xFFFF;

    private readonly string $domain;

    private readonly int $requestTimeout;

    public function __construct(?string $domain = null, ?int $requestTimeout = null)
    {
        $this->domain = $this->normalizeDomain($domain ?? config('habbo-api.domain'));
        $this->requestTimeout = $requestTimeout ?? (int) config('habbo-api.request_timeout', 15);
    }

    /**
     * Return a copy of the service that talks to another hotel.
     */
    public function hotel(string|\Stringable $hotel): self
    {
        return new self((string) $hotel, $this->requestTimeout);
    }

    /**
     * The fully qualified host this instance talks to, e.g. `www.habbo.com`.
     */
    public function domain(): string
    {
        return $this->domain;
    }

    /*
    |--------------------------------------------------------------------------
    | Meta
    |--------------------------------------------------------------------------
    */

    /**
     * Whether the hotel API answers the ping endpoint. Never throws — a
     * connection failure is reported as `false`.
     */
    public function ping(): bool
    {
        try {
            return $this->client()->get('/ping')->successful();
        } catch (ConnectionException) {
            return false;
        }
    }

    /**
     * Perform an arbitrary GET against the public API and return the raw decoded
     * body, for endpoints without a dedicated DTO method.
     *
     * @param  array<string, mixed>  $query
     * @return array<mixed>
     */
    public function get(string $path, array $query = []): array
    {
        return $this->send(fn () => $this->client()->get($this->normalizePath($path), $query))->json() ?? [];
    }

    /*
    |--------------------------------------------------------------------------
    | Achievements
    |--------------------------------------------------------------------------
    */

    /**
     * Every achievement with its level requirements.
     *
     * @return DataCollection<int, AchievementData>
     */
    public function achievements(): DataCollection
    {
        return AchievementData::collect($this->listPayload(fn () => $this->client()->get('/achievements')), DataCollection::class);
    }

    /**
     * Achievement progress for a single user (by unique id).
     *
     * @return DataCollection<int, AchievementData>
     */
    public function userAchievements(string $userId): DataCollection
    {
        return AchievementData::collect($this->listPayload(fn () => $this->client()->get("/achievements/{$userId}")), DataCollection::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Badges
    |--------------------------------------------------------------------------
    */

    /**
     * Owner details for a badge code.
     */
    public function badgeOwners(string $badgeCode): ?BadgeOwnersData
    {
        return $this->object(fn () => $this->client()->get("/badge/owners/{$badgeCode}"), BadgeOwnersData::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Groups
    |--------------------------------------------------------------------------
    */

    /**
     * A single group by its id.
     */
    public function group(string $id): ?GroupData
    {
        return $this->object(fn () => $this->client()->get("/groups/{$id}"), GroupData::class);
    }

    /**
     * Members of a group.
     *
     * @return DataCollection<int, GroupMemberData>
     */
    public function groupMembers(string $id): DataCollection
    {
        return GroupMemberData::collect($this->listPayload(fn () => $this->client()->get("/groups/{$id}/members")), DataCollection::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Marketplace
    |--------------------------------------------------------------------------
    */

    /**
     * Marketplace price statistics for a batch of items. Each item may be a bare
     * class name string or a `['item' => 'name']` shape.
     *
     * @param  array<int, string|array{item: string}>  $roomItems
     * @param  array<int, string|array{item: string}>  $wallItems
     */
    public function marketplaceStats(array $roomItems = [], array $wallItems = []): MarketplaceStatsData
    {
        return MarketplaceStatsData::from($this->payload(fn () => $this->client()->post('/marketplace/stats/batch', [
            'roomItems' => $this->itemList($roomItems),
            'wallItems' => $this->itemList($wallItems),
        ])) ?? []);
    }

    /*
    |--------------------------------------------------------------------------
    | Rooms
    |--------------------------------------------------------------------------
    */

    /**
     * Information about a public room.
     */
    public function room(int $roomId): ?RoomData
    {
        return $this->object(fn () => $this->client()->get("/rooms/{$roomId}"), RoomData::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Lists
    |--------------------------------------------------------------------------
    */

    /**
     * The current hot looks list. This endpoint answers with XML, which is
     * parsed here before hydrating the DTOs.
     *
     * @return DataCollection<int, HotLookData>
     */
    public function hotLooks(): DataCollection
    {
        $body = $this->send(fn () => $this->client()->get('/lists/hotlooks'))->body();

        $xml = @simplexml_load_string($body ?: '<habbos/>');

        $looks = [];

        foreach ($xml === false ? [] : $xml->habbo as $habbo) {
            $looks[] = [
                'figure' => (string) $habbo['figure'],
                'gender' => (string) $habbo['gender'] ?: null,
                'hash' => (string) $habbo['hash'] ?: null,
            ];
        }

        return HotLookData::collect($looks, DataCollection::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    */

    /**
     * Look a user up by exact name. Pass a previously returned ETag as
     * $ifNoneMatch to get `null` back when nothing changed (HTTP 304).
     */
    public function userByName(string $name, ?string $ifNoneMatch = null): ?UserData
    {
        return $this->object(fn () => $this->client($ifNoneMatch)->get('/users', ['name' => $name]), UserData::class);
    }

    /**
     * A user by unique id.
     */
    public function user(string $id, ?string $ifNoneMatch = null): ?UserData
    {
        return $this->object(fn () => $this->client($ifNoneMatch)->get("/users/{$id}"), UserData::class);
    }

    /**
     * A user's friends.
     *
     * @return DataCollection<int, FriendData>
     */
    public function userFriends(string $id): DataCollection
    {
        return FriendData::collect($this->listPayload(fn () => $this->client()->get("/users/{$id}/friends")), DataCollection::class);
    }

    /**
     * The groups a user belongs to.
     *
     * @return DataCollection<int, UserGroupData>
     */
    public function userGroups(string $id): DataCollection
    {
        return UserGroupData::collect($this->listPayload(fn () => $this->client()->get("/users/{$id}/groups")), DataCollection::class);
    }

    /**
     * A user's public rooms.
     *
     * @return DataCollection<int, RoomData>
     */
    public function userRooms(string $id): DataCollection
    {
        return RoomData::collect($this->listPayload(fn () => $this->client()->get("/users/{$id}/rooms")), DataCollection::class);
    }

    /**
     * A user's badges.
     *
     * @return DataCollection<int, BadgeData>
     */
    public function userBadges(string $id): DataCollection
    {
        return BadgeData::collect($this->listPayload(fn () => $this->client()->get("/users/{$id}/badges")), DataCollection::class);
    }

    /**
     * A user's full profile (groups, badges, friends, rooms inline).
     */
    public function userProfile(string $id): ?UserProfileData
    {
        return $this->object(fn () => $this->client()->get("/users/{$id}/profile"), UserProfileData::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Wired variables
    |
    | Room-scoped endpoints guarded by a read key and a write key. Both come from
    | a "Variable Global Add-on: Web API" furni placed in the room (currently
    | handed out to beta testers only). Reads send the key in the X-Wired-Read-Key
    | header, writes in the X-Wired-Write-Key header; a rejected key raises a
    | HabboAuthException.
    |
    | Rate limits from the API maintainers, applied per room over a 60s sustained
    | window with a 10s burst allowance:
    |
    |   simple reads    300/min (burst 60)
    |   list endpoints  120/min (burst 20)
    |   profile reads   120/min (burst 20)
    |   writes          120/min (burst 30)
    |   bulk deletes     10/min (burst 5)
    |   batch requests   30/min, plus a separate 500/min budget for batched writes
    |
    | The "/count" endpoints are cached server-side for 20s-600s depending on the
    | size of the count. This client does not throttle or cache for you — pace
    | calls from your own scheduler/queue, and cache profile/count reads yourself
    | if you expose them to a lot of users.
    |--------------------------------------------------------------------------
    */

    /**
     * List the wired variables configured in a room.
     */
    public function roomVariables(int $roomId, string $readKey): ?WiredRoomVariablesData
    {
        return $this->object(
            fn () => $this->wiredClient(readKey: $readKey)->get("/rooms/{$roomId}/variables"),
            WiredRoomVariablesData::class,
        );
    }

    /**
     * Read a single user or furni wired variable value.
     */
    public function roomVariable(int $roomId, string $scope, string $variableName, string $targetKind, string $entityId, string $readKey): ?WiredVariableData
    {
        return $this->object(
            fn () => $this->wiredClient(readKey: $readKey)->get($this->variablePath($roomId, $scope, $variableName, $targetKind, $entityId)),
            WiredVariableData::class,
        );
    }

    /**
     * Create or replace a single user or furni wired variable value.
     */
    public function setRoomVariable(int $roomId, string $scope, string $variableName, string $targetKind, string $entityId, int $value, string $writeKey): ?WiredVariableData
    {
        return $this->object(
            fn () => $this->wiredClient(writeKey: $writeKey)->put($this->variablePath($roomId, $scope, $variableName, $targetKind, $entityId), ['value' => $value]),
            WiredVariableData::class,
        );
    }

    /**
     * Update a single user or furni wired variable value.
     */
    public function updateRoomVariable(int $roomId, string $scope, string $variableName, string $targetKind, string $entityId, int $value, string $writeKey): ?WiredVariableData
    {
        return $this->object(
            fn () => $this->wiredClient(writeKey: $writeKey)->patch($this->variablePath($roomId, $scope, $variableName, $targetKind, $entityId), ['value' => $value]),
            WiredVariableData::class,
        );
    }

    /**
     * Delete one stored user or furni wired variable value. `true` on success,
     * `false` when the value did not exist (HTTP 404).
     */
    public function deleteRoomVariable(int $roomId, string $scope, string $variableName, string $targetKind, string $entityId, string $writeKey): bool
    {
        return $this->send(fn () => $this->wiredClient(writeKey: $writeKey)
            ->delete($this->variablePath($roomId, $scope, $variableName, $targetKind, $entityId)))->successful();
    }

    /**
     * List the stored values of a wired variable for one target kind.
     *
     * `page` is 1-based; `size` defaults to 50 and is capped at 100.
     *
     * @param  array{order_by?: 'value'|'creation_time'|'update_time', order_dir?: 'asc'|'desc', page?: int, size?: int}  $query
     */
    public function listRoomVariableValues(int $roomId, string $scope, string $variableName, string $targetKind, string $readKey, array $query = []): ?WiredPagedVariablesData
    {
        return $this->object(
            fn () => $this->wiredClient(readKey: $readKey)->get("/rooms/{$roomId}/variables/{$scope}/{$variableName}/{$targetKind}", $query),
            WiredPagedVariablesData::class,
        );
    }

    /**
     * Count the stored values of a wired variable for one target kind.
     */
    public function countRoomVariableValues(int $roomId, string $scope, string $variableName, string $targetKind, string $readKey): ?WiredCountData
    {
        return $this->object(
            fn () => $this->wiredClient(readKey: $readKey)->get("/rooms/{$roomId}/variables/{$scope}/{$variableName}/{$targetKind}/count"),
            WiredCountData::class,
        );
    }

    /**
     * Delete all stored values for multiple wired variables by name.
     *
     * @param  array<int, string>  $variableNames
     */
    public function bulkDeleteRoomVariables(int $roomId, array $variableNames, string $writeKey): bool
    {
        return $this->send(fn () => $this->wiredClient(writeKey: $writeKey)
            ->post("/rooms/{$roomId}/variables/bulk-delete", ['variables' => array_values($variableNames)]))->successful();
    }

    /**
     * Execute a batch (1-50) of operations against one wired variable.
     *
     * @param  array<int, array<string, mixed>>  $requests
     */
    public function batchRoomVariable(int $roomId, string $scope, string $variableName, array $requests, string $writeKey): ?WiredBatchResultsData
    {
        return $this->object(
            fn () => $this->wiredClient(writeKey: $writeKey)->post("/rooms/{$roomId}/variables/{$scope}/{$variableName}/batch", ['requests' => array_values($requests)]),
            WiredBatchResultsData::class,
        );
    }

    /**
     * Read a global room wired variable.
     */
    public function globalRoomVariable(int $roomId, string $variableName, string $readKey): ?WiredVariableData
    {
        return $this->object(
            fn () => $this->wiredClient(readKey: $readKey)->get("/rooms/{$roomId}/variables/global/{$variableName}"),
            WiredVariableData::class,
        );
    }

    /**
     * Update a global room wired variable.
     */
    public function updateGlobalRoomVariable(int $roomId, string $variableName, int $value, string $writeKey): ?WiredVariableData
    {
        return $this->object(
            fn () => $this->wiredClient(writeKey: $writeKey)->patch("/rooms/{$roomId}/variables/global/{$variableName}", ['value' => $value]),
            WiredVariableData::class,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Wired variable profiles
    |--------------------------------------------------------------------------
    */

    /**
     * Read a user variables profile by name or unique id.
     */
    public function userVariablesProfileByName(int $roomId, string $readKey, ?string $name = null, ?string $uniqueId = null): ?WiredVariablesProfileData
    {
        return $this->object(
            fn () => $this->wiredClient(readKey: $readKey)->get("/rooms/{$roomId}/variables_profile/user/users", array_filter([
                'name' => $name,
                'unique_id' => $uniqueId,
            ], fn ($value) => $value !== null)),
            WiredVariablesProfileData::class,
        );
    }

    /**
     * Read a user, pet, or bot variables profile.
     */
    public function userVariablesProfile(int $roomId, string $targetKind, string $entityId, string $readKey): ?WiredVariablesProfileData
    {
        return $this->object(
            fn () => $this->wiredClient(readKey: $readKey)->get("/rooms/{$roomId}/variables_profile/user/{$targetKind}/{$entityId}"),
            WiredVariablesProfileData::class,
        );
    }

    /**
     * Patch a user, pet, or bot variables profile. A null value clears that variable.
     *
     * @param  array<string, int|null>  $variables
     */
    public function patchUserVariablesProfile(int $roomId, string $targetKind, string $entityId, array $variables, string $writeKey): ?WiredVariablesProfileData
    {
        return $this->object(
            fn () => $this->wiredClient(writeKey: $writeKey)->patch("/rooms/{$roomId}/variables_profile/user/{$targetKind}/{$entityId}", ['variables' => $variables]),
            WiredVariablesProfileData::class,
        );
    }

    /**
     * Delete a user, pet, or bot variables profile.
     */
    public function deleteUserVariablesProfile(int $roomId, string $targetKind, string $entityId, string $writeKey): bool
    {
        return $this->send(fn () => $this->wiredClient(writeKey: $writeKey)
            ->delete("/rooms/{$roomId}/variables_profile/user/{$targetKind}/{$entityId}"))->successful();
    }

    /**
     * Read a furni variables profile.
     */
    public function furniVariablesProfile(int $roomId, string $targetKind, string $entityId, string $readKey): ?WiredVariablesProfileData
    {
        return $this->object(
            fn () => $this->wiredClient(readKey: $readKey)->get("/rooms/{$roomId}/variables_profile/furni/{$targetKind}/{$entityId}"),
            WiredVariablesProfileData::class,
        );
    }

    /**
     * Patch a furni variables profile. A null value clears that variable.
     *
     * @param  array<string, int|null>  $variables
     */
    public function patchFurniVariablesProfile(int $roomId, string $targetKind, string $entityId, array $variables, string $writeKey): ?WiredVariablesProfileData
    {
        return $this->object(
            fn () => $this->wiredClient(writeKey: $writeKey)->patch("/rooms/{$roomId}/variables_profile/furni/{$targetKind}/{$entityId}", ['variables' => $variables]),
            WiredVariablesProfileData::class,
        );
    }

    /**
     * Read the global variables profile.
     */
    public function globalVariablesProfile(int $roomId, string $readKey): ?WiredVariablesProfileData
    {
        return $this->object(
            fn () => $this->wiredClient(readKey: $readKey)->get("/rooms/{$roomId}/variables_profile/global"),
            WiredVariablesProfileData::class,
        );
    }

    /**
     * Patch the global variables profile.
     *
     * @param  array<string, int>  $variables
     */
    public function patchGlobalVariablesProfile(int $roomId, array $variables, string $writeKey): ?WiredVariablesProfileData
    {
        return $this->object(
            fn () => $this->wiredClient(writeKey: $writeKey)->patch("/rooms/{$roomId}/variables_profile/global", ['variables' => $variables]),
            WiredVariablesProfileData::class,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Origins
    |
    | Endpoints for Habbo Origins. They key off a player's uniquePlayerId
    | (Origins minigame identity) rather than the regular Habbo unique id;
    | `originsHabboIds()` maps between the two. Fishing derby endpoints take an
    | optional `api_key` query parameter.
    |--------------------------------------------------------------------------
    */

    /**
     * Ids of the minigame matches a player took part in, most recent first.
     *
     * @param  array{offset?: int, limit?: int, start_time?: string, end_time?: string}  $query
     * @return array<int, string>
     */
    public function originsMatchIds(string $uniquePlayerId, array $query = []): array
    {
        return $this->listPayload(fn () => $this->client()->get("/matches/v1/{$uniquePlayerId}/ids", $query));
    }

    /**
     * Full detail for one minigame match.
     */
    public function originsMatch(string $uniqueMatchId): ?OriginsMatchData
    {
        return $this->object(fn () => $this->client()->get("/matches/v1/{$uniqueMatchId}"), OriginsMatchData::class);
    }

    /**
     * Ids of the fishing derbies a player took part in, most recent first.
     *
     * @param  array{offset?: int, limit?: int, start_time?: string, end_time?: string, api_key?: string}  $query
     * @return array<int, string>
     */
    public function originsDerbyIds(string $uniquePlayerId, array $query = []): array
    {
        return $this->listPayload(fn () => $this->client()->get("/minigame/derby/v1/{$uniquePlayerId}/ids", $query));
    }

    /**
     * Full detail for one fishing derby. The API spec does not define this
     * response body, so it is returned as a raw decoded array.
     *
     * @param  array{api_key?: string}  $query
     * @return array<string, mixed>
     */
    public function originsDerby(string $uniqueDerbyId, array $query = []): array
    {
        return $this->payload(fn () => $this->client()->get("/minigame/derby/v1/{$uniqueDerbyId}", $query)) ?? [];
    }

    /**
     * The current fishing derby status. The API spec does not define this
     * response body, so it is returned as a raw decoded array.
     *
     * @param  array{api_key?: string}  $query
     * @return array<string, mixed>
     */
    public function originsDerbyStatus(array $query = []): array
    {
        return $this->payload(fn () => $this->client()->get('/minigame/derby/v1/status', $query)) ?? [];
    }

    /**
     * A player's progress in one skill.
     */
    public function originsSkill(string $uniquePlayerId, string $skillType = 'FISHING'): ?OriginsSkillData
    {
        return $this->object(
            fn () => $this->client()->get("/skills/{$uniquePlayerId}", ['skillType' => $skillType]),
            OriginsSkillData::class,
        );
    }

    /**
     * A page of a skill leaderboard.
     */
    public function originsSkillLeaderboard(string $skillType = 'FISHING', int $page = 1): ?OriginsSkillLeaderboardData
    {
        return $this->object(
            fn () => $this->client()->get('/skills/leaderboard', ['skillType' => $skillType, 'page' => $page]),
            OriginsSkillLeaderboardData::class,
        );
    }

    /**
     * Map an Origins uniquePlayerId to the matching Habbo unique id(s).
     *
     * @return array<int, string>
     */
    public function originsHabboIds(string $uniquePlayerId): array
    {
        return $this->listPayload(fn () => $this->client()->get("/users/by-playerId/{$uniquePlayerId}"));
    }

    /**
     * Convert an in-game entity id to the API entity id for a target kind.
     *
     * The API uses "clean" identifiers while the game exposes legacy ones:
     * wall-item ids are negated and Builders Club ids are offset by a large
     * constant. `furni`, `users`, `pets` and `bots` ids are already clean and
     * pass through untouched. A `wall-items-bc` id is negated around its BC
     * offset (negate first, then remove the offset).
     *
     * If you only hold the raw in-game id and don't know its kind, use
     * {@see self::furniId()} instead — it derives the kind from the number.
     */
    public static function apiEntityId(string $targetKind, int $inGameId): int
    {
        $id = $inGameId;

        if (str_starts_with($targetKind, 'wall-items')) {
            $id *= -1;
        }

        if (str_ends_with($targetKind, '-bc')) {
            $id -= self::MIN_CLUB_FURNI_ID;
        }

        return $id;
    }

    /**
     * Split an in-game furni id into the API target kind and clean id.
     *
     * The game encodes the kind into the number: wall-item ids are negative, and
     * Builders Club ids sit in a high band offset by a large constant. The API
     * instead takes a `kind` path segment plus a small clean id — the
     * `['kind' => ..., 'id' => ...]` pair returned here, ready to pass straight
     * into the wired methods' `$targetKind` / `$entityId` arguments.
     *
     * 1:1 port of the API's reference `toFurniId()`.
     *
     * @return array{kind: 'furni'|'furni-bc'|'wall-items'|'wall-items-bc', id: int}
     */
    public static function furniId(int $inGameFurniId): array
    {
        $isWall = $inGameFurniId < 0;
        $normalized = $isWall ? -$inGameFurniId : $inGameFurniId;

        if ($normalized >= self::MIN_CLUB_FURNI_ID) {
            return [
                'kind' => $isWall ? 'wall-items-bc' : 'furni-bc',
                'id' => $normalized - self::MIN_CLUB_FURNI_ID,
            ];
        }

        return [
            'kind' => $isWall ? 'wall-items' : 'furni',
            'id' => $normalized,
        ];
    }

    /**
     * Rebuild the in-game furni id from an API target kind and clean id. The
     * inverse of {@see self::furniId()} (the API's reference `fromFurniId()`).
     */
    public static function inGameFurniId(string $kind, int $id): int
    {
        $base = in_array($kind, ['furni-bc', 'wall-items-bc'], true)
            ? $id + self::MIN_CLUB_FURNI_ID
            : $id;

        return in_array($kind, ['wall-items', 'wall-items-bc'], true) ? -$base : $base;
    }

    /*
    |--------------------------------------------------------------------------
    | Internals
    |--------------------------------------------------------------------------
    */

    /**
     * Hydrate a single DTO from a response, or null for a missing resource.
     *
     * @template TData of \Spatie\LaravelData\Data
     *
     * @param  Closure(): Response  $request
     * @param  class-string<TData>  $data
     * @return TData|null
     */
    private function object(Closure $request, string $data): ?object
    {
        $payload = $this->payload($request);

        return $payload === null ? null : $data::from($payload);
    }

    /**
     * The decoded body as an array, or null for a missing resource (HTTP 404 /
     * `not-found`) or an unchanged conditional read (HTTP 304).
     *
     * @param  Closure(): Response  $request
     * @return array<string, mixed>|null
     */
    private function payload(Closure $request): ?array
    {
        $json = $this->send($request)->json();

        if (! is_array($json) || array_key_exists('error', $json)) {
            return null;
        }

        return $json;
    }

    /**
     * The decoded body as a list, or an empty array for a missing resource.
     *
     * @param  Closure(): Response  $request
     * @return array<int, mixed>
     */
    private function listPayload(Closure $request): array
    {
        $json = $this->send($request)->json();

        return is_array($json) && ! array_key_exists('error', $json) ? array_values($json) : [];
    }

    /**
     * Run the request, translating transport and HTTP failures into a
     * {@see HabboApiException}. A missing
     * resource (404 / not-found) and an unchanged conditional read (304) are
     * returned untouched for the caller to treat as "no data".
     *
     * @param  Closure(): Response  $request
     */
    private function send(Closure $request): Response
    {
        try {
            $response = $request();
        } catch (ConnectionException $e) {
            throw new HabboConnectionException($e->getMessage(), 0, $e);
        }

        $json = $response->json();
        $error = is_array($json) ? ($json['error'] ?? null) : null;

        if ($error === 'maintenance') {
            throw new HabboMaintenanceException($response);
        }

        if ($response->successful() || $response->status() === 304) {
            return $response;
        }

        if ($response->status() === 404 || $error === 'not-found') {
            return $response;
        }

        throw match (true) {
            $response->status() === 429 => new HabboRateLimitException($response),
            in_array($response->status(), [401, 403], true) => new HabboAuthException($response),
            default => new HabboRequestException($response),
        };
    }

    private function client(?string $ifNoneMatch = null): PendingRequest
    {
        $client = Http::baseUrl("https://{$this->domain}/api/public")
            ->acceptJson()
            ->timeout($this->requestTimeout);

        return $ifNoneMatch === null ? $client : $client->withHeaders(['If-None-Match' => $ifNoneMatch]);
    }

    private function wiredClient(?string $readKey = null, ?string $writeKey = null): PendingRequest
    {
        $client = $this->client();

        if ($readKey !== null) {
            $client = $client->withHeaders(['X-Wired-Read-Key' => $readKey]);
        }

        if ($writeKey !== null) {
            $client = $client->withHeaders(['X-Wired-Write-Key' => $writeKey]);
        }

        return $client;
    }

    private function variablePath(int $roomId, string $scope, string $variableName, string $targetKind, string $entityId): string
    {
        return "/rooms/{$roomId}/variables/{$scope}/{$variableName}/{$targetKind}/{$entityId}";
    }

    /**
     * @param  array<int, string|array{item: string}>  $items
     * @return array<int, array{item: string}>
     */
    private function itemList(array $items): array
    {
        return array_values(array_map(
            fn (string|array $item): array => is_array($item) ? $item : ['item' => $item],
            $items,
        ));
    }

    private function normalizeDomain(string $domain): string
    {
        $host = preg_replace('#^https?://#', '', trim($domain));
        $host = preg_replace('#^www\.#', '', rtrim($host, '/'));

        return "www.{$host}";
    }

    private function normalizePath(string $path): string
    {
        return '/'.ltrim(preg_replace('#^/?api/public/?#', '', $path), '/');
    }
}
