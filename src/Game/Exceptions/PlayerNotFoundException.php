<?php
declare(strict_types=1);

namespace App\Game\Exceptions;

class PlayerNotFoundException extends \LogicException
{
    private int $playerId;

    public function __construct(int $playerId)
    {
        $this->playerId = $playerId;

        parent::__construct('Player not found');
    }

    public function getPlayerId(): int
    {
        return $this->playerId;
    }
}
