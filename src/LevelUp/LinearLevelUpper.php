<?php

namespace HabboFeeling\HabboWebApi\LevelUp;

/**
 * A flat curve: every level costs the same `$stepSize` XP, capped at `$maxLevel`.
 */
final class LinearLevelUpper extends LevelUpper
{
    public function __construct(
        private readonly int $stepSize,
        private readonly int $maxLevel,
    ) {}

    public function currentLevel(int $xp): int
    {
        return min($this->maxLevel, 1 + intdiv($this->boundedValue($xp), $this->stepSize));
    }

    public function totalXpRequired(int $xp): int
    {
        return $this->isMaxed($xp) ? 0 : $this->stepSize;
    }

    public function progress(int $xp): int
    {
        return $this->isMaxed($xp) ? 0 : $this->boundedValue($xp) % $this->stepSize;
    }

    public function progressPercentage(int $xp): int
    {
        if ($this->isMaxed($xp)) {
            return 0;
        }

        return (int) floor($this->progress($xp) / $this->stepSize * 100);
    }

    public function xpRemaining(int $xp): int
    {
        if ($this->isMaxed($xp)) {
            return 0;
        }

        return $this->stepSize - ($this->boundedValue($xp) % $this->stepSize);
    }

    public function isMaxed(int $xp): bool
    {
        return $this->currentLevel($xp) >= $this->maxLevel;
    }

    public function maxLevel(): int
    {
        return $this->maxLevel;
    }

    public function maxXp(): int
    {
        // The reference `level-upper.ts` ships `maximumLevel * stepSize` here; the
        // API maintainer corrected it to `(maximumLevel - 1) * stepSize` (the XP
        // total at which the last level begins).
        return ($this->maxLevel - 1) * $this->stepSize;
    }
}
