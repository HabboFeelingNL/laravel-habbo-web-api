<?php

use Carbon\CarbonImmutable;
use HabboFeeling\HabboWebApi\Data\AchievementData;
use HabboFeeling\HabboWebApi\Data\MarketplaceStatsData;
use HabboFeeling\HabboWebApi\Data\UserData;
use HabboFeeling\HabboWebApi\Data\UserProfileData;
use HabboFeeling\HabboWebApi\Data\Wired\WiredVariableData;
use HabboFeeling\HabboWebApi\Exceptions\HabboAuthException;
use HabboFeeling\HabboWebApi\Exceptions\HabboConnectionException;
use HabboFeeling\HabboWebApi\Exceptions\HabboMaintenanceException;
use HabboFeeling\HabboWebApi\Exceptions\HabboRateLimitException;
use HabboFeeling\HabboWebApi\Exceptions\HabboRequestException;
use HabboFeeling\HabboWebApi\HabboApi;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Spatie\LaravelData\DataCollection;

beforeEach(function () {
    Http::preventStrayRequests();
});

/*
|--------------------------------------------------------------------------
| Domain / hotel routing
|--------------------------------------------------------------------------
*/

it('normalizes hotel domains to the www public host', function (string $input, string $expected) {
    expect((new HabboApi($input))->domain())->toBe($expected);
})->with([
    'bare domain' => ['habbo.com', 'www.habbo.com'],
    'already prefixed' => ['www.habbo.com', 'www.habbo.com'],
    'full url' => ['https://www.habbo.com/', 'www.habbo.com'],
    'multi part tld' => ['habbo.com.br', 'www.habbo.com.br'],
]);

it('talks to another hotel through hotel() without mutating the original instance', function () {
    Http::fake([
        'www.habbo.com/api/public/*' => Http::response([]),
        'www.habbo.com.br/api/public/*' => Http::response([]),
    ]);

    $service = app(HabboApi::class);
    $service->hotel('habbo.com.br')->achievements();
    $service->achievements();

    Http::assertSent(fn ($request) => $request->url() === 'https://www.habbo.com.br/api/public/achievements');
    Http::assertSent(fn ($request) => $request->url() === 'https://www.habbo.com/api/public/achievements');
});

it('resolves the HabboAPI facade to the container singleton', function () {
    Http::fake(['www.habbo.com/api/public/*' => Http::response(['error' => 'not-found'], 404)]);

    expect(HabboFeeling\HabboWebApi\Facades\HabboAPI::domain())->toBe('www.habbo.com')
        ->and(HabboFeeling\HabboWebApi\Facades\HabboAPI::group('x'))->toBeNull();
});

it('accepts any Stringable as the hotel in hotel()', function () {
    Http::fake(['www.habbo.es/api/public/*' => Http::response('<habbos/>')]);

    $hotel = new class implements Stringable
    {
        public function __toString(): string
        {
            return 'habbo.es';
        }
    };

    app(HabboApi::class)->hotel($hotel)->hotLooks();

    Http::assertSent(fn ($request) => $request->url() === 'https://www.habbo.es/api/public/lists/hotlooks');
});

/*
|--------------------------------------------------------------------------
| DTO hydration
|--------------------------------------------------------------------------
*/

it('hydrates a user into a UserData DTO with parsed dates', function () {
    Http::fake(['www.habbo.com/api/public/users/hhus-1' => Http::response([
        'uniqueId' => 'hhus-1',
        'name' => 'Cerbaro',
        'figureString' => 'hr-100',
        'motto' => 'I love Habbo!',
        'online' => false,
        'memberSince' => '2022-02-03T10:19:39.000+0000',
        'currentLevel' => 5,
        'selectedBadges' => ['ADM', 'NL1'],
    ])]);

    $user = app(HabboApi::class)->user('hhus-1');

    expect($user)->toBeInstanceOf(UserData::class)
        ->and($user->name)->toBe('Cerbaro')
        ->and($user->currentLevel)->toBe(5)
        ->and($user->selectedBadges)->toBe(['ADM', 'NL1'])
        ->and($user->memberSince)->toBeInstanceOf(CarbonImmutable::class)
        ->and($user->memberSince->toDateString())->toBe('2022-02-03');
});

