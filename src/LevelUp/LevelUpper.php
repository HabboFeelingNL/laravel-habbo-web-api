<?php

namespace HabboFeeling\HabboWebApi\LevelUp;

/**
 * XP → level calculator for the Habbo "level up" add-on.
 *
 * A 1:1 port of the API maintainers' reference `level-upper.ts`. The add-on
 * stores an XP total per entity plus a level curve; pick the matching strategy
 * with {@see self::linear()}, {@see self::interpolate()} or
 * {@see self::exponential()} and query it with a raw XP value.
 *
 * Values are plain PHP `int`: the add-on's numbers are signed 64-bit integers,
 * which a 64-bit PHP build represents exactly.
 */
abstract class LevelUpper
{
    /**
     * Clamp an XP value into the `0 .. maxXp()` range the curve is defined for.
     */
    public function boundedValue(int $xp): int
    {
        if ($xp < 0) {
            return 0;
        }

        $maxXp = $this->maxXp();

        return $xp > $maxXp ? $maxXp : $xp;
    }

    /**
     * The level a given XP total corresponds to (1-based).
     */
    abstract public function currentLevel(int $xp): int;

    /**
     * XP needed to get from the current level to the next one. `0` when maxed.
     */
    abstract public function totalXpRequired(int $xp): int;

    /**
     * XP earned into the current level. `0` when maxed.
     */
    abstract public function progress(int $xp): int;

    /**
     * Progress into the current level as a whole-number percentage (0-100).
     */
    abstract public function progressPercentage(int $xp): int;

    /**
     * XP still needed to reach the next level. `0` when maxed.
     */
    abstract public function xpRemaining(int $xp): int;

    /**
     * Whether this XP total is at (or past) the maximum level.
     */
    abstract public function isMaxed(int $xp): bool;

    /**
     * The highest reachable level.
     */
    abstract public function maxLevel(): int;

    /**
     * The XP total at which the maximum level is reached.
     */
    abstract public function maxXp(): int;

    /**
     * A fixed amount of XP per level, up to a maximum level.
     */
    public static function linear(int $stepSize, int $maxLevel): self
    {
        return new LinearLevelUpper($stepSize, $maxLevel);
    }

    /**
     * A curve given as explicit `level => cumulative XP` waypoints, linearly
     * interpolated between them.
     *
     * @param  array<int, int>  $levelToXp
     */
    public static function interpolate(array $levelToXp): self
    {
        return new InterpolateLevelUpper($levelToXp);
    }

    /**
     * A geometric curve: each level costs `strength`% more XP than the last.
     */
    public static function exponential(int $initialXp, int $strength, int $maxLevel): self
    {
        return new ExponentialLevelUpper($initialXp, $strength, $maxLevel);
    }
}
