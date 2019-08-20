<?php
declare(strict_types=1);

namespace App\Game\Round;

use App\Game\Cards\Card;
use App\Game\Cards\Deck;
use App\Game\Events\PlayerStake as PlayerStakeEvent;
use App\Game\Events\RoundFinished;
use App\Game\Events\RoundStarted;
use App\Game\Events\SubRoundFinished;
use App\Game\Events\SubRoundStarted;
use App\Game\Game;
use App\Game\Player;
use Psr\EventDispatcher\EventDispatcherInterface;

class Round
{
    /**
     * Стандартный раунд
     */
    public const ROUND_TYPE_REGULAR = 0;

    /**
     * Тёмная
     */
    public const ROUND_TYPE_DARKEN = 1;

    /**
     * Безкозырка
     */
    public const ROUND_TYPE_WITHOUT_TRUMP = 2;

    /**
     * Золотая
     */
    public const ROUND_TYPE_GOLDEN = 3;

    /**
     * Мизера
     */
    public const ROUND_TYPE_DOWN = 4;

    public const STATE_NEW = 0;
    public const STATE_STAKES = 1;
    public const STATE_IN_PROGRESS = 2;
    public const STATE_FINISHED = 3;

    private int $number;
    private int $cardsToPlayer;
    private int $type;
    private int $state;

    private Game $game;

    /**
     * @var PlayerDeck[]
     */
    private array $playerDecks = [];

    /**
     * @var PlayerStake[]
     */
    private array $playerStakes = [];

    private Card $trump;

    /**
     * @var Player[]
     */
    private array $stakesOrder = [];

    private int $stakesCount = 0;

    private SubRound $currentSubRound;

    private ?Player $subRoundWinner = null;

    private int $subRoundsPlayed = 0;

    private array $subRoundWins = [];

    private \Closure $onSubRoundFinishedListener;

    /**
     * @var EventDispatcherInterface|\Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(Game $game, int $number, int $cardsToPlayer, int $type)
    {
        if (!\in_array($type, [
            self::ROUND_TYPE_REGULAR,
            self::ROUND_TYPE_DARKEN,
            self::ROUND_TYPE_WITHOUT_TRUMP,
            self::ROUND_TYPE_GOLDEN,
            self::ROUND_TYPE_DOWN
        ], true)) {
            throw new \InvalidArgumentException('Unknown round type');
        }

        $this->number = $number;
        $this->cardsToPlayer = $cardsToPlayer;
        $this->type = $type;
        $this->state = self::STATE_NEW;
        $this->eventDispatcher = $game->getEventDispatcher();
        $this->game = $game;
        $this->onSubRoundFinishedListener = \Closure::fromCallable([$this, 'onSubRoundFinished']);

        $this->eventDispatcher->addListener(SubRoundFinished::NAME, $this->onSubRoundFinishedListener, 1);
    }

    public function __destruct()
    {
        $this->unsubscribe();
    }

    private function unsubscribe(): void
    {
        $this->eventDispatcher->removeListener(SubRoundFinished::NAME, $this->onSubRoundFinishedListener);
    }

    /**
     * @param Deck $deck
     * @param Player|null $firstPlayer
     */
    public function start(Deck $deck, ?Player $firstPlayer): void
    {
        if (self::STATE_NEW !== $this->state) {
            throw new \LogicException('Cannot start already started round');
        }

        $players = $this->game->getRoom()->getPlayers();

        if (null !== $firstPlayer) {
            $players = \getPlayersOrder($firstPlayer, $players);
        } else {
            $firstPlayer = $players[0];
        }

        foreach ($players as $player) {
            $this->subRoundWins[$player->id] = 0;
            $this->playerDecks[] = new PlayerDeck($player);
        }

        for ($i = 0; $i < $this->cardsToPlayer; $i++) {
            foreach ($this->playerDecks as $playerDeck) {
                $playerDeck->addCard($deck->getCard());
            }
        }

        $this->trump = $deck->getCard();

        if (self::ROUND_TYPE_GOLDEN === $this->type || self::ROUND_TYPE_DOWN === $this->type) {
            $this->dispatchRoundStartedEvent($firstPlayer);

            $this->startSubRound();
            return;
        }

        $this->state = self::STATE_STAKES;
        $this->stakesOrder = $players;

        $this->dispatchRoundStartedEvent($firstPlayer);
    }

    public function startSubRound(): void
    {
        $this->currentSubRound = new SubRound($this);
        $this->state = self::STATE_IN_PROGRESS;

        $this->eventDispatcher->dispatch(
            new SubRoundStarted($this->currentSubRound, $this->currentSubRound->getPlayersOrder()[0]),
            SubRoundStarted::NAME
        );
    }

    public function onSubRoundFinished(SubRoundFinished $event): void
    {
        $subRoundWinner = $event->getWinner();

        $this->subRoundWinner = $subRoundWinner;
        $this->subRoundWins[$subRoundWinner->id]++;
        $this->subRoundsPlayed++;

        if ($this->subRoundsPlayed === $this->cardsToPlayer) {
            $this->state = self::STATE_FINISHED;

            $this->unsubscribe();

            $roundResults = $this->calculateResults();
            \usort($roundResults, static function (RoundResult $a, RoundResult $b) {
                return $b->getScoreResult() <=> $a->getScoreResult();
            });

            defer(fn () => $this->eventDispatcher->dispatch(
                new RoundFinished($this, $roundResults[0]->getPlayerStake()->getPlayer(), $roundResults),
                RoundFinished::NAME
            ));

            return;
        }

        defer(fn () => $this->startSubRound());
    }

