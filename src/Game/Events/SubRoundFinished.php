<?php
declare(strict_types=1);

namespace App\Game\Events;

use App\Game\Player;
use App\Game\Round\SubRound;
use Symfony\Contracts\EventDispatcher\Event;

class SubRoundFinished extends Event
{
    public const NAME = 'game.sub_round_finished';

    private SubRound $subRound;

    private Player $winner;

    public function __construct(SubRound $round, Player $winner)
    {
        $this->subRound = $round;
        $this->winner = $winner;
    }

    public function getSubRound(): SubRound
    {
        return $this->subRound;
    }

    public function getWinner(): Player
    {
        return $this->winner;
    }
}