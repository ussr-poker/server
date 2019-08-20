<?php
declare(strict_types=1);

namespace App\Game\Round;

use App\Game\Cards\Card;
use App\Game\Cards\CardSuit;
use App\Game\Player;

class PlayerDeck
{
    private Player $player;

    /**
     * @var Card[]
     */
    private array $cards = [];

    public function __construct(Player $player)
    {
        $this->player = $player;
    }

    public function addCard(Card $card): void
    {
        $this->cards[] = $card;
    }

    /**
     * @return Card[]
     */
    public function getCards(): array
    {
        return $this->cards;
    }

    public function hasCardWithSuit(CardSuit $suit): bool
    {
        foreach ($this->cards as $card) {
            if (!$card->isJoker() && $card->getSuit()->getSuit() === $suit->getSuit()) {
                return true;
            }
        }

        return false;
    }

    public function hasHigherCardWithSameSuit(Card $card): bool
    {
        foreach ($this->cards as $_card) {
            if ($_card->getSuit() === $card->getSuit() && $_card->getValue() > $card->getValue()) {
                return true;
            }
        }

        return false;
    }

    public function getCard(int $cardId): Card
    {
        if (!isset($this->cards[$cardId])) {
            throw new \InvalidArgumentException('Card not found in player deck');
        }

        return $this->cards[$cardId];
    }

    public function removeCard(int $cardId): void
    {
        unset($this->cards[$cardId]);
    }

    public function size(): int
    {
        return \count($this->cards);
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }
}