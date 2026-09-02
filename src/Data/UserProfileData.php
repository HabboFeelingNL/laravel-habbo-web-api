<?php

namespace HabboFeeling\HabboWebApi\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

/**
 * The `/users/{id}/profile` payload: the {@see UserData} under `user`, plus the
 * user's groups, badges, friends and rooms inline.
 */
class UserProfileData extends Data
{
    /**
     * @param  array<int, UserGroupData>  $groups
     * @param  array<int, BadgeData>  $badges
     * @param  array<int, FriendData>  $friends
     * @param  array<int, RoomData>  $rooms
     */
    public function __construct(
        public UserData $user,
        #[DataCollectionOf(UserGroupData::class)]
        public array $groups = [],
        #[DataCollectionOf(BadgeData::class)]
        public array $badges = [],
        #[DataCollectionOf(FriendData::class)]
        public array $friends = [],
        #[DataCollectionOf(RoomData::class)]
        public array $rooms = [],
    ) {}
}
