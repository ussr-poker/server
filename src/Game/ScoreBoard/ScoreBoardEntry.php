<?php
declare(strict_types=1);

namespace App\Game\ScoreBoard;

use App\Game\Round\PlayerStake;
use App\Game\Round\Round;
use App\Game\Round\RoundResult;

class ScoreBoardEntry
{
    private Round $round;

    /**
     * @var ScoreBoardPlayer[]
     */
    private array $players = [];

    public function __construct(Round $round)
    {
        $this->round = $round;
        $this->players = [];

        foreach ($round->getGame()->getRoom()->getPlayers() as $player) {
            $this->players[] = new ScoreBoardPlayer($player);
        }
    }

    public function setPlayerStake(PlayerStake $playerStake): void
    {
        foreach ($this->players as $player) {
            if ($player->getPlayer()->id === $playerStake->getPlayer()->id) {
                $player->setStake($playerStake->getStake());
            }
        }
    }

    public function setPlayerResults(RoundResult $result): void
    {
        foreach ($this->players as $player) {
            if ($player->getPlayer()->id === $result->getPlayerStake()->getPlayer()->id) {
                $player->setWins($result->getWins());
                $player->setScore($result->getScoreResult());
            }
        }
    }

    public function getRound(): Round
    {
        return $this->round;
    }

    /**
     * @return ScoreBoardPlayer[]
     */
    public function getPlayers(): array
    {
        return $this->players;
    }
}