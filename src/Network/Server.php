<?php
declare(strict_types=1);

namespace App\Network;

use App\Game\Cards\Card;
use App\Game\Events\PlayerMove as PlayerMoveEvent;
use App\Game\Events\PlayerStake as PlayerStakeEvent;
use App\Game\Events\RoundFinished;
use App\Game\Events\RoundStarted;
use App\Game\Events\SubRoundFinished;
use App\Game\Events\SubRoundStarted;
use App\Game\Player;
use App\Game\Room;
use App\Game\Round\RoundResult;
use App\Network\Commands\CommandInterface;
use App\Network\Commands\CreateRoomCommand;
use App\Network\Commands\GetRoomCommand;
use App\Network\Commands\JoinRoomCommand;
use App\Network\Commands\LoginCommand;
use App\Network\Commands\MakeMoveCommand;
use App\Network\Commands\MakeStakeCommand;
use App\Network\Commands\RegisterCommand;
use App\Network\Events\PlayerJoinedEvent;
use App\Network\Events\RoomCreated;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Server
{
    private \Swoole\WebSocket\Server $server;

    /**
     * @var Client[]
     */
    private array $clients = [];

    /**
     * @var Room[]
     */
    private array $rooms = [];

    private EventDispatcher $eventDispatcher;

    public function start(string $host = '127.0.0.1', int $port = 9501): void
    {
        $this->eventDispatcher = new EventDispatcher();
        $this->eventDispatcher->addListener(PlayerJoinedEvent::NAME, [$this, 'onPlayerJoined']);

        $this->eventDispatcher->addListener(RoundStarted::NAME, [$this, 'onRoundStarted']);
        $this->eventDispatcher->addListener(RoundFinished::NAME, [$this, 'onRoundFinished']);
        $this->eventDispatcher->addListener(PlayerStakeEvent::NAME, [$this, 'onPlayerStake']);
        $this->eventDispatcher->addListener(PlayerMoveEvent::NAME, [$this, 'onPlayerMove']);
        $this->eventDispatcher->addListener(SubRoundStarted::NAME, [$this, 'onSubRoundStarted']);
        $this->eventDispatcher->addListener(SubRoundFinished::NAME, [$this, 'onSubRoundFinished']);

//        $this->server = new \Swoole\Server($host, $port, SWOOLE_BASE, SWOOLE_SOCK_TCP);
        $this->server = new \Swoole\WebSocket\Server($host, $port, SWOOLE_BASE, SWOOLE_SOCK_TCP);
        $this->server->set([
            'worker_num' => 1
        ]);

        // Register the function for the event `start`
        $this->server->on('start', [$this, 'onStart']);

        // Register the function for the event `connect`
        $this->server->on('connect', [$this, 'onConnect']);

        // websocket Register the function for the event `open`
        $this->server->on('open', [$this, 'onOpen']);

        // Register the function for the event `receive`
        $this->server->on('receive', [$this, 'onReceive']);

        // websocket Register the function for the event `message`
        $this->server->on('message', [$this, 'onMessage']);

        // Register the function for the event `close`
        $this->server->on('close', [$this, 'onClose']);

        $this->server->start();
    }

    public function onStart(\Swoole\Server $server): void
    {
        \logger()->info('Server started', ['host' => $server->host, 'port' => $server->port]);
    }

    public function onConnect(\Swoole\Server $server, $fd): void
    {
        $this->clients[$fd] = new Client($fd);

        $info = $server->getClientInfo($fd);

        \logger()->info('Client connected', [
            'fd' => $fd,
            'ip' => $info['remote_ip']
        ]);
    }

    public function onOpen(\Swoole\WebSocket\Server $server, \Swoole\Http\Request $request): void
    {
        $fd = $request->fd;
        $info = $server->getClientInfo($fd);

        if (isset($this->clients[$fd])) {
            \logger()->info('Client reconnected', [
                'fd' => $request->fd,
                'ip' => $info['remote_ip']
            ]);
            return;
        }

        $this->clients[$fd] = new Client($fd);

        \logger()->info('Client connected', [
            'fd' => $fd,
            'ip' => $info['remote_ip']
        ]);
    }

    public function onReceive(\Swoole\Server $server, $fd, $from_id, $data): void
    {
        $client = $this->clients[$fd];

        try {
            $payload = \json_decode($data, true, 5, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $server->send($fd, 'wrong_payload');
            return;
        }

        /* @var CommandInterface[] $commands */
        $commands = [
            1 => new RegisterCommand(),
            2 => new LoginCommand($this),
            3 => new CreateRoomCommand($this),
            4 => new JoinRoomCommand($this),
            5 => new MakeStakeCommand($this),
            6 => new MakeMoveCommand($this),
            7 => new GetRoomCommand($this),
        ];

        $commandId = (int)($payload['commandId'] ?? 0);
        $commandPayload = $payload['commandPayload'] ?? null;

        if (!isset($commands[$commandId])) {
            $server->send($fd, 'unknown_command');
            return;
        }

        $command = $commands[$commandId];
        try {
            $commandRes = $command->handle($client, $commandPayload);
            $response = \json_encode(['ok' => true, 'res' => $commandRes]);
        } catch (\Throwable $e) {
            $response = \json_encode([
                'ok' => false,
                'res' => $e->getMessage()
            ]);
        }

        $server->send($fd, $response);
    }

    public function onMessage(\Swoole\WebSocket\Server $server, \Swoole\WebSocket\Frame $frame): void
    {
        $fd = $frame->fd;
        $data = $frame->data;

        $client = $this->clients[$fd];

        try {
            $payload = \json_decode($data, true, 5, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $server->push($fd, 'wrong_payload');
            return;
        }

        /* @var CommandInterface[] $commands */
        $commands = [
            1 => new RegisterCommand(),
            2 => new LoginCommand($this),
            3 => new CreateRoomCommand($this),
            4 => new JoinRoomCommand($this),
            5 => new MakeStakeCommand($this),
            6 => new MakeMoveCommand($this),
            7 => new GetRoomCommand($this),
        ];

        $commandId = (int)($payload['commandId'] ?? 0);
        $commandPayload = $payload['commandPayload'] ?? null;

        if (!isset($commands[$commandId])) {
            $server->push($fd, \json_encode(['ok' => false, 'res' => 'unknown_command']));
            return;
        }

        $command = $commands[$commandId];
        try {
            $commandRes = $command->handle($client, $commandPayload);
            $response = \json_encode([
                'ok' => true,
                'commandId' => $commandId,
                'commandRes' => $commandRes
            ]);
        } catch (\Throwable $e) {
            $response = \json_encode([
                'ok' => false,
                'commandId' => $commandId,
                'commandRes' => $e->getMessage()
            ]);
        }

        $server->push($fd, $response);
    }

    public function onClose($server, $fd): void
    {
        unset($this->clients[$fd]);

        \logger()->info('Client disconnected', [
            'fd' => $fd
        ]);
    }

    public function createRoom(int $players, int $deckSize): Room
    {
        $room = new Room($this->eventDispatcher, $players, $deckSize);
        $this->rooms[$room->getId()] = $room;

        $this->eventDispatcher->dispatch(new RoomCreated($room), RoomCreated::NAME);

        return $room;
    }

    public function getRoom(int $roomId): Room
    {
        if (!isset($this->rooms[$roomId])) {
            throw new \InvalidArgumentException('Room not found');
        }

        return $this->rooms[$roomId];
    }

    /**
     * @return Room[]
     */
    public function getRooms(): array
    {
        return $this->rooms;
    }

    public function onPlayerJoined(PlayerJoinedEvent $event): void
    {
        $player = $event->getPlayer();
        $data = [
            'id' => $player->id,
            'name' => $player->name
        ];

        $this->broadcast($event->getRoom(), 1000, $data, $player->id);
    }

    public function onRoundStarted(RoundStarted $event): void
    {
        $round = $event->getRound();
        $trump = $round->getTrump();
        $room = $round->getGame()->getRoom();

        $data = [
            'number' => $round->getNumber(),
            'cardsCount' => $round->getCardsToPlayer(),
            'type' => $round->getType(),
            'state' => $round->getState(),
            'trump' => [
                'suit' => $trump->getSuit()->getSuit(),
                'value' => $trump->getValue()
            ],
            'awaitedPlayerId' => $round->getPlayerAwaited()->id ?? null
        ];

        $playerSpecificData = [];
        foreach ($room->getPlayers() as $player) {
            $cards = [];

            $playerDeck = $round->getPlayerDeck($player);
            foreach ($playerDeck->getCards() as $cardId => $card) {
                $cards[] = [
                    'id' => $cardId,
                    'suit' => $card->getSuit()->getSuit(),
                    'value' => $card->getValue()
                ];
            }

            $playerSpecificData[$player->id]['cards'] = $cards;
        }

        $this->broadcast($room, 1001, $data, null, $playerSpecificData);
    }

    public function onRoundFinished(RoundFinished $event): void
    {
        $round = $event->getRound();

        $results = \array_map(static function (RoundResult $result) {
            $playerStake = $result->getPlayerStake();

            return [
                'playerId' => $playerStake->getPlayer()->id,
                'stake' => $playerStake->getStake(),
                'wins' => $result->getWins(),
                'score' => $result->getScoreResult()
            ];
        }, $event->getRoundResults());

        $data = [
            'number' => $round->getNumber(),
            'winnerId' => $event->getWinner()->id,
            'results' => $results
        ];

        $this->broadcast($round->getGame()->getRoom(), 1002, $data);
    }

    public function onPlayerStake(PlayerStakeEvent $event): void
    {
        $playerStake = $event->getPlayerStake();

        $data = [
            'playerId' => $playerStake->getPlayer()->id,
            'stake' => $playerStake->getStake(),
            'awaitedPlayerId' => $event->getRound()->getPlayerAwaited()->id ?? null
        ];

        $this->broadcast($event->getRound()->getGame()->getRoom(), 1003, $data);
    }

    public function onPlayerMove(PlayerMoveEvent $event): void
    {
        $playerMove = $event->getPlayerMove();
        $card = $playerMove->getCard();

        $formattedJokerMove = null;
        $jokerMove = $playerMove->getJokerMove();
        if (null !== $jokerMove) {
            $formattedJokerMove = [
                'mode' => $jokerMove->getMode(),
                'suit' => $jokerMove->getSuit()->getSuit(),
            ];
        }

        $data = [
            'playerId' => $playerMove->getPlayer()->id,
            'card' => [
                'id' => $event->getCardId(),
                'suit' => $card->getSuit()->getSuit(),
                'value' => $card->getValue()
            ],
            'jokerMove' => $formattedJokerMove,
            'awaitedPlayerId' => $event->getSubRound()->getRound()->getPlayerAwaited()->id ?? null
        ];

        $this->broadcast($event->getSubRound()->getRound()->getGame()->getRoom(), 1004, $data);
    }

    public function onSubRoundStarted(SubRoundStarted $event): void
    {
        $subRound = $event->getSubRound();
        $round = $subRound->getRound();

        $data = [
            'number' => $subRound->getNumber(),
            'awaitedPlayerId' => $subRound->getRound()->getPlayerAwaited()->id ?? null
        ];

        if (null === $data['awaitedPlayerId']) {
            \logger()->info('awaitedPlayerId is null', [
                'round' => [
                    'number' => $round->getNumber(),
                    'state' => $round->getState(),
                    'stakesOrder' => \array_map(fn(Player $player, $key) => [$key, $player->name], $round->getStakesOrder(), \array_keys($round->getStakesOrder())),
                ],
                'subround' => [
                    'number' => $subRound->getNumber(),
                    'movesCount' => $subRound->getMovesCount(),
                    'playersOrder' => \array_map(fn(Player $player, $key) => [$key, $player->name], $subRound->getPlayersOrder(), array_keys($subRound->getPlayersOrder())),
                ]
            ]);
        }

        $this->broadcast($subRound->getRound()->getGame()->getRoom(), 1005, $data);
    }

    public function onSubRoundFinished(SubRoundFinished $event): void
    {
        $subRound = $event->getSubRound();

        $data = [
            'winnerId' => $event->getWinner()->id,
            'number' => $subRound->getNumber()
        ];

        $this->broadcast($subRound->getRound()->getGame()->getRoom(), 1006, $data);
    }

    private function broadcast(
        Room $room,
        int $commandId,
        $data,
        ?int $exceptPlayerId = null,
        array $playerSpecificData = []
    ): void {
        foreach ($room->getPlayers() as $player) {
            if (null !== $exceptPlayerId && $player->id === $exceptPlayerId) {
                continue;
            }

            $client = $player->getClient();
            if ($this->disconnectBadClient($client->getFd())) {
                continue;
            }

            \logger()->info('Sending notification to player', [
                'fd' => $client->getFd(),
                'id' => $client->getUser()->id,
                'name' => $client->getUser()->name
            ]);

            try {
                $this->server->push($client->getFd(), \json_encode([
                    'ok' => true,
                    'commandId' => $commandId,
                    'commandRes' => $data + ($playerSpecificData[$player->id] ?? [])
                ]));
            } catch (\Throwable $e) {
                \logger()->info('Unable to send notification to player', [
                    'reason' => $e->getMessage(),
                    'fd' => $client->getFd(),
                    'id' => $client->getUser()->id,
                    'name' => $client->getUser()->name
                ]);
            }
        }
    }

    private function disconnectBadClient(int $fd): bool
    {
        if (!$this->server->isEstablished($fd)) {
            \logger()->info('Disconnected client', [
                'fd' => $fd,
                'id' => $this->clients[$fd]->getUser(),
                'name' => $this->clients[$fd]->getUser()->name,
            ]);

            unset($this->clients[$fd]);
            return true;
        }

        return false;
    }
}