<?php
declare(strict_types=1);

namespace App\Game\Events;

use App\Game\Round\PlayerMove as PlayerMoveObject;
use App\Game\Round\SubRound;
use Symfony\Contracts\EventDispatcher\Event;

class PlayerMove extends Event
{
    public const NAME = 'game.player_move';

    private SubRound $subRound;

    private PlayerMoveObject $playerMove;

    private int $cardId;

    public function __construct(SubRound $subRound, PlayerMoveObject $playerMove, int $cardId)
    {
        $this->subRound = $subRound;
        $this->playerMove = $playerMove;
        $this->cardId = $cardId;
    }

    public function getSubRound(): SubRound
    {
        return $this->subRound;
    }

    public function getPlayerMove(): PlayerMoveObject
    {
        return $this->playerMove;
    }

    public function getCardId(): int
    {
        return $this->cardId;
    }
}