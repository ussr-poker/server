<?php
declare(strict_types=1);

namespace App\Game\Exceptions;

use App\Game\Game;

class GameIsNotReadyException extends \LogicException
{
    private Game $game;

    public function __construct(Game $game)
    {
        $this->game = $game;

        parent::__construct('Game is not started or finished');
    }

    public function getGame(): Game
    {
        return $this->game;
    }
}
