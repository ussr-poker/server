<?php
declare(strict_types=1);

namespace App\Game\ScoreBoard;

use App\Game\Player;

class ScoreBoardPlayer
{
    private Player $player;

    private ?int $stake = null;

    private ?int $wins = null;

    private ?int $score = null;

    public function __construct(Player $player)
    {
        $this->player = $player;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getStake(): ?int
    {
        return $this->stake;
    }

    public function setStake(int $stake): void
    {
        $this->stake = $stake;
    }

    public function getWins(): ?int
    {
        return $this->wins;
    }

    public function setWins(int $wins): void
    {
        $this->wins = $wins;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(int $score): void
    {
        $this->score = $score;
    }
}