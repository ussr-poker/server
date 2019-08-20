<?php
declare(strict_types=1);

namespace App\Game\Round;

class RoundResult
{
    private PlayerStake $playerStake;

    private int $wins;

    private int $scoreResult;

    public function __construct(PlayerStake $playerStake, int $wins, int $scoreResult)
    {
        $this->playerStake = $playerStake;
        $this->wins = $wins;
        $this->scoreResult = $scoreResult;
    }

    public function getPlayerStake(): PlayerStake
    {
        return $this->playerStake;
    }

    public function getWins(): int
    {
        return $this->wins;
    }

    public function getScoreResult(): int
    {
        return $this->scoreResult;
    }
}