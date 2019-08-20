<?php
declare(strict_types=1);

namespace App\Game\Cards;

class Card
{
    private CardSuit $suit;

    /**
     * 2-10 - card face value as is
     * 11 - J
     * 12 - Q
     * 13 - K
     * 14 - A
     *
     * @var int
     */
    private int $value;

    public function __construct(CardSuit $suit, int $value)
    {
        $this->suit = $suit;
        $this->value = $value;

        if (2 > $value || 14 < $value) {
            throw new \InvalidArgumentException('Card value must be between 2 and 14');
        }
    }

    public function getSuit(): CardSuit
    {
        return $this->suit;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function isJoker(): bool
    {
        return 6 === $this->value && CardSuit::SPADES === $this->suit->getSuit();
    }
}