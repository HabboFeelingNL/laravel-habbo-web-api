<?php

namespace HabboFeeling\HabboWebApi\LevelUp;

/**
 * A curve defined by explicit `level => cumulative XP` waypoints. Levels between
 * two waypoints are spread out evenly (linear interpolation).
 */
final class InterpolateLevelUpper extends LevelUpper
{
    /** @var list<array{level: int, xp: int}> waypoints, ascending by xp */
    private array $xpToLevel;

    /**
     * @param  array<int, int>  $levelToXp  level number => cumulative XP at that level
     */
    public function __construct(array $levelToXp)
    {
        $pairs = [];

        foreach ($levelToXp as $level => $xp) {
            $pairs[] = ['level' => (int) $level, 'xp' => $xp];
        }

        usort($pairs, fn (array $a, array $b): int => $a['xp'] <=> $b['xp']);

        $this->xpToLevel = $pairs;
    }

    public function currentLevel(int $xp): int
    {
        return $this->findProgressInfo($xp)['currentLevel'];
    }

    public function totalXpRequired(int $xp): int
    {
        $info = $this->findProgressInfo($xp);

        return $info['nextLevelXp'] - $info['currentLevelXp'];
    }

    public function progress(int $xp): int
    {
        $info = $this->findProgressInfo($xp);

        return $info['currentXp'] - $info['currentLevelXp'];
    }

    public function progressPercentage(int $xp): int
    {
        $info = $this->findProgressInfo($xp);
        $totalRequired = $info['nextLevelXp'] - $info['currentLevelXp'];

        if ($totalRequired === 0) {
            return 0;
        }

        return (int) floor(($info['currentXp'] - $info['currentLevelXp']) / $totalRequired * 100);
    }

    public function xpRemaining(int $xp): int
    {
        $info = $this->findProgressInfo($xp);

        return $info['nextLevelXp'] - $info['currentXp'];
    }

    public function isMaxed(int $xp): bool
    {
        return $this->findProgressInfo($xp)['isMaxed'];
    }

    public function maxLevel(): int
    {
        return $this->findProgressInfo($this->maxXp())['currentLevel'];
    }

    public function maxXp(): int
    {
        if ($this->xpToLevel === []) {
            return 0;
        }

        return $this->xpToLevel[count($this->xpToLevel) - 1]['xp'];
    }

    /**
     * @return array{currentLevel: int, currentLevelXp: int, currentXp: int, nextLevelXp: int, isMaxed: bool}
     */
    private function findProgressInfo(int $xp): array
    {
        if ($this->xpToLevel === []) {
            return ['currentLevel' => 1, 'currentLevelXp' => 0, 'currentXp' => 0, 'nextLevelXp' => 0, 'isMaxed' => true];
        }

        $bounded = $this->boundedValue($xp);
        $last = $this->xpToLevel[count($this->xpToLevel) - 1];

        if ($bounded >= $last['xp']) {
            return [
                'currentLevel' => $last['level'],
                'currentLevelXp' => $last['xp'],
                'currentXp' => $last['xp'],
                'nextLevelXp' => $last['xp'],
                'isMaxed' => true,
            ];
        }

        $floor = ['level' => 1, 'xp' => 0];
        $ceil = $this->xpToLevel[0];

        foreach ($this->xpToLevel as $entry) {
            if ($entry['xp'] <= $bounded) {
                $floor = $entry;

                continue;
            }

            $ceil = $entry;
            break;
        }

        $levelDifference = $ceil['level'] - $floor['level'];
        $xpDifference = $ceil['xp'] - $floor['xp'];
        $xpPerLevel = $xpDifference / $levelDifference;
        $interpolationProgress = $bounded - $floor['xp'];

        $levelSteps = (int) min(
            max((int) floor($interpolationProgress / $xpPerLevel), 0),
            $levelDifference - 1,
        );

        $currentLevel = $floor['level'] + $levelSteps;
        $currentLevelXp = $floor['xp'] + (int) floor($xpPerLevel * $levelSteps);

        if ($levelSteps === $levelDifference - 1) {
            $nextLevelXp = $ceil['xp'];
        } else {
            $nextLevelXp = $floor['xp'] + (int) floor($xpPerLevel * ($levelSteps + 1));

            if ($bounded >= $nextLevelXp) {
                $levelSteps++;
                $currentLevel = $floor['level'] + $levelSteps;
                $currentLevelXp = $floor['xp'] + (int) floor($xpPerLevel * $levelSteps);
                $nextLevelXp = $levelSteps === $levelDifference
                    ? $ceil['xp']
                    : $floor['xp'] + (int) floor($xpPerLevel * ($levelSteps + 1));
            }
        }

        return [
            'currentLevel' => $currentLevel,
            'currentLevelXp' => $currentLevelXp,
            'currentXp' => $bounded,
            'nextLevelXp' => $nextLevelXp,
            'isMaxed' => false,
        ];
    }
}