    /**
     * @return RoundResult[]
     */
    private function calculateResults(): array
    {
        $results = [];

        foreach ($this->subRoundWins as $playerId => $winsCount) {
            if ($this->type === self::ROUND_TYPE_GOLDEN) {
                $player = $this->getGame()->getRoom()->getPlayerById($playerId);
                $playerStake = new PlayerStake($player, $winsCount);

                $scoreResult = 10 * $winsCount;
            } elseif ($this->type === self::ROUND_TYPE_DOWN) {
                $player = $this->getGame()->getRoom()->getPlayerById($playerId);
                $playerStake = new PlayerStake($player, $winsCount);

                $scoreResult = -10 * $winsCount;
            } elseif ($this->type === self::ROUND_TYPE_DARKEN) {
                $playerStake = $this->getPlayerStakeByPlayerId($playerId);
                $stake = $playerStake->getStake();

                if ($winsCount === $stake) {
                    if (0 === $stake) {
                        $scoreResult = 5;
                    } else {
                        $scoreResult = 20 * $winsCount;
                    }
                } elseif ($winsCount > $stake) {
                    $scoreResult = $winsCount;
                } else {
                    $scoreResult = -20 * ($stake - $winsCount);
                }
            } else {
                $playerStake = $this->getPlayerStakeByPlayerId($playerId);
                $stake = $playerStake->getStake();

                if ($winsCount === $stake) {
                    if (0 === $stake) {
                        $scoreResult = 5;
                    } else {
                        $scoreResult = 10 * $winsCount;
                    }
                } elseif ($winsCount > $stake) {
                    $scoreResult = $winsCount;
                } else {
                    $scoreResult = -10 * ($stake - $winsCount);
                }
            }

            $results[] = new RoundResult($playerStake, $winsCount, $scoreResult);
        }

        return $results;
    }

    public function makeMove(Player $player, int $cardId, ?JokerMove $jokerMove): ?Player
    {
        if (self::STATE_IN_PROGRESS !== $this->state) {
            throw new \LogicException('Round is not IN_PROGRESS');
        }

        $deck = $this->getPlayerDeck($player);

        return $this->currentSubRound->makeMove($deck, $cardId, $jokerMove);
    }

    public function getPlayerAwaited(): ?Player
    {
        if (self::STATE_STAKES === $this->state) {
            return $this->stakesOrder[$this->stakesCount] ?? null;
        }

        if (self::STATE_IN_PROGRESS === $this->state) {
            return $this->currentSubRound->getAwaitedPlayer();
        }

        return null;
    }

    public function makeStake(Player $player, int $stake): void
    {
        if (self::STATE_STAKES !== $this->state || $this->stakesCount === \count($this->stakesOrder)) {
            throw new \LogicException('Stakes already done');
        }

        $playerWhichStakeAwaited = $this->stakesOrder[$this->stakesCount];
        if ($playerWhichStakeAwaited->id !== $player->id) {
            throw new \LogicException('Not your turn');
        }

        if ($stake > $this->cardsToPlayer) {
            throw new \LogicException('Stake cannot be bigger than cards count');
        }

        if (([] !== $this->playerStakes) && $this->cardsToPlayer === ($this->getSumOfStakes() + $stake)) {
            throw new \LogicException('Stake (+ previous stakes) cannot be equal to number of cards');
        }

        // TODO: Add lock

        $playerStake = new PlayerStake($player, $stake);
        $this->playerStakes[] = $playerStake;
        $this->stakesCount++;

        $this->eventDispatcher->dispatch(new PlayerStakeEvent($this, $playerStake), PlayerStakeEvent::NAME);

        if ($this->stakesCount === \count($this->stakesOrder)) {
            $this->startSubRound();
        }
    }

    private function dispatchRoundStartedEvent(Player $firstPlayer): void
    {
        $this->eventDispatcher->dispatch(new RoundStarted($this, $firstPlayer), RoundStarted::NAME);
    }

    private function getSumOfStakes(): int
    {
        $sum = 0;

        foreach ($this->playerStakes as $stake) {
            $sum += $stake->getStake();
        }

        return $sum;
    }

    public function getPlayerStakeByPlayerId(int $playerId): PlayerStake
    {
        foreach ($this->playerStakes as $playerStake) {
            if ($playerStake->getPlayer()->id === $playerId) {
                return $playerStake;
            }
        }

        throw new \LogicException('Player stake not found');
    }

    /**
     * @return PlayerDeck[]
     */
    public function getPlayerDecks(): array
    {
        return $this->playerDecks;
    }

    public function getPlayerDeck(Player $player): PlayerDeck
    {
        foreach ($this->playerDecks as $playerDeck) {
            if ($playerDeck->getPlayer()->id === $player->id) {
                return $playerDeck;
            }
        }

        throw new \LogicException('Player deck not found');
    }

    public function setPlayerDecks(array $playerDecks): void
    {
        $this->playerDecks = $playerDecks;
    }

    public function getTrump(): Card
    {
        return $this->trump;
    }

    public function setTrump(Card $card): void
    {
        $this->trump = $card;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getCardsToPlayer(): int
    {
        return $this->cardsToPlayer;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getState(): int
    {
        return $this->state;
    }

    /**
     * @return PlayerStake[]
     */
    public function getPlayerStakes(): array
    {
        return $this->playerStakes;
    }

    public function getCurrentSubRound(): SubRound
    {
        return $this->currentSubRound;
    }

    public function getSubRoundsPlayed(): int
    {
        return $this->subRoundsPlayed;
    }

    public function getSubRoundWinner(): ?Player
    {
        return $this->subRoundWinner;
    }

    /**
     * @return Player[]
     */
    public function getStakesOrder(): array
    {
        return $this->stakesOrder;
    }

    public function getGame(): Game
    {
        return $this->game;
    }
}