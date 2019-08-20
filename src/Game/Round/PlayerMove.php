<?php
declare(strict_types=1);

namespace App\Game\Round;

use App\Game\Cards\Card;
use App\Game\Player;

class PlayerMove
{
    private Player $player;

    private Card $card;

    private ?JokerMove $jokerMove;

    public function __construct(Player $player, Card $card, ?JokerMove $jokerMove = null)
    {
        $this->player = $player;
        $this->card = $card;
        $this->jokerMove = $jokerMove;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getCard(): Card
    {
        return $this->card;
    }

    public function getJokerMove(): ?JokerMove
    {
        return $this->jokerMove;
    }
}