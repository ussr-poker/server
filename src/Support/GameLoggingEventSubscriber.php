<?php
declare(strict_types=1);

namespace App\Support;

use App\Game\Events\GameFinished;
use App\Game\Events\GameStarted;
use App\Game\Events\PlayerMove as PlayerMoveEvent;
use App\Game\Events\PlayerStake as PlayerStakeEvent;
use App\Game\Events\RoundFinished;
use App\Game\Events\RoundStarted;
use App\Game\Events\SubRoundFinished;
use App\Game\Events\SubRoundStarted;
use Psr\EventDispatcher\EventDispatcherInterface;

class GameLoggingEventSubscriber
{
    private static bool $initialized = false;

    private static \Closure $gameStarted;

    private static \Closure $gameFinished;

    private static \Closure $roundStarted;

    private static \Closure $roundFinished;

    private static \Closure $subRoundStarted;

    private static \Closure $subRoundFinished;

    private static \Closure $playerStake;

    private static \Closure $playerMove;

    private static function initialize(): void
    {
        static::$initialized = true;

        static::$gameStarted = \Closure::fromCallable(static function () {
            \logger()->info('Game started');
        });

        static::$gameFinished = \Closure::fromCallable(static function () {
            \logger()->info('Game finished');
        });

        static::$roundStarted = \Closure::fromCallable(static function (RoundStarted $event) {
            \logger()->info('Game round started', ['round' => $event->getRound()->getNumber()]);
        });

        static::$roundFinished = \Closure::fromCallable(static function (RoundFinished $event) {
            $round = $event->getRound();
            $winner = $event->getWinner();
            $result = [];

            foreach ($event->getRoundResults() as $roundResult) {
                $playerStake = $roundResult->getPlayerStake();

                $result[] = [
                    'playerId' => $playerStake->getPlayer()->id,
                    'stake' => $playerStake->getStake(),
                    'wins' => $roundResult->getWins(),
                    'scores' => $roundResult->getScoreResult()
                ];
            }

            \logger()->info('Game round finished', [
                'round' => $round->getNumber(),
                'winnerId' => $winner->id,
                'result' => $result
            ]);
        });

        static::$subRoundStarted = \Closure::fromCallable(static function (SubRoundStarted $event) {
            $subRound = $event->getSubRound();
            $round = $subRound->getRound();

            \logger()->info('Game subround started', [
                'round' => $round->getNumber(),
                'subround' => $subRound->getNumber()
            ]);
        });

        static::$subRoundFinished = \Closure::fromCallable(static function (SubRoundFinished $event) {
            $subRound = $event->getSubRound();
            $round = $subRound->getRound();
            $winner = $event->getWinner();

            \logger()->info('Game subround finished', [
                'round' => $round->getNumber(),
                'subround' => $subRound->getNumber(),
                'winnerId' => $winner->id
            ]);
        });

        static::$playerStake = \Closure::fromCallable(static function (PlayerStakeEvent $event) {
            $round = $event->getRound();
            $playerStake = $event->getPlayerStake();

            \logger()->info('Game round stake made', [
                'round' => $round->getNumber(),
                'playerId' => $playerStake->getPlayer()->id,
                'stake' => $playerStake->getStake()
            ]);
        });

        static::$playerMove = \Closure::fromCallable(static function (PlayerMoveEvent $event) {
            $subRound = $event->getSubRound();
            $round = $subRound->getRound();

            $playerMove = $event->getPlayerMove();
            $card = $playerMove->getCard();

            $jokerMove = $playerMove->getJokerMove();

            \logger()->info('Game subround player move', [
                'round' => $round->getNumber(),
                'subround' => $subRound->getNumber(),
                'playerId' => $playerMove->getPlayer()->id,
                'card' => [
                    'id' => $event->getCardId(),
                    'suit' => \getCardTxtSuit($card),
                    'value' => \getTxtValue($card)
                ],
                'jokerMove' => $jokerMove ? [
                    'suit' => \getTxtSuit($jokerMove->getSuit()),
                    'mode' => $jokerMove->getMode()
                ] : null
            ]);
        });
    }

    /**
     * @param EventDispatcherInterface|\Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
     */
    public static function unsubscribe(EventDispatcherInterface $eventDispatcher): void
    {
        if (!static::$initialized) {
            static::initialize();
        }

        $eventDispatcher->removeListener(GameStarted::NAME, static::$gameStarted);
        $eventDispatcher->removeListener(GameFinished::NAME, static::$gameFinished);

        $eventDispatcher->removeListener(RoundStarted::NAME, static::$roundStarted);
        $eventDispatcher->removeListener(RoundFinished::NAME, static::$roundFinished);

        $eventDispatcher->removeListener(SubRoundStarted::NAME, static::$subRoundStarted);
        $eventDispatcher->removeListener(SubRoundFinished::NAME, static::$subRoundFinished);

        $eventDispatcher->removeListener(PlayerStakeEvent::NAME, static::$playerStake);
        $eventDispatcher->removeListener(PlayerMoveEvent::NAME, static::$playerMove);
    }

    /**
     * @param EventDispatcherInterface|\Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
     */
    public static function subscribe(EventDispatcherInterface $eventDispatcher): void
    {
        if (!static::$initialized) {
            static::initialize();
        }

        $eventDispatcher->addListener(GameStarted::NAME, static::$gameStarted);
        $eventDispatcher->addListener(GameFinished::NAME, static::$gameFinished);

        $eventDispatcher->addListener(RoundStarted::NAME, static::$roundStarted);
        $eventDispatcher->addListener(RoundFinished::NAME, static::$roundFinished);

        $eventDispatcher->addListener(SubRoundStarted::NAME, static::$subRoundStarted);
        $eventDispatcher->addListener(SubRoundFinished::NAME, static::$subRoundFinished);

        $eventDispatcher->addListener(PlayerStakeEvent::NAME, static::$playerStake);
        $eventDispatcher->addListener(PlayerMoveEvent::NAME, static::$playerMove);
    }
}