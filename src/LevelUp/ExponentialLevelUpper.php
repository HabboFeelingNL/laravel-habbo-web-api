<?php

namespace HabboFeeling\HabboWebApi\LevelUp;

/**
 * A geometric curve: the XP cost of each level is `$strength` percent higher than
 * the level before it, starting from `$initialXp`, capped at `$maxLevel`.
 */
final class ExponentialLevelUpper extends LevelUpper
{
    private readonly float $strengthAsDecimal;

    private readonly int $maxXpValue;

    public function __construct(
        private readonly int $initialXp,
        int $strength,
        private readonly int $maxLevel,
    ) {
        $this->strengthAsDecimal = $strength / 100;
        $this->maxXpValue = $this->xpForLevel($this->maxLevel);
    }

    public function currentLevel(int $xp): int
    {
        $bounded = $this->boundedValue($xp);

        if ($bounded <= 0) {
            return 1;
        }

        $logBase = 1 + $this->strengthAsDecimal;
        $level = (int) (floor(log($bounded * $this->strengthAsDecimal / $this->initialXp + 1) / log($logBase)) + 1);

        if ($level > $this->maxLevel) {
            return $this->maxLevel;
        }

        if ($level < 1) {
            return 1;
        }

        if ($bounded < $this->xpForLevel($level)) {
            return $level > 1 ? $level - 1 : 1;
        }

        if ($bounded >= $this->xpForLevel($level + 1)) {
            return $level + 1 > $this->maxLevel ? $this->maxLevel : $level + 1;
        }

        return $level;
    }

    public function totalXpRequired(int $xp): int
    {
        if ($this->isMaxed($xp)) {
            return 0;
        }

        $currentLevel = $this->currentLevel($xp);

        return $this->xpForLevel($currentLevel + 1) - $this->xpForLevel($currentLevel);
    }

    public function progress(int $xp): int
    {
        $bounded = $this->boundedValue($xp);

        if ($this->isMaxed($bounded)) {
            return 0;
        }

        return $bounded - $this->xpForLevel($this->currentLevel($bounded));
    }

    public function progressPercentage(int $xp): int
    {
        $bounded = $this->boundedValue($xp);

        if ($this->isMaxed($bounded)) {
            return 0;
        }

        $currentLevel = $this->currentLevel($bounded);
        $levelXp = $this->xpForLevel($currentLevel);
        $nextLevelXp = $this->xpForLevel($currentLevel + 1);

        if ($levelXp === $nextLevelXp) {
            return 100;
        }

        return (int) floor(($bounded - $levelXp) / ($nextLevelXp - $levelXp) * 100);
    }

    public function xpRemaining(int $xp): int
    {
        $bounded = $this->boundedValue($xp);

        if ($this->isMaxed($bounded)) {
            return 0;
        }

        return $this->xpForLevel($this->currentLevel($bounded) + 1) - $bounded;
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
        return $this->maxXpValue;
    }

    private function xpForLevel(int $level): int
    {
        if ($level < 1) {
            return 0;
        }

        if ($level > $this->maxLevel) {
            return $this->maxXpValue;
        }

        return (int) floor(
            $this->initialXp * (((1 + $this->strengthAsDecimal) ** ($level - 1) - 1 + 1e-9) / $this->strengthAsDecimal)
        );
    }
}
