<?php
declare(strict_types=1);

namespace App\Tests\Unit\Game\Round;

use App\Game\Cards\CardSuit;
use App\Game\Events\SubRoundStarted;
use App\Game\Player;
use App\Game\Room;
use App\Tests\Helpers\SubRoundConditionsBuilder;
use App\Tests\Unit\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class SubRoundTest extends TestCase
{
    public function testSuccess(): void
    {
        $conditions = (new SubRoundConditionsBuilder())
            ->addPlayer(228)
            ->addCard(CardSuit::clubs(), 2)
            //
            ->addPlayer(322)
            ->addCard(CardSuit::clubs(), 3)
            //
            ->setTrump(CardSuit::clubs(), 6)
            ->setWinnerId(322)
            ->getConditions();

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(SubRoundStarted::NAME, [$this, 'onSubRoundStarted']);

        $room = new Room($dispatcher, \count($conditions->players), 36);
        foreach ($conditions->players as $player) {
            $room->joinPlayer($player);
        }

        $game = $room->getGame();

        $game->getCurrentRound()->setPlayerDecks($conditions->playerDecks);
        $game->getCurrentRound()->setTrump($conditions->trump);

        // ROUND 1
        for ($i = 0, $iMax = \count($conditions->players); $i < $iMax; $i++) {
            $game->makeStake($conditions->players[$i], 1);
        }

        for ($i = 0, $iMax = \count($conditions->players); $i < $iMax; $i++) {
            $winner = $game->makeMove($conditions->players[$i], 0, $conditions->jokerMoves[$i]);
        }

        // ROUND 2
        foreach ($game->getCurrentRound()->getStakesOrder() as $player) {
            $game->makeStake($player, 0);
        }

        foreach ($game->getCurrentRound()->getStakesOrder() as $player) {
            $winner = $game->makeMove($player, 0, null);
        }

        $round = $game->getCurrentRound();
        $subRound = $round->getCurrentSubRound();

        \logger()->info('after', [
            'awaitedPlayer' => $round->getPlayerAwaited(),
            'round' => [
                'number' => $round->getNumber(),
                'state' => $round->getState(),
                'stakesOrder' => \array_map(fn(Player $player, $key) => [$key, $player->name], $round->getStakesOrder(), \array_keys($round->getStakesOrder())),
            ],
            'subround' => [
                'number' => $subRound->getNumber(),
                'movesCount' => $subRound->getMovesCount(),
                'playersOrder' => \array_map(fn(Player $player, $key) => [$key, $player->name], $subRound->getPlayersOrder(), array_keys($subRound->getPlayersOrder())),
            ]
        ]);
    }

    public function onSubRoundStarted(SubRoundStarted $event): void
    {
        $subRound = $event->getSubRound();
        $round = $subRound->getRound();

        $data = [
            'number' => $subRound->getNumber(),
//            'awaitedPlayerId' => $subRound->getRound()->getPlayerAwaited()->id ?? null
        ];

        \logger()->info('before', [
            'awaitedPlayer' => $subRound->getRound()->getPlayerAwaited(),
            'round' => [
                'number' => $round->getNumber(),
                'state' => $round->getState(),
                'stakesOrder' => \array_map(fn(Player $player, $key) => [$key, $player->name], $round->getStakesOrder(), \array_keys($round->getStakesOrder())),
            ],
            'subround' => [
                'number' => $subRound->getNumber(),
                'movesCount' => $subRound->getMovesCount(),
                'playersOrder' => \array_map(fn(Player $player, $key) => [$key, $player->name], $subRound->getPlayersOrder(), array_keys($subRound->getPlayersOrder())),
            ]
        ]);
    }
}