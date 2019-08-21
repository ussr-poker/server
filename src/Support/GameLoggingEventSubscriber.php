<?php
declare(strict_types=1);

namespace App\Support;

use App\Game\Events\GameFinishedEvent;
use App\Game\Events\GameStartedEvent;
use App\Game\Events\PlayerMoveEvent as PlayerMoveEvent;
use App\Game\Events\PlayerStakeEvent as PlayerStakeEvent;
use App\Game\Events\RoundFinishedEvent;
use App\Game\Events\RoundStartedEvent;
use App\Game\Events\SubRoundFinishedEvent;
use App\Game\Events\SubRoundStartedEvent;
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

        static::$roundStarted = \Closure::fromCallable(static function (RoundStartedEvent $event) {
            \logger()->info('Game round started', ['round' => $event->getRound()->getNumber()]);
        });

        static::$roundFinished = \Closure::fromCallable(static function (RoundFinishedEvent $event) {
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

        static::$subRoundStarted = \Closure::fromCallable(static function (SubRoundStartedEvent $event) {
            $subRound = $event->getSubRound();
            $round = $subRound->getRound();

            \logger()->info('Game subround started', [
                'round' => $round->getNumber(),
                'subround' => $subRound->getNumber()
            ]);
        });

        static::$subRoundFinished = \Closure::fromCallable(static function (SubRoundFinishedEvent $event) {
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

        $eventDispatcher->removeListener(GameStartedEvent::NAME, static::$gameStarted);
        $eventDispatcher->removeListener(GameFinishedEvent::NAME, static::$gameFinished);

        $eventDispatcher->removeListener(RoundStartedEvent::NAME, static::$roundStarted);
        $eventDispatcher->removeListener(RoundFinishedEvent::NAME, static::$roundFinished);

        $eventDispatcher->removeListener(SubRoundStartedEvent::NAME, static::$subRoundStarted);
        $eventDispatcher->removeListener(SubRoundFinishedEvent::NAME, static::$subRoundFinished);

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

        $eventDispatcher->addListener(GameStartedEvent::NAME, static::$gameStarted);
        $eventDispatcher->addListener(GameFinishedEvent::NAME, static::$gameFinished);

        $eventDispatcher->addListener(RoundStartedEvent::NAME, static::$roundStarted);
        $eventDispatcher->addListener(RoundFinishedEvent::NAME, static::$roundFinished);

        $eventDispatcher->addListener(SubRoundStartedEvent::NAME, static::$subRoundStarted);
        $eventDispatcher->addListener(SubRoundFinishedEvent::NAME, static::$subRoundFinished);

        $eventDispatcher->addListener(PlayerStakeEvent::NAME, static::$playerStake);
        $eventDispatcher->addListener(PlayerMoveEvent::NAME, static::$playerMove);
    }
}
