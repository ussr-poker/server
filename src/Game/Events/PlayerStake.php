<?php
declare(strict_types=1);

namespace App\Game\Events;

use App\Game\Round\PlayerStake as PlayerStakeObject;
use App\Game\Round\Round;
use Symfony\Contracts\EventDispatcher\Event;

class PlayerStake extends Event
{
    public const NAME = 'game.player_stake';

    private Round $round;

    private PlayerStakeObject $playerStake;

    public function __construct(Round $round, PlayerStakeObject $playerStake)
    {
        $this->round = $round;
        $this->playerStake = $playerStake;
    }

    public function getRound(): Round
    {
        return $this->round;
    }

    public function getPlayerStake(): PlayerStakeObject
    {
        return $this->playerStake;
    }
}