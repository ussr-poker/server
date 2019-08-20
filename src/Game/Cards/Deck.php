<?php
declare(strict_types=1);

namespace App\Game\Cards;

class Deck
{
    public const CARDS_COUNT_SHORT = 36;

    public const CARDS_COUNT_FULL = 52;

    /**
     * @var Card[]
     */
    private array $cards = [];

    private int $cardsLeft;

    private int $size;

    public function __construct(int $cardsCount)
    {
        if (!\in_array($cardsCount, [self::CARDS_COUNT_SHORT, self::CARDS_COUNT_FULL], true)) {
            throw new \InvalidArgumentException('Cards count must be 36 or 52');
        }

        $this->size = $cardsCount;
        $this->cardsLeft = $cardsCount;

        $cardsCountBySingleSuit = $cardsCount / 4;
        $additionalValue = self::CARDS_COUNT_SHORT === $cardsCount ? 6 : 2;

        for ($i = 0; $i < $cardsCountBySingleSuit; $i++) {
            $this->cards[] = new Card(CardSuit::hearts(), $additionalValue + $i);
            $this->cards[] = new Card(CardSuit::clubs(), $additionalValue + $i);
            $this->cards[] = new Card(CardSuit::spades(), $additionalValue + $i);
            $this->cards[] = new Card(CardSuit::diamonds(), $additionalValue + $i);
        }

        \shuffle($this->cards);
    }

    public function getCard(): Card
    {
        $idx = $this->size - $this->cardsLeft;
        if ($idx >= $this->size) {
            throw new \LogicException('No more cards in the deck');
        }

        $this->cardsLeft--;

        return $this->cards[$idx];
    }

    /**
     * @return Card[]
     */
    public function getCards(): array
    {
        return $this->cards;
    }

    public function getSize(): int
    {
        return $this->size;
    }
}