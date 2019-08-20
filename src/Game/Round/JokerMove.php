<?php
declare(strict_types=1);

namespace App\Game\Round;

use App\Game\Cards\CardSuit;

class JokerMove
{
    public const MODE_LOWEST = 0;
    public const MODE_HIGHEST = 1;

    private int $mode;
    private CardSuit $suit;

    public function __construct(int $mode, CardSuit $suit)
    {
        if (!\in_array($mode, [self::MODE_LOWEST, self::MODE_HIGHEST], true)) {
            throw new \InvalidArgumentException('Unknown joker mode');
        }

        $this->mode = $mode;
        $this->suit = $suit;
    }

    public function getMode(): int
    {
        return $this->mode;
    }

    public function getSuit(): CardSuit
    {
        return $this->suit;
    }
}