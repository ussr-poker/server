<?php
declare(strict_types=1);

namespace App\Tests\Helpers;

use App\Database\Entity\User;
use App\Game\Cards\Card;
use App\Game\Cards\CardSuit;
use App\Game\Player;
use App\Game\Round\JokerMove;
use App\Game\Round\PlayerDeck;
use App\Network\Client;

class SubRoundConditionsBuilder
{
    private int $playersCount = 0;

    /**
     * @var Player[]
     */
    private array $players = [];

    /**
     * @var PlayerDeck[]
     */
    private array $playerDecks = [];

    /**
     * @var JokerMove[]
     */
    private array $jokerMoves = [];

    private int $winnerId;

    private Card $trump;

    private SubRoundConditions $conditions;

    public function __construct()
    {
        $this->conditions = new SubRoundConditions();
    }

    public function addPlayer(int $id): self
    {
        $client = new Client(0);
        $user = new User();
        $user->id = $id;
        $user->name = '(no name)';
        $client->setUser($user);

        $this->players[] = $player = new Player($client);
        $this->playerDecks[] = new PlayerDeck($player);

        $this->playersCount++;

        return $this;
    }

    public function addCard(CardSuit $suit, int $value, ?JokerMove $jokerMove = null): self
    {
        $card = new Card($suit, $value);
        if ($card->isJoker()) {
            if (null === $jokerMove) {
                throw new \LogicException('You should specify joker move when card is joker');
            }

            $this->jokerMoves[$this->playersCount - 1] = $jokerMove;
        } else {
            $this->jokerMoves[$this->playersCount - 1] = null;
        }

        $this->playerDecks[$this->playersCount - 1]->addCard($card);

        return $this;
    }

    public function setWinnerId(int $winnerId): self
    {
        $this->winnerId = $winnerId;

        return $this;
    }

    public function setTrump(CardSuit $suit, int $value): self
    {
        $this->trump = new Card($suit, $value);

        return $this;
    }

    public function getConditions(): SubRoundConditions
    {
        $this->conditions->winnerId = $this->winnerId;
        $this->conditions->trump = $this->trump;
        $this->conditions->players = $this->players;
        $this->conditions->playerDecks = $this->playerDecks;
        $this->conditions->jokerMoves = $this->jokerMoves;

        return $this->conditions;
    }
}