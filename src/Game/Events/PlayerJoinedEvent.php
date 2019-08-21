<?php
declare(strict_types=1);

namespace App\Game\Events;

use App\Game\Player;
use App\Game\Room;
use Symfony\Contracts\EventDispatcher\Event;

class PlayerJoinedEvent extends Event
{
    public const NAME = 'room.player_joined';

    private Room $room;
    private Player $player;

    public function __construct(Room $room, Player $player)
    {
        $this->room = $room;
        $this->player = $player;
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
