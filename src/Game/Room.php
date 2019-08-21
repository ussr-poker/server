<?php
declare(strict_types=1);

namespace App\Game;

use App\Game\Exceptions\PlayerAlreadyInRoomException;
use App\Game\Exceptions\PlayerNotFoundException;
use App\Game\Exceptions\RoomIsFullException;
use App\Game\Events\PlayerJoinedEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Room
{
    public static int $roomsCount = 0;

    private int $id;

    private int $playersNeeded;

    /**
     * @var Player[]
     */
    private array $players = [];

    private Game $game;

    private EventDispatcher $serverEventDispatcher;

    public function __construct(EventDispatcher $serverEventDispatcher, int $playersNeeded, int $deckSize)
    {
        $this->playersNeeded = $playersNeeded;
        $this->game = new Game($serverEventDispatcher, $this, $deckSize, Game::MODE_SHORT);
        $this->serverEventDispatcher = $serverEventDispatcher;

        static::$roomsCount++;
        $this->id = static::$roomsCount;
    }

    public function joinPlayer(Player $player): void
    {
        try {
            $this->getPlayerById($player->id);

            throw new PlayerAlreadyInRoomException($this, $player);
        } catch (PlayerNotFoundException $e) {
        }

        if ($this->playersNeeded === \count($this->players)) {
            throw new RoomIsFullException($this);
        }

        $this->players[] = $player;

        \logger()->info('Room player joined', ['roomId' => $this->id, 'playerId' => $player->id]);
        $this->serverEventDispatcher->dispatch(new PlayerJoinedEvent($this, $player), PlayerJoinedEvent::NAME);

        if ($this->playersNeeded === \count($this->players)) {
            $this->game->startGame();
        }
    }

    public function getPlayerById(int $id): Player
    {
        foreach ($this->players as $player) {
            if ($player->id === $id) {
                return $player;
            }
        }

        throw new PlayerNotFoundException($id);
    }

    /**
     * @return Player[]
     */
    public function getPlayers(): array
    {
        return $this->players;
    }

    public function getPlayersNeeded(): int
    {
        return $this->playersNeeded;
    }

    public function getGame(): Game
    {
        return $this->game;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getServerEventDispatcher(): EventDispatcher
    {
        return $this->serverEventDispatcher;
    }
}
