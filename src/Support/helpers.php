<?php
declare(strict_types=1);

use App\Game\Cards\Card;
use App\Game\Cards\CardSuit;
use App\Game\Player;

if (!function_exists('logger')) {
    function logger(): \Monolog\Logger
    {
        return \App\Support\Logger::getLogger();
    }
}

if (!function_exists('getPlayersOrder')) {
    /**
     * @param Player $firstPlayer
     * @param array $players
     * @return Player[]
     */
    function getPlayersOrder(Player $firstPlayer, array $players): array
    {
        $order = [$firstPlayer];

        $startAdding = false;
        foreach ($players as $player) {
            if ($player->id === $firstPlayer->id) {
                $startAdding = true;
                continue;
            }

            if ($startAdding) {
                $order[] = $player;
            }
        }

        foreach ($players as $player) {
            if ($player->id !== $firstPlayer->id) {
                $order[] = $player;
            } else {
                break;
            }
        }

        return $order;
    }
}

if (!function_exists('getCardTxtSuit')) {
    function getCardTxtSuit(Card $card): string
    {
        $cardSuit = $card->getSuit();

        return getTxtSuit($cardSuit);
    }
}

if (!function_exists('getTxtSuit')) {
    function getTxtSuit(CardSuit $suit): string
    {
        switch ($suit->getSuit()) {
            case CardSuit::HEARTS:
                $char = '♥';
                break;
            case CardSuit::DIAMONDS:
                $char = '♦';
                break;
            case CardSuit::SPADES:
                $char = '♠';
                break;
            case CardSuit::CLUBS:
                $char = '♣';
                break;
        }

        return $char;
    }
}

if (!function_exists('getTxtValue')) {
    function getTxtValue(Card $card): string
    {
        $cardValue = $card->getValue();
        if (10 >= $cardValue) {
            $value = (string)$cardValue;
        } elseif (11 === $cardValue) {
            $value = 'J';
        } elseif (12 === $cardValue) {
            $value = 'Q';
        } elseif (13 === $cardValue) {
            $value = 'K';
        } elseif (14 === $cardValue) {
            $value = 'A';
        }

        return $value;
    }
}