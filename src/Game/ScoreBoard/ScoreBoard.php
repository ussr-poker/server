<?php
declare(strict_types=1);

namespace App\Game\ScoreBoard;

use App\Game\Round\PlayerStake;
use App\Game\Round\Round;
use App\Game\Round\RoundResult;

class ScoreBoard
{
    /**
     * @var ScoreBoardEntry[]
     */
    private array $history = [];

    public function addEntry(Round $round): void
    {
        $this->history[] = new ScoreBoardEntry($round);
    }

    public function addPlayerStake(Round $round, PlayerStake $playerStake): void
    {
        $entry = $this->findEntry($round);
        $entry->setPlayerStake($playerStake);
    }

    public function addRoundResult(Round $round, RoundResult $roundResult): void
    {
        $entry = $this->findEntry($round);
        $entry->setPlayerResults($roundResult);
    }

    private function findEntry(Round $round): ScoreBoardEntry
    {
        foreach ($this->history as $history) {
            if ($history->getRound()->getNumber() === $round->getNumber()) {
                return $history;
            }
        }

        throw new \LogicException('ScoreBoardEntry not found');
    }

    /**
     * @return ScoreBoardEntry[]
     */
    public function getScores(): array
    {
        return $this->history;
    }
}