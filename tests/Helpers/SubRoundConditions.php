<?php
declare(strict_types=1);

namespace App\Tests\Helpers;

use App\Game\Cards\Card;
use App\Game\Player;
use App\Game\Round\JokerMove;
use App\Game\Round\PlayerDeck;

/**
 * Class SubRoundConditions
 * @package App\Tests\Helpers
 */
class SubRoundConditions
{
    public int $winnerId;

    public Card $trump;

    /**
     * @var Player[]
     */
    public array $players;

    /**
     * @var PlayerDeck[]
     */
    public array $playerDecks;

    /**
     * @var JokerMove[]
     */
    public array $jokerMoves;
}