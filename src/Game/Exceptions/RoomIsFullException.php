<?php
declare(strict_types=1);

namespace App\Game\Exceptions;

use App\Game\Room;

class RoomIsFullException extends \LogicException
{
    private Room $room;

    public function __construct(Room $room)
    {
        $this->room = $room;

        parent::__construct('Room is full');
    }

    public function getRoom(): Room
    {
        return $this->room;
    }
}
