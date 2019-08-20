<?php
declare(strict_types=1);

namespace App\Game\Round;

use App\Game\Player;

class PlayerStake
{
    private Player $player;

    private int $stake;

    public function __construct(Player $player, int $stake)
    {
        $this->player = $player;
        $this->stake = $stake;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getStake(): int
    {
        return $this->stake;
    }
}