it('returns null for a missing single resource', function () {
    Http::fake(['www.habbo.com/api/public/groups/*' => Http::response(['error' => 'not-found'], 404)]);

    expect(app(HabboApi::class)->group('missing'))->toBeNull();
});

it('returns null when a conditional request is not modified', function () {
    Http::fake(['www.habbo.com/api/public/users*' => Http::response('', 304)]);

    expect(app(HabboApi::class)->userByName('Cerbaro', 'W/"abc123"'))->toBeNull();
});

it('hydrates achievements into a DataCollection with nested detail and requirements', function () {
    Http::fake(['www.habbo.com/api/public/achievements' => Http::response([
        [
            'achievement' => ['id' => 313, 'name' => 'AchievementName', 'creationTime' => '2000-01-01', 'category' => 'identity'],
            'levelRequirements' => [
                ['level' => 1, 'requiredScore' => 1],
                ['level' => 2, 'requiredScore' => 5],
            ],
        ],
    ])]);

    $achievements = app(HabboApi::class)->achievements();

    expect($achievements)->toBeInstanceOf(DataCollection::class)
        ->and($achievements)->toHaveCount(1);

    $first = $achievements[0];

    expect($first)->toBeInstanceOf(AchievementData::class)
        ->and($first->achievement->id)->toBe(313)
        ->and($first->achievement->creationTime)->toBeInstanceOf(CarbonImmutable::class)
        ->and($first->levelRequirements)->toHaveCount(2)
        ->and($first->levelRequirements[1]->requiredScore)->toBe(5);
});

it('returns an empty DataCollection for a list endpoint error', function () {
    Http::fake(['www.habbo.com/api/public/users/*/friends' => Http::response(['error' => 'not-found'], 404)]);

    $friends = app(HabboApi::class)->userFriends('unknown');

    expect($friends)->toBeInstanceOf(DataCollection::class)
        ->and($friends)->toHaveCount(0);
});

/*
|--------------------------------------------------------------------------
| Failure handling
|--------------------------------------------------------------------------
*/

it('throws HabboMaintenanceException on a maintenance envelope', function () {
    Http::fake(['www.habbo.com/api/public/*' => Http::response(['error' => 'maintenance'], 503)]);

    app(HabboApi::class)->user('hhus-1');
})->throws(HabboMaintenanceException::class, 'The Habbo hotel is in maintenance.');

it('throws HabboRequestException carrying the response on a 500', function () {
    Http::fake(['www.habbo.com/api/public/*' => Http::response('boom', 500)]);

    try {
        app(HabboApi::class)->group('g-1');
        expect()->fail('expected HabboRequestException');
    } catch (HabboRequestException $e) {
        expect($e->status())->toBe(500)
            ->and($e->response->body())->toBe('boom');
    }
});

it('throws HabboAuthException when a wired key is rejected', function () {
    Http::fake(['www.habbo.com/api/public/*' => Http::response(['error' => 'forbidden'], 403)]);

    app(HabboApi::class)->roomVariable(42, 'user', 'score', 'users', '7', 'bad-key');
})->throws(HabboAuthException::class);

it('throws HabboRateLimitException with the Retry-After hint on a 429', function () {
    Http::fake(['www.habbo.com/api/public/*' => Http::response('', 429, ['Retry-After' => '2'])]);

    try {
        app(HabboApi::class)->achievements();
        expect()->fail('expected HabboRateLimitException');
    } catch (HabboRateLimitException $e) {
        expect($e->retryAfter())->toBe(2);
    }
});

it('wraps a connection failure in HabboConnectionException', function () {
    Http::fake(fn () => throw new ConnectionException('cURL error 28: timed out'));

    app(HabboApi::class)->user('hhus-1');
})->throws(HabboConnectionException::class);

it('still returns null on a plain 404 and false on a failed delete', function () {
    Http::fake([
        'www.habbo.com/api/public/groups/*' => Http::response('', 404),
        'www.habbo.com/api/public/rooms/*' => Http::response('', 404),
    ]);

    expect(app(HabboApi::class)->group('missing'))->toBeNull()
        ->and(app(HabboApi::class)->deleteRoomVariable(42, 'user', 'score', 'users', '7', 'k'))->toBeFalse();
});

