<?php
declare(strict_types=1);

namespace App\Network\Notifications;

use App\Game\Events\RoundStartedEvent;
use App\Network\Server;

class RoundStartedNotification implements NotificationInterface
{
    public const ID = 1001;

    private Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function handle($event): void
    {
        if (!$event instanceof RoundStartedEvent) {
            throw new \InvalidArgumentException('RoundStartedEvent must be passed');
        }

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

        $this->server->broadcast($room, self::ID, $data, null, $playerSpecificData);
    }

    public function getId(): int
    {
        return self::ID;
    }
}
