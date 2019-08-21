<?php
declare(strict_types=1);

namespace App\Network\Notifications;

use App\Game\Events\PlayerMoveEvent;
use App\Network\Server;

class PlayerMoveNotification implements NotificationInterface
{
    public const ID = 1004;

    private Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function handle($event): void
    {
        if (!$event instanceof PlayerMoveEvent) {
            throw new \InvalidArgumentException('PlayerMoveEvent must be passed');
        }

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

        $round = $event->getSubRound()->getRound();

        $data = [
            'playerId' => $playerMove->getPlayer()->id,
            'card' => [
                'id' => $event->getCardId(),
                'suit' => $card->getSuit()->getSuit(),
                'value' => $card->getValue()
            ],
            'jokerMove' => $formattedJokerMove,
            'awaitedPlayerId' => $round->getPlayerAwaited()->id ?? null
        ];

        $this->server->broadcast($round->getGame()->getRoom(), self::ID, $data);
    }

    public function getId(): int
    {
        return self::ID;
    }
}