it('hydrates the nested collections of a user profile', function () {
    Http::fake(['www.habbo.com/api/public/users/hhus-1/profile' => Http::response([
        'user' => ['uniqueId' => 'hhus-1', 'name' => 'Cerbaro'],
        'groups' => [['id' => 'g-1', 'name' => 'The Habbo Club', 'isAdmin' => true]],
        'badges' => [['code' => 'ADM', 'name' => 'Staff']],
        'friends' => [['uniqueId' => 'hhus-2', 'name' => 'Frank']],
        'rooms' => [['id' => 10305181, 'name' => 'My Room']],
    ])]);

    $profile = app(HabboApi::class)->userProfile('hhus-1');

    expect($profile)->toBeInstanceOf(UserProfileData::class)
        ->and($profile->user->name)->toBe('Cerbaro')
        ->and($profile->groups[0]->isAdmin)->toBeTrue()
        ->and($profile->badges[0]->code)->toBe('ADM')
        ->and($profile->friends[0]->name)->toBe('Frank')
        ->and($profile->rooms[0]->id)->toBe(10305181);
});

it('parses the hotlooks XML feed into HotLookData', function () {
    Http::fake(['www.habbo.com/api/public/lists/hotlooks' => Http::response(
        '<habbos url="/habbo-imaging/avatar/"><habbo gender="f" figure="hr-515" hash="abc"/><habbo gender="m" figure="hd-205" hash="def"/></habbos>',
    )]);

    $looks = app(HabboApi::class)->hotLooks();

    expect($looks)->toHaveCount(2)
        ->and($looks[0]->gender)->toBe('f')
        ->and($looks[0]->figure)->toBe('hr-515')
        ->and($looks[1]->hash)->toBe('def');
});

it('hydrates nested marketplace item stats and history', function () {
    Http::fake(['www.habbo.com/api/public/marketplace/stats/batch' => Http::response([
        'status' => 'OK',
        'roomItemData' => [[
            'item' => 'chair',
            'statsDate' => '2024-01-20',
            'averagePrice' => 4,
            'history' => [['dayOffset' => '-1', 'averagePrice' => '2']],
        ]],
        'wallItemData' => [],
    ])]);

    $stats = app(HabboApi::class)->marketplaceStats(['chair']);

    expect($stats)->toBeInstanceOf(MarketplaceStatsData::class)
        ->and($stats->status)->toBe('OK')
        ->and($stats->roomItemData[0]->item)->toBe('chair')
        ->and($stats->roomItemData[0]->averagePrice)->toBe(4)
        ->and($stats->roomItemData[0]->history[0]->dayOffset)->toBe('-1');
});

/*
|--------------------------------------------------------------------------
| Request shaping
|--------------------------------------------------------------------------
*/

it('sends the user name as a query param and an ETag as If-None-Match', function () {
    Http::fake(['www.habbo.com/api/public/users*' => Http::response(['uniqueId' => 'hhus-1', 'name' => 'Cerbaro'])]);

    app(HabboApi::class)->userByName('Cerbaro', 'W/"abc123"');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'name=Cerbaro')
            && $request->hasHeader('If-None-Match', 'W/"abc123"');
    });
});

it('omits the If-None-Match header when no ETag is given', function () {
    Http::fake(['www.habbo.com/api/public/users*' => Http::response(['uniqueId' => 'hhus-1', 'name' => 'Cerbaro'])]);

    app(HabboApi::class)->userByName('Cerbaro');

    Http::assertSent(fn ($request) => ! $request->hasHeader('If-None-Match'));
});

it('wraps bare marketplace item names into the request shape', function () {
    Http::fake(['www.habbo.com/api/public/marketplace/stats/batch' => Http::response(['status' => 'OK', 'roomItemData' => [], 'wallItemData' => []])]);

    app(HabboApi::class)->marketplaceStats(['chair', ['item' => 'table']], ['poster']);

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && $request['roomItems'] === [['item' => 'chair'], ['item' => 'table']]
            && $request['wallItems'] === [['item' => 'poster']];
    });
});

