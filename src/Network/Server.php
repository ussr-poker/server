<?php
declare(strict_types=1);

namespace App\Network;

use App\Game\Events\PlayerMoveEvent;
use App\Game\Events\PlayerStakeEvent;
use App\Game\Events\RoundFinishedEvent;
use App\Game\Events\RoundStartedEvent;
use App\Game\Events\SubRoundFinishedEvent;
use App\Game\Events\SubRoundStartedEvent;
use App\Game\Room;
use App\Network\Commands\CommandInterface;
use App\Network\Commands\CreateRoomCommand;
use App\Network\Commands\GetRoomCommand;
use App\Network\Commands\JoinRoomCommand;
use App\Network\Commands\LoginCommand;
use App\Network\Commands\MakeMoveCommand;
use App\Network\Commands\MakeStakeCommand;
use App\Network\Commands\RegisterCommand;
use App\Game\Events\PlayerJoinedEvent;
use App\Network\Events\RoomCreated;
use App\Network\Notifications\NotificationInterface;
use App\Network\Notifications\PlayerConnectedNotification;
use App\Network\Notifications\PlayerDisconnectedNotification;
use App\Network\Notifications\PlayerJoinedNotification;
use App\Network\Notifications\PlayerMoveNotification;
use App\Network\Notifications\PlayerStakeNotification;
use App\Network\Notifications\RoundFinishedNotification;
use App\Network\Notifications\RoundStartedNotification;
use App\Network\Notifications\SubRoundFinishedNotification;
use App\Network\Notifications\SubRoundStartedNotification;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Server
{
    private \Swoole\WebSocket\Server $server;

    /**
     * @var CommandInterface[]
     */
    private array $commands = [];

    /**
     * @var NotificationInterface[]
     */
    private array $notifications = [];

    /**
     * @var Client[]
     */
    private array $clients = [];

    /**
     * @var Room[]
     */
    private array $rooms = [];

    private EventDispatcher $eventDispatcher;

    public function __construct()
    {
        /* @var CommandInterface[] $commands */
        $commands = [
            new RegisterCommand(),
            new LoginCommand($this),
            new CreateRoomCommand($this),
            new JoinRoomCommand($this),
            new MakeStakeCommand($this),
            new MakeMoveCommand($this),
            new GetRoomCommand($this),
        ];

        foreach ($commands as $command) {
            $this->commands[$command->getId()] = $command;
        }

        /* @var NotificationInterface[] $notifications */
        $notifications = [
            new PlayerJoinedNotification($this),
            new PlayerMoveNotification($this),
            new PlayerStakeNotification($this),
            new RoundStartedNotification($this),
            new RoundFinishedNotification($this),
            new SubRoundStartedNotification($this),
            new SubRoundFinishedNotification($this),
            new PlayerDisconnectedNotification($this),
            new PlayerConnectedNotification($this)
        ];

        foreach ($notifications as $notification) {
            $this->notifications[$notification->getId()] = $notification;
        }

        $this->eventDispatcher = new EventDispatcher();

        $this->eventDispatcher->addListener(PlayerJoinedEvent::NAME, [$this, 'onPlayerJoined']);
        $this->eventDispatcher->addListener(RoundStartedEvent::NAME, [$this, 'onRoundStarted']);
        $this->eventDispatcher->addListener(RoundFinishedEvent::NAME, [$this, 'onRoundFinished']);
        $this->eventDispatcher->addListener(PlayerStakeEvent::NAME, [$this, 'onPlayerStake']);
        $this->eventDispatcher->addListener(PlayerMoveEvent::NAME, [$this, 'onPlayerMove']);
        $this->eventDispatcher->addListener(SubRoundStartedEvent::NAME, [$this, 'onSubRoundStarted']);
        $this->eventDispatcher->addListener(SubRoundFinishedEvent::NAME, [$this, 'onSubRoundFinished']);
    }

    public function start(string $host = '127.0.0.1', int $port = 9501): void
    {
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

        $commandId = (int)($payload['commandId'] ?? 0);
        $commandPayload = $payload['commandPayload'] ?? null;

        if (!isset($this->commands[$commandId])) {
            $server->send($fd, 'unknown_command');
            return;
        }

        $command = $this->commands[$commandId];
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
            $this->sendMessage($client, \json_encode(['ok' => false, 'res' => 'wrong_payload']));
            return;
        }

        $commandId = (int)($payload['commandId'] ?? 0);
        $commandPayload = $payload['commandPayload'] ?? null;

        if (!isset($this->commands[$commandId])) {
            $this->sendMessage($client, \json_encode(['ok' => false, 'res' => 'unknown_command']));
            return;
        }

        $command = $this->commands[$commandId];

        // asynchronous command handling
        go(fn () => $this->handleCommand($client, $command, $commandPayload));
    }

    private function handleCommand(Client $client, CommandInterface $command, $commandPayload): void
    {
        try {
            $commandRes = $command->handle($client, $commandPayload);
            $response = \json_encode([
                'ok' => true,
                'commandId' => $command->getId(),
                'commandRes' => $commandRes
            ]);
        } catch (\Throwable $e) {
            $response = \json_encode([
                'ok' => false,
                'commandId' => $command->getId(),
                'commandRes' => $e->getMessage()
            ]);
        }

        $this->sendMessage($client, $response);
    }

    public function onClose($server, $fd): void
    {
        $client = $this->clients[$fd];
        $user = $client->getUser();
        if ($user) {
            $this->onPlayerDisconnected($user->id);
        }

        unset($this->clients[$fd]);

        \logger()->info('Client disconnected', [
            'fd' => $fd
        ]);
    }

    public function sendMessage(Client $client, $data): void
    {
        if ($this->disconnectBadClient($client->getFd())) {
            throw new \LogicException('Client disconnected');
        }

        $this->server->push($client->getFd(), $data);
    }

    public function broadcast(
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

            \logger()->info('Sending notification to player', [
                'fd' => $client->getFd(),
                'id' => $client->getUser()->id,
                'name' => $client->getUser()->name
            ]);

            try {
                $message = \json_encode([
                    'ok' => true,
                    'commandId' => $commandId,
                    'commandRes' => $data + ($playerSpecificData[$player->id] ?? [])
                ]);

                $this->sendMessage($client, $message);
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

    public function onPlayerDisconnected(int $playerId): void
    {
        $this->notifications[PlayerDisconnectedNotification::ID]->handle($playerId);
    }

    public function onPlayerConnected(int $playerId): void
    {
        $this->notifications[PlayerConnectedNotification::ID]->handle($playerId);
    }

    public function onPlayerJoined(PlayerJoinedEvent $event): void
    {
        $this->notifications[PlayerJoinedNotification::ID]->handle($event);
    }

    public function onRoundStarted(RoundStartedEvent $event): void
    {
        $this->notifications[RoundStartedNotification::ID]->handle($event);
    }

    public function onRoundFinished(RoundFinishedEvent $event): void
    {
        $this->notifications[RoundFinishedNotification::ID]->handle($event);
    }

    public function onPlayerStake(PlayerStakeEvent $event): void
    {
        $this->notifications[PlayerStakeNotification::ID]->handle($event);
    }

    public function onPlayerMove(PlayerMoveEvent $event): void
    {
        $this->notifications[PlayerMoveNotification::ID]->handle($event);
    }

    public function onSubRoundStarted(SubRoundStartedEvent $event): void
    {
        $this->notifications[SubRoundStartedNotification::ID]->handle($event);
    }

    public function onSubRoundFinished(SubRoundFinishedEvent $event): void
    {
        $this->notifications[SubRoundFinishedNotification::ID]->handle($event);
    }

    private function disconnectBadClient(int $fd): bool
    {
        if (!$this->server->isEstablished($fd)) {
            \logger()->info('Disconnected client', [
                'fd' => $fd,
            ]);

            unset($this->clients[$fd]);
            return true;
        }

        return false;
    }
}
