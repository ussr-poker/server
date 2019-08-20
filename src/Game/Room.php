<?php
declare(strict_types=1);

namespace App\Game;

use App\Network\Events\PlayerJoinedEvent;
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

    public function joinPlayer(Player $player)
    {
        if ($this->playersNeeded === \count($this->players)) {
            throw new \LogicException('Room is full');
        }

        $playerIsInRoom = false;
        foreach ($this->players as $roomPlayer) {
            if ($roomPlayer->id === $player->id) {
                $roomPlayer->setClient($player->getClient());
                $playerIsInRoom = true;
                break;
            }
        }
        if (!$playerIsInRoom) {
            $this->players[] = $player;
        }

        \logger()->info('Room player joined', ['roomId' => $this->id, 'playerId' => $player->id]);
        $this->serverEventDispatcher->dispatch(new PlayerJoinedEvent($this, $player), PlayerJoinedEvent::NAME);

        if ($this->playersNeeded === \count($this->players)) {
            $this->game->startGame();
        }
    }

    public function onPlayerDisconnect(Player $disconnectedPlayer): void
    {

    }

    public function getPlayerById(int $id): Player
    {
        foreach ($this->players as $player) {
            if ($player->id === $id) {
                return $player;
            }
        }

        throw new \LogicException('Player not found');
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