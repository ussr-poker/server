<?php
declare(strict_types=1);

namespace App\Tests\Unit\Game;

use App\Database\Entity\User;
use App\Game\Cards\Card;
use App\Game\Cards\CardSuit;
use App\Game\Player;
use App\Game\Room;
use App\Game\Round\PlayerDeck;
use App\Network\Client;
use App\Tests\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class GameTest extends TestCase
{
    public function testGameProcess(): void
    {
        $dispatcher = new EventDispatcher();
        $room = new Room($dispatcher,2, 36);
        $game = $room->getGame();

        $clientA = new Client(0);
        $userA = new User();
        $userA->id = 228;
        $userA->name = 'UserA';
        $clientA->setUser($userA);
        $playerA = new Player($clientA);

        $clientB = new Client(0);
        $userB = new User();
        $userB->id = 322;
        $userB->name = 'UserB';
        $clientB->setUser($userB);
        $playerB = new Player($clientB);

        $room->joinPlayer($playerA);
        $room->joinPlayer($playerB);

        $playerADeck = new PlayerDeck($playerA);
        $playerADeck->addCard(new Card(CardSuit::diamonds(), 2));

        $playerBDeck = new PlayerDeck($playerB);
        $playerBDeck->addCard(new Card(CardSuit::diamonds(), 3));

        $game->getCurrentRound()->setPlayerDecks([
            $playerADeck,
            $playerBDeck,
        ]);

        $game->makeStake($playerA, 1);
        $game->makeStake($playerB, 1);

        $game->makeMove($playerA, 0, null);
        $game->makeMove($playerB, 0, null);

        $scoreBoard = $game->getScoreBoard();

        $this->assertEquals(-10, $scoreBoard->getScores()[0]->getPlayers()[0]->getScore());
        $this->assertEquals(10, $scoreBoard->getScores()[0]->getPlayers()[1]->getScore());
    }
}
