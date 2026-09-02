<?php

use HabboFeeling\HabboWebApi\LevelUp\ExponentialLevelUpper;
use HabboFeeling\HabboWebApi\LevelUp\InterpolateLevelUpper;
use HabboFeeling\HabboWebApi\LevelUp\LevelUpper;
use HabboFeeling\HabboWebApi\LevelUp\LinearLevelUpper;

/*
|--------------------------------------------------------------------------
| Level-up add-on calculator
|--------------------------------------------------------------------------
| Expectations are taken from running the API maintainers' reference
| `level-upper.ts` directly, except LinearLevelUpper::maxXp(), which applies
| the maintainer's stated `(maxLevel - 1) * stepSize` correction.
*/

function assertLevelRow(LevelUpper $upper, int $xp, array $expected): void
{
    expect([
        'level' => $upper->currentLevel($xp),
        'progress' => $upper->progress($xp),
        'pct' => $upper->progressPercentage($xp),
        'remaining' => $upper->xpRemaining($xp),
        'total' => $upper->totalXpRequired($xp),
        'maxed' => $upper->isMaxed($xp),
    ])->toBe($expected);
}

it('exposes named constructors returning the right strategy', function () {
    expect(LevelUpper::linear(100, 10))->toBeInstanceOf(LinearLevelUpper::class)
        ->and(LevelUpper::interpolate([1 => 0, 10 => 100]))->toBeInstanceOf(InterpolateLevelUpper::class)
        ->and(LevelUpper::exponential(100, 50, 10))->toBeInstanceOf(ExponentialLevelUpper::class);
});

it('clamps out-of-range xp to the curve bounds', function () {
    $upper = LevelUpper::linear(100, 10);

    expect($upper->boundedValue(-1))->toBe(0)
        ->and($upper->boundedValue(10_000))->toBe($upper->maxXp());
});

/*
| Linear — stepSize 100, maxLevel 10
*/

it('walks a linear curve', function (int $xp, array $expected) {
    assertLevelRow(LevelUpper::linear(100, 10), $xp, $expected);
})->with([
    'below zero' => [-50, ['level' => 1, 'progress' => 0, 'pct' => 0, 'remaining' => 100, 'total' => 100, 'maxed' => false]],
    'zero' => [0, ['level' => 1, 'progress' => 0, 'pct' => 0, 'remaining' => 100, 'total' => 100, 'maxed' => false]],
    'mid level 1' => [50, ['level' => 1, 'progress' => 50, 'pct' => 50, 'remaining' => 50, 'total' => 100, 'maxed' => false]],
    'exact level 2' => [100, ['level' => 2, 'progress' => 0, 'pct' => 0, 'remaining' => 100, 'total' => 100, 'maxed' => false]],
    'mid level 2' => [150, ['level' => 2, 'progress' => 50, 'pct' => 50, 'remaining' => 50, 'total' => 100, 'maxed' => false]],
    'just before max' => [899, ['level' => 9, 'progress' => 99, 'pct' => 99, 'remaining' => 1, 'total' => 100, 'maxed' => false]],
    'at max' => [900, ['level' => 10, 'progress' => 0, 'pct' => 0, 'remaining' => 0, 'total' => 0, 'maxed' => true]],
    'past max' => [950, ['level' => 10, 'progress' => 0, 'pct' => 0, 'remaining' => 0, 'total' => 0, 'maxed' => true]],
    'far past max' => [5000, ['level' => 10, 'progress' => 0, 'pct' => 0, 'remaining' => 0, 'total' => 0, 'maxed' => true]],
]);

it('reports the linear ceiling with the maintainer maxXp correction', function () {
    $upper = LevelUpper::linear(100, 10);

    expect($upper->maxLevel())->toBe(10)
        ->and($upper->maxXp())->toBe(900); // reference snippet ships 1000; corrected to (10 - 1) * 100
});

/*
| Interpolate — {1: 0, 5: 1000, 10: 5000}
*/

