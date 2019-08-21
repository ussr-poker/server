<?php
declare(strict_types=1);

namespace App\Game\Events;

use App\Game\Player;
use App\Game\Round\Round;
use App\Game\Round\RoundResult;
use Symfony\Contracts\EventDispatcher\Event;

class RoundFinishedEvent extends Event
{
    public const NAME = 'game.round_finished';

    private Round $round;

    private Player $winner;

    /**
     * @var RoundResult[]
     */
    private array $roundResults;

    /**
     * RoundFinished constructor.
     * @param Round $round
     * @param Player $winner
     * @param RoundResult[] $roundResults
     */
    public function __construct(Round $round, Player $winner, array $roundResults)
    {
        $this->round = $round;
        $this->winner = $winner;
        $this->roundResults = $roundResults;
    }

    public function getRound(): Round
    {
        return $this->round;
    }

    public function getWinner(): Player
    {
        return $this->winner;
    }

    /**
     * @return RoundResult[]
     */
    public function getRoundResults(): array
    {
        return $this->roundResults;
    }
}
