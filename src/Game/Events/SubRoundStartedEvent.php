<?php
declare(strict_types=1);

namespace App\Game\Events;

use App\Game\Player;
use App\Game\Round\SubRound;
use Symfony\Contracts\EventDispatcher\Event;

class SubRoundStartedEvent extends Event
{
    public const NAME = 'game.sub_round_started';

    private SubRound $subRound;

    private Player $firstPlayer;

    public function __construct(SubRound $round, Player $firstPlayer)
    {
        $this->subRound = $round;
        $this->firstPlayer = $firstPlayer;
    }

    public function getSubRound(): SubRound
    {
        return $this->subRound;
    }

    public function getFirstPlayer(): Player
    {
        return $this->firstPlayer;
    }
}