it('walks an interpolated curve', function (int $xp, array $expected) {
    assertLevelRow(LevelUpper::interpolate([1 => 0, 5 => 1000, 10 => 5000]), $xp, $expected);
})->with([
    'below zero' => [-10, ['level' => 1, 'progress' => 0, 'pct' => 0, 'remaining' => 250, 'total' => 250, 'maxed' => false]],
    'zero' => [0, ['level' => 1, 'progress' => 0, 'pct' => 0, 'remaining' => 250, 'total' => 250, 'maxed' => false]],
    'first segment' => [250, ['level' => 2, 'progress' => 0, 'pct' => 0, 'remaining' => 250, 'total' => 250, 'maxed' => false]],
    'first segment deeper' => [500, ['level' => 3, 'progress' => 0, 'pct' => 0, 'remaining' => 250, 'total' => 250, 'maxed' => false]],
    'waypoint' => [1000, ['level' => 5, 'progress' => 0, 'pct' => 0, 'remaining' => 800, 'total' => 800, 'maxed' => false]],
    'second segment' => [2000, ['level' => 6, 'progress' => 200, 'pct' => 25, 'remaining' => 600, 'total' => 800, 'maxed' => false]],
    'second segment deeper' => [3000, ['level' => 7, 'progress' => 400, 'pct' => 50, 'remaining' => 400, 'total' => 800, 'maxed' => false]],
    'at max' => [5000, ['level' => 10, 'progress' => 0, 'pct' => 0, 'remaining' => 0, 'total' => 0, 'maxed' => true]],
    'past max' => [9999, ['level' => 10, 'progress' => 0, 'pct' => 0, 'remaining' => 0, 'total' => 0, 'maxed' => true]],
]);

it('reports the interpolated ceiling from the last waypoint', function () {
    $upper = LevelUpper::interpolate([1 => 0, 5 => 1000, 10 => 5000]);

    expect($upper->maxLevel())->toBe(10)
        ->and($upper->maxXp())->toBe(5000);
});

it('treats an empty interpolation map as already maxed at level 1', function () {
    $upper = LevelUpper::interpolate([]);

    expect($upper->maxXp())->toBe(0)
        ->and($upper->currentLevel(100))->toBe(1)
        ->and($upper->isMaxed(0))->toBeTrue();
});

/*
| Exponential — initialXp 100, strength 50, maxLevel 10
*/

it('walks an exponential curve', function (int $xp, array $expected) {
    assertLevelRow(LevelUpper::exponential(100, 50, 10), $xp, $expected);
})->with([
    'below zero' => [-5, ['level' => 1, 'progress' => 0, 'pct' => 0, 'remaining' => 100, 'total' => 100, 'maxed' => false]],
    'zero' => [0, ['level' => 1, 'progress' => 0, 'pct' => 0, 'remaining' => 100, 'total' => 100, 'maxed' => false]],
    'mid level 1' => [50, ['level' => 1, 'progress' => 50, 'pct' => 50, 'remaining' => 50, 'total' => 100, 'maxed' => false]],
    'exact level 2' => [100, ['level' => 2, 'progress' => 0, 'pct' => 0, 'remaining' => 150, 'total' => 150, 'maxed' => false]],
    'exact level 3' => [250, ['level' => 3, 'progress' => 0, 'pct' => 0, 'remaining' => 225, 'total' => 225, 'maxed' => false]],
    'mid curve' => [1000, ['level' => 5, 'progress' => 188, 'pct' => 37, 'remaining' => 318, 'total' => 506, 'maxed' => false]],
    'high curve' => [5000, ['level' => 9, 'progress' => 75, 'pct' => 2, 'remaining' => 2488, 'total' => 2563, 'maxed' => false]],
    'near ceiling' => [7000, ['level' => 9, 'progress' => 2075, 'pct' => 80, 'remaining' => 488, 'total' => 2563, 'maxed' => false]],
    'past ceiling' => [100000, ['level' => 10, 'progress' => 0, 'pct' => 0, 'remaining' => 0, 'total' => 0, 'maxed' => true]],
]);

it('reports the exponential ceiling from xpForLevel(maxLevel)', function () {
    $upper = LevelUpper::exponential(100, 50, 10);

    expect($upper->maxLevel())->toBe(10)
        ->and($upper->maxXp())->toBe(7488);
});
