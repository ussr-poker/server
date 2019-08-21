<?php
declare(strict_types=1);

namespace App\Tests\Unit\Game\Round;

use App\Game\Cards\CardSuit;
use App\Game\Room;
use App\Game\Round\JokerMove;
use App\Tests\Helpers\SubRoundConditions;
use App\Tests\Helpers\SubRoundConditionsBuilder;
use App\Tests\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class SubRoundWinnerTest extends TestCase
{
    /**
     * @dataProvider playersDataProvider
     * @param SubRoundConditions $conditions
     */
    public function testWinner(SubRoundConditions $conditions): void
    {
        $dispatcher = new EventDispatcher();
        $room = new Room($dispatcher, \count($conditions->players), 36);
        foreach ($conditions->players as $player) {
            $room->joinPlayer($player);
        }

        $game = $room->getGame();

        $game->getCurrentRound()->setPlayerDecks($conditions->playerDecks);
        $game->getCurrentRound()->setTrump($conditions->trump);

        for ($i = 0, $iMax = \count($conditions->players); $i < $iMax; $i++) {
            $game->makeStake($conditions->players[$i], 1);
        }

        for ($i = 0, $iMax = \count($conditions->players); $i < $iMax; $i++) {
            $winner = $game->makeMove($conditions->players[$i], 0, $conditions->jokerMoves[$i]);
        }

        $this->assertEquals($conditions->winnerId, $winner->id);
    }

    public function playersDataProvider(): array
    {
        return [
            'same_suit_trump' => [
                (new SubRoundConditionsBuilder())
                    ->addPlayer(228)
                    ->addCard(CardSuit::clubs(), 2)
                    //
                    ->addPlayer(322)
                    ->addCard(CardSuit::clubs(), 3)
                    //
                    ->setTrump(CardSuit::clubs(), 6)
                    ->setWinnerId(322)
                    ->getConditions()
            ],
            'trump_vs_not_trump' => [
                (new SubRoundConditionsBuilder())
                    ->addPlayer(228)
                    ->addCard(CardSuit::clubs(), 2)
                    //
                    ->addPlayer(322)
                    ->addCard(CardSuit::hearts(), 3)
                    //
                    ->setTrump(CardSuit::clubs(), 6)
                    ->setWinnerId(228)
                    ->getConditions()
            ],
            'four_players' => [
                (new SubRoundConditionsBuilder())
                    ->addPlayer(228)
                    ->addCard(CardSuit::clubs(), 2)
                    //
                    ->addPlayer(322)
                    ->addCard(CardSuit::hearts(), 3)
                    //
                    ->addPlayer(1488)
                    ->addCard(CardSuit::clubs(), 4)
                    //
                    ->addPlayer(1933)
                    ->addCard(CardSuit::spades(), 5)
                    //
                    ->setTrump(CardSuit::clubs(), 6)
                    ->setWinnerId(1488)
                    ->getConditions()
            ],
            'joker_lowest_spades_fold' => [
                (new SubRoundConditionsBuilder())
                    ->addPlayer(228)
                    ->addCard(CardSuit::spades(), 6, new JokerMove(JokerMove::MODE_LOWEST, CardSuit::spades()))
                    //
                    ->addPlayer(322)
                    ->addCard(CardSuit::spades(), 2)
                    //
                    ->setTrump(CardSuit::clubs(), 6)
                    ->setWinnerId(322)
                    ->getConditions()
            ],
            'joker_lowest_spades_take' => [
                (new SubRoundConditionsBuilder())
                    ->addPlayer(228)
                    ->addCard(CardSuit::spades(), 6, new JokerMove(JokerMove::MODE_LOWEST, CardSuit::spades()))
                    //
                    ->addPlayer(322)
                    ->addCard(CardSuit::hearts(), 2)
                    //
                    ->setTrump(CardSuit::clubs(), 6)
                    ->setWinnerId(228)
                    ->getConditions()
            ],
            'joker_highest_trump_first' => [
                (new SubRoundConditionsBuilder())
                    ->addPlayer(228)
                    ->addCard(CardSuit::spades(), 6, new JokerMove(JokerMove::MODE_HIGHEST, CardSuit::clubs()))
                    //
                    ->addPlayer(322)
                    ->addCard(CardSuit::clubs(), 14)
                    //
                    ->setTrump(CardSuit::clubs(), 6)
                    ->setWinnerId(228)
                    ->getConditions()
            ],
            'joker_highest_trump_second' => [
                (new SubRoundConditionsBuilder())
                    ->addPlayer(322)
                    ->addCard(CardSuit::clubs(), 14)
                    //
                    ->addPlayer(228)
                    ->addCard(CardSuit::spades(), 6, new JokerMove(JokerMove::MODE_HIGHEST, CardSuit::clubs()))
                    //
                    ->addPlayer(1488)
                    ->addCard(CardSuit::diamonds(), 6)
                    //
                    ->setTrump(CardSuit::clubs(), 6)
                    ->setWinnerId(228)
                    ->getConditions()
            ]
        ];
    }
}
