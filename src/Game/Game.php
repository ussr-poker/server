<?php
declare(strict_types=1);

namespace App\Game;

use App\Game\Cards\Deck;
use App\Game\Events\GameFinishedEvent;
use App\Game\Events\GameStartedEvent;
use App\Game\Events\PlayerStakeEvent as PlayerStakeEvent;
use App\Game\Events\RoundFinishedEvent;
use App\Game\Events\RoundStartedEvent;
use App\Game\Exceptions\GameIsNotReadyException;
use App\Game\Round\JokerMove;
use App\Game\Round\Round;
use App\Game\Round\RoundsMap;
use App\Game\ScoreBoard\ScoreBoard;
use App\Support\GameLoggingEventSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Game
{
    public const STATE_NEW = 0;
    public const STATE_IN_PROGRESS = 1;
    public const STATE_FINISHED = 2;

    public const MODE_SHORT = 0;
    public const MODE_LONG = 1;

    private Room $room;

    private int $state;

    private int $deckSize;

    private int $mode;

    private ScoreBoard $scoreBoard;

    private int $roundsPlayed = 0;

    private Round $currentRound;

    private array $roundsMap;

    private ?Player $roundWinner = null;

    private EventDispatcher $eventDispatcher;

    public function __construct(EventDispatcher $eventDispatcher, Room $room, int $deckSize, int $mode)
    {
        $this->room = $room;
        $this->state = self::STATE_NEW;
        $this->deckSize = $deckSize;
        $this->mode = $mode;
        $this->scoreBoard = new ScoreBoard();
        $this->eventDispatcher = $eventDispatcher;

        switch ($mode) {
            case self::MODE_SHORT:
                $this->roundsMap = RoundsMap::SHORT_ROUNDS_MAP_TWO_PLAYERS;
                break;
//            case self::MODE_LONG:
//                $this->roundsMap = RoundsMap::SHORT_ROUNDS_MAP_TWO_PLAYERS;
//                break;
        }

//        $this->eventDispatcher = new EventDispatcher();
        $this->eventDispatcher->addListener(RoundStartedEvent::NAME, [$this, 'onRoundStarted'], 1);
        $this->eventDispatcher->addListener(RoundFinishedEvent::NAME, [$this, 'onRoundFinished'], 1);
        $this->eventDispatcher->addListener(PlayerStakeEvent::NAME, [$this, 'onPlayerStake'], 1);

        GameLoggingEventSubscriber::subscribe($this->eventDispatcher);
    }

    public function __destruct()
    {
        GameLoggingEventSubscriber::unsubscribe($this->eventDispatcher);
    }

    public function startGame(): void
    {
        $roundMap = $this->roundsMap[0];
        $this->currentRound = new Round($this, $roundMap[0], $roundMap[1], $roundMap[2]);

        $this->eventDispatcher->dispatch(new GameStartedEvent($this), GameStartedEvent::NAME);

        $this->startRound();
    }

    private function startRound(): void
    {
        $deck = new Deck($this->deckSize);

        $this->currentRound->start($deck, $this->roundWinner);

        $this->state = self::STATE_IN_PROGRESS;
    }

    public function makeMove(Player $player, int $cardId, ?JokerMove $jokerMove): ?Player
    {
        if (self::STATE_IN_PROGRESS !== $this->state) {
            throw new GameIsNotReadyException($this);
        }

        return $this->currentRound->makeMove($player, $cardId, $jokerMove);
    }

    public function makeStake(Player $player, int $stake): void
    {
        if (self::STATE_IN_PROGRESS !== $this->state) {
            throw new GameIsNotReadyException($this);
        }

        $this->currentRound->makeStake($player, $stake);
    }

    public function onPlayerStake(PlayerStakeEvent $event): void
    {
        $this->scoreBoard->addPlayerStake($event->getRound(), $event->getPlayerStake());
    }

    public function onRoundStarted(RoundStartedEvent $event): void
    {
        $this->scoreBoard->addEntry($event->getRound());
    }

    public function onRoundFinished(RoundFinishedEvent $event): void
    {
        $this->roundsPlayed++;
        $this->roundWinner = $event->getWinner();

        foreach ($event->getRoundResults() as $roundResult) {
            $this->scoreBoard->addRoundResult($event->getRound(), $roundResult);
        }

        if ($this->roundsPlayed === \count($this->roundsMap)) {
            $this->state = self::STATE_FINISHED;

            defer(fn() => $this->eventDispatcher->dispatch(new GameFinishedEvent($this), GameFinishedEvent::NAME));
            return;
        }

        $roundMap = $this->roundsMap[$this->roundsPlayed];
        $this->currentRound = new Round($this, $roundMap[0], $roundMap[1], $roundMap[2]);

        defer(fn() => $this->startRound());
    }

    public function onPlayerDisconnected(Player $player): void
    {

    }

    public function getState(): int
    {
        return $this->state;
    }

    public function getScoreBoard(): ScoreBoard
    {
        return $this->scoreBoard;
    }

    public function getDeckSize(): int
    {
        return $this->deckSize;
    }

    public function getMode(): int
    {
        return $this->mode;
    }

    public function getCurrentRound(): Round
    {
        return $this->currentRound;
    }

    public function getRoundsCount(): int
    {
        return \count($this->roundsMap);
    }

    /**
     * @return EventDispatcher
     */
    public function getEventDispatcher(): EventDispatcher
    {
        return $this->eventDispatcher;
    }

    public function getRoom(): Room
    {
        return $this->room;
    }
}