it('strips a leading api/public segment from get() paths', function () {
    Http::fake(['www.habbo.com/api/public/rooms/5' => Http::response([])]);

    app(HabboApi::class)->get('api/public/rooms/5');
    app(HabboApi::class)->get('/rooms/5');

    Http::assertSentCount(2);
    Http::assertSent(fn ($request) => $request->url() === 'https://www.habbo.com/api/public/rooms/5');
});

/*
|--------------------------------------------------------------------------
| Wired variables
|--------------------------------------------------------------------------
*/

it('attaches the room read key and hydrates a WiredVariableData', function () {
    Http::fake(['www.habbo.com/api/public/rooms/*' => Http::response([
        'value' => 7,
        'creation_time' => '2023-11-16T22:13:20Z',
        'update_time' => '2023-11-17T09:30:00Z',
    ])]);

    $variable = app(HabboApi::class)->roomVariable(42, 'user', 'score', 'users', '7', 'read-key');

    expect($variable)->toBeInstanceOf(WiredVariableData::class)
        ->and($variable->value)->toBe(7)
        ->and($variable->creationTime)->toBeInstanceOf(CarbonImmutable::class);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://www.habbo.com/api/public/rooms/42/variables/user/score/users/7'
            && $request->hasHeader('X-Wired-Read-Key', 'read-key')
            && ! $request->hasHeader('X-Wired-Write-Key');
    });
});

it('attaches the room write key and value body when setting a wired variable', function () {
    Http::fake(['www.habbo.com/api/public/rooms/*' => Http::response(['value' => 7])]);

    app(HabboApi::class)->setRoomVariable(42, 'user', 'score', 'users', '7', 7, 'write-key');

    Http::assertSent(function ($request) {
        return $request->method() === 'PUT'
            && $request->hasHeader('X-Wired-Write-Key', 'write-key')
            && $request['value'] === 7;
    });
});

it('reports a wired variable deletion as successful from a 204', function () {
    Http::fake(['www.habbo.com/api/public/rooms/*' => Http::response('', 204)]);

    expect(app(HabboApi::class)->deleteRoomVariable(42, 'user', 'score', 'users', '7', 'write-key'))->toBeTrue();
});

it('reports a wired variable deletion as failed from a 404', function () {
    Http::fake(['www.habbo.com/api/public/rooms/*' => Http::response('', 404)]);

    expect(app(HabboApi::class)->deleteRoomVariable(42, 'user', 'score', 'users', '7', 'write-key'))->toBeFalse();
});

it('converts in-game entity ids to their API form per target kind', function (string $targetKind, int $inGame, int $expected) {
    expect(HabboApi::apiEntityId($targetKind, $inGame))->toBe($expected);
})->with([
    'plain furni untouched' => ['furni', 12345, 12345],
    'user untouched' => ['users', 500, 500],
    'wall item negated' => ['wall-items', -777, 777],
    'bc furni de-offset' => ['furni-bc', 2147418112 + 42, 42],
    'bc wall item negated then de-offset' => ['wall-items-bc', -(2147418112 + 42), 42],
]);

it('drops null lookup params when reading a user variables profile by name', function () {
    Http::fake(['www.habbo.com/api/public/rooms/*' => Http::response([])]);

    app(HabboApi::class)->userVariablesProfileByName(42, 'read-key', name: 'Cerbaro');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'name=Cerbaro')
            && ! str_contains($request->url(), 'unique_id');
    });
});

/*
|--------------------------------------------------------------------------
| Origins
|--------------------------------------------------------------------------
*/

it('returns Origins match ids as a string list and forwards paging params', function () {
    Http::fake(['www.habbo.com/api/public/matches/v1/*/ids*' => Http::response(['m-1', 'm-2'])]);

    $ids = app(HabboApi::class)->originsMatchIds('dev1-123', ['limit' => 2, 'start_time' => '2024-08-20 12:00:00.000']);

    expect($ids)->toBe(['m-1', 'm-2']);

    Http::assertSent(fn ($request) => str_contains($request->url(), 'matches/v1/dev1-123/ids')
        && str_contains($request->url(), 'limit=2')
        && str_contains($request->url(), 'start_time='));
});

