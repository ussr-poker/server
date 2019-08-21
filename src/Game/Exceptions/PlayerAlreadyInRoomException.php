<?php
declare(strict_types=1);

namespace App\Game\Exceptions;

use App\Game\Player;
use App\Game\Room;

class PlayerAlreadyInRoomException extends \LogicException
{
    private Room $room;

    private Player $player;

    public function __construct(Room $room, Player $player)
    {
        $this->room = $room;
        $this->player = $player;

        parent::__construct('Player is already in the room');
    }

    public function getRoom(): Room
    {
        return $this->room;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }
}
