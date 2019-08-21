<?php
declare(strict_types=1);

namespace App\Game\Events;

use App\Game\Game;
use Symfony\Contracts\EventDispatcher\Event;

class GameFinishedEvent extends Event
{
    public const NAME = 'game.finished';

    private Game $game;

    public function __construct(Game $game)
    {
        $this->game = $game;
    }

    public function getGame(): Game
    {
        return $this->game;
    }
}