it('hydrates an Origins match with nested metadata, participants and teams', function () {
    Http::fake(['www.habbo.com/api/public/matches/v1/gm-1' => Http::response([
        'metadata' => ['matchId' => 'gm-1', 'participantPlayerIds' => ['dev1-1', 'dev1-2']],
        'info' => [
            'gameCreation' => 1724061623000,
            'gameDuration' => 196000,
            'gameMode' => 'BOUNCER',
            'mapId' => 1,
            'ranked' => true,
            'participants' => [
                ['gamePlayerId' => 'dev1-1', 'gameScore' => 40, 'playerPlacement' => 1, 'tilesColoured' => 12],
            ],
            'teams' => [
                ['teamId' => 1, 'win' => true, 'teamScore' => 40, 'teamPlacement' => 1],
            ],
        ],
    ])]);

    $match = app(HabboApi::class)->originsMatch('gm-1');

    expect($match->metadata->matchId)->toBe('gm-1')
        ->and($match->metadata->participantPlayerIds)->toBe(['dev1-1', 'dev1-2'])
        ->and($match->info->gameMode)->toBe('BOUNCER')
        ->and($match->info->ranked)->toBeTrue()
        ->and($match->info->participants[0]->gameScore)->toBe(40)
        ->and($match->info->participants[0]->tilesColoured)->toBe(12)
        ->and($match->info->teams[0]->win)->toBeTrue();
});

it('returns null for a missing Origins match', function () {
    Http::fake(['www.habbo.com/api/public/matches/v1/*' => Http::response(['error' => 'not-found'], 404)]);

    expect(app(HabboApi::class)->originsMatch('nope'))->toBeNull();
});

it('sends the skillType when reading an Origins skill and hydrates it', function () {
    Http::fake(['www.habbo.com/api/public/skills/dev1-9*' => Http::response(['level' => 5, 'experience' => 1000])]);

    $skill = app(HabboApi::class)->originsSkill('dev1-9');

    expect($skill->level)->toBe(5)->and($skill->experience)->toBe(1000);

    Http::assertSent(fn ($request) => str_contains($request->url(), 'skills/dev1-9?')
        && str_contains($request->url(), 'skillType=FISHING'));
});

it('hydrates an Origins skill leaderboard page', function () {
    Http::fake(['www.habbo.com/api/public/skills/leaderboard*' => Http::response([
        'entries' => [
            ['uniqueId' => 'dev1-1', 'level' => 10, 'experience' => 5000],
            ['uniqueId' => 'dev1-2', 'level' => 9, 'experience' => 4200],
        ],
        'totalPages' => 5,
        'currentPage' => 1,
        'pageSize' => 50,
    ])]);

    $board = app(HabboApi::class)->originsSkillLeaderboard('FISHING', 1);

    expect($board->entries)->toHaveCount(2)
        ->and($board->entries[0]->uniqueId)->toBe('dev1-1')
        ->and($board->entries[1]->experience)->toBe(4200)
        ->and($board->totalPages)->toBe(5);
});

it('maps an Origins player id to Habbo unique ids', function () {
    Http::fake(['www.habbo.com/api/public/users/by-playerId/dev1-1' => Http::response(['hhous-abc'])]);

    expect(app(HabboApi::class)->originsHabboIds('dev1-1'))->toBe(['hhous-abc']);
});

it('returns the fishing derby status as a raw array and forwards the api key', function () {
    Http::fake(['www.habbo.com/api/public/minigame/derby/v1/status*' => Http::response(['state' => 'RUNNING', 'endsAt' => 123])]);

    $status = app(HabboApi::class)->originsDerbyStatus(['api_key' => 'secret']);

    expect($status)->toBe(['state' => 'RUNNING', 'endsAt' => 123]);

    Http::assertSent(fn ($request) => str_contains($request->url(), 'derby/v1/status?')
        && str_contains($request->url(), 'api_key=secret'));
});
