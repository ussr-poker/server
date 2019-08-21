<?php
declare(strict_types=1);

namespace App\Game\Events;

use App\Game\Player;
use App\Game\Round\Round;
use Symfony\Contracts\EventDispatcher\Event;

class RoundStartedEvent extends Event
{
    public const NAME = 'game.round_started';

    private Round $round;

    private Player $firstPlayer;

    public function __construct(Round $round, Player $firstPlayer)
    {
        $this->round = $round;
        $this->firstPlayer = $firstPlayer;
    }

    public function getRound(): Round
    {
        return $this->round;
    }

    public function getFirstPlayer(): Player
    {
        return $this->firstPlayer;
    }
}
