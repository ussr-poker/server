<?php
declare(strict_types=1);

namespace App\Game\Cards;

class CardSuit
{
    // черва
    public const HEARTS = 0;

    // бубна
    public const DIAMONDS = 1;

    // пика
    public const SPADES = 2;

    // трефа/крестья
    public const CLUBS = 3;

    private static ?CardSuit $hearts = null;

    private static ?CardSuit $diamonds = null;

    private static ?CardSuit $spades = null;

    private static ?CardSuit $clubs = null;

    private int $suit;

    public static function hearts(): CardSuit
    {
        if (null === static::$hearts) {
            static::$hearts = new self(self::HEARTS);
        }

        return static::$hearts;
    }

    public static function diamonds(): CardSuit
    {
        if (null === static::$diamonds) {
            static::$diamonds = new self(self::DIAMONDS);
        }

        return static::$diamonds;
    }

    public static function spades(): CardSuit
    {
        if (null === static::$spades) {
            static::$spades = new self(self::SPADES);
        }

        return static::$spades;
    }

    public static function clubs(): CardSuit
    {
        if (null === static::$clubs) {
            static::$clubs = new self(self::CLUBS);
        }

        return static::$clubs;
    }

    public static function from(int $suit): CardSuit
    {
        switch ($suit) {
            case self::HEARTS:
                return self::hearts();
            case self::DIAMONDS:
                return self::diamonds();
            case self::SPADES:
                return self::spades();
            case self::CLUBS:
                return self::clubs();
        }

        throw new \InvalidArgumentException('Wrong suit');
    }

    private function __construct(int $suit)
    {
        $this->suit = $suit;
    }

    /**
     * @return int
     */
    public function getSuit(): int
    {
        return $this->suit;
    }

    private function __clone() {}
}