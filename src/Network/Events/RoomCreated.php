<?php
declare(strict_types=1);

namespace App\Network\Events;

use App\Game\Room;
use Symfony\Contracts\EventDispatcher\Event;

class RoomCreated extends Event
{
    public const NAME = 'server.room_created';

    private Room $room;

    public function __construct(Room $room)
    {
        $this->room = $room;
    }

    public function getRoom(): Room
    {
        return $this->room;
    }
}