<?php

namespace HabboFeeling\HabboWebApi\Facades;

use HabboFeeling\HabboWebApi\HabboApi;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \HabboFeeling\HabboWebApi\HabboApi hotel(string|\Stringable $hotel)
 * @method static string domain()
 * @method static bool ping()
 * @method static array get(string $path, array $query = [])
 * @method static \Spatie\LaravelData\DataCollection achievements()
 * @method static \Spatie\LaravelData\DataCollection userAchievements(string $userId)
 * @method static \HabboFeeling\HabboWebApi\Data\BadgeOwnersData|null badgeOwners(string $badgeCode)
 * @method static \HabboFeeling\HabboWebApi\Data\GroupData|null group(string $id)
 * @method static \Spatie\LaravelData\DataCollection groupMembers(string $id)
 * @method static \HabboFeeling\HabboWebApi\Data\MarketplaceStatsData marketplaceStats(array $roomItems = [], array $wallItems = [])
 * @method static \HabboFeeling\HabboWebApi\Data\RoomData|null room(int $roomId)
 * @method static \Spatie\LaravelData\DataCollection hotLooks()
 * @method static \HabboFeeling\HabboWebApi\Data\UserData|null userByName(string $name, ?string $ifNoneMatch = null)
 * @method static \HabboFeeling\HabboWebApi\Data\UserData|null user(string $id, ?string $ifNoneMatch = null)
 * @method static \Spatie\LaravelData\DataCollection userFriends(string $id)
 * @method static \Spatie\LaravelData\DataCollection userGroups(string $id)
 * @method static \Spatie\LaravelData\DataCollection userRooms(string $id)
 * @method static \Spatie\LaravelData\DataCollection userBadges(string $id)
 * @method static \HabboFeeling\HabboWebApi\Data\UserProfileData|null userProfile(string $id)
 * @method static \HabboFeeling\HabboWebApi\Data\Wired\WiredRoomVariablesData|null roomVariables(int $roomId, string $readKey)
 * @method static \HabboFeeling\HabboWebApi\Data\Wired\WiredVariableData|null roomVariable(int $roomId, string $scope, string $variableName, string $targetKind, string $entityId, string $readKey)
 * @method static \HabboFeeling\HabboWebApi\Data\Wired\WiredVariableData|null setRoomVariable(int $roomId, string $scope, string $variableName, string $targetKind, string $entityId, int $value, string $writeKey)
 * @method static \HabboFeeling\HabboWebApi\Data\Wired\WiredVariableData|null updateRoomVariable(int $roomId, string $scope, string $variableName, string $targetKind, string $entityId, int $value, string $writeKey)
 * @method static bool deleteRoomVariable(int $roomId, string $scope, string $variableName, string $targetKind, string $entityId, string $writeKey)
 * @method static \HabboFeeling\HabboWebApi\Data\Wired\WiredPagedVariablesData|null listRoomVariableValues(int $roomId, string $scope, string $variableName, string $targetKind, string $readKey, array $query = [])
 * @method static \HabboFeeling\HabboWebApi\Data\Wired\WiredCountData|null countRoomVariableValues(int $roomId, string $scope, string $variableName, string $targetKind, string $readKey)
 * @method static bool bulkDeleteRoomVariables(int $roomId, array $variableNames, string $writeKey)
 * @method static \HabboFeeling\HabboWebApi\Data\Wired\WiredBatchResultsData|null batchRoomVariable(int $roomId, string $scope, string $variableName, array $requests, string $writeKey)
 * @method static \HabboFeeling\HabboWebApi\Data\Wired\WiredVariableData|null globalRoomVariable(int $roomId, string $variableName, string $readKey)
 * @method static \HabboFeeling\HabboWebApi\Data\Wired\WiredVariableData|null updateGlobalRoomVariable(int $roomId, string $variableName, int $value, string $writeKey)
 * @method static \HabboFeeling\HabboWebApi\Data\Wired\WiredVariablesProfileData|null userVariablesProfileByName(int $roomId, string $readKey, ?string $name = null, ?string $uniqueId = null)
 * @method static \HabboFeeling\HabboWebApi\Data\Wired\WiredVariablesProfileData|null userVariablesProfile(int $roomId, string $targetKind, string $entityId, string $readKey)
 * @method static \HabboFeeling\HabboWebApi\Data\Wired\WiredVariablesProfileData|null patchUserVariablesProfile(int $roomId, string $targetKind, string $entityId, array $variables, string $writeKey)
 * @method static bool deleteUserVariablesProfile(int $roomId, string $targetKind, string $entityId, string $writeKey)
 * @method static \HabboFeeling\HabboWebApi\Data\Wired\WiredVariablesProfileData|null furniVariablesProfile(int $roomId, string $targetKind, string $entityId, string $readKey)
 * @method static \HabboFeeling\HabboWebApi\Data\Wired\WiredVariablesProfileData|null patchFurniVariablesProfile(int $roomId, string $targetKind, string $entityId, array $variables, string $writeKey)
 * @method static \HabboFeeling\HabboWebApi\Data\Wired\WiredVariablesProfileData|null globalVariablesProfile(int $roomId, string $readKey)
 * @method static \HabboFeeling\HabboWebApi\Data\Wired\WiredVariablesProfileData|null patchGlobalVariablesProfile(int $roomId, array $variables, string $writeKey)
 * @method static array<int, string> originsMatchIds(string $uniquePlayerId, array $query = [])
 * @method static \HabboFeeling\HabboWebApi\Data\Origins\OriginsMatchData|null originsMatch(string $uniqueMatchId)
 * @method static array<int, string> originsDerbyIds(string $uniquePlayerId, array $query = [])
 * @method static array<string, mixed> originsDerby(string $uniqueDerbyId, array $query = [])
 * @method static array<string, mixed> originsDerbyStatus(array $query = [])
 * @method static \HabboFeeling\HabboWebApi\Data\Origins\OriginsSkillData|null originsSkill(string $uniquePlayerId, string $skillType = 'FISHING')
 * @method static \HabboFeeling\HabboWebApi\Data\Origins\OriginsSkillLeaderboardData|null originsSkillLeaderboard(string $skillType = 'FISHING', int $page = 1)
 * @method static array<int, string> originsHabboIds(string $uniquePlayerId)
 * @method static int apiEntityId(string $targetKind, int $inGameId)
 *
 * @see HabboApi
 */
class Habbo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return HabboApi::class;
    }
}
