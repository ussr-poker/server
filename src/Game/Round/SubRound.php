<?php
declare(strict_types=1);

namespace App\Game\Round;

use App\Game\Cards\Card;
use App\Game\Events\SubRoundFinished;
use App\Game\Events\PlayerMove as PlayerMoveEvent;
use App\Game\Player;
use Psr\EventDispatcher\EventDispatcherInterface;

class SubRound
{
    private Round $round;

    private int $number;

    /**
     * @var Player[]
     */
    private array $playersOrder;

    /**
     * @var PlayerMove[]
     */
    private array $playerMoves = [];

    private int $movesCount = 0;

    private Card $trump;

    /**
     * @var EventDispatcherInterface|\Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    private EventDispatcherInterface $eventDispatcher;

    /**
     * SubRound constructor.
     * @param Round $round
     */
    public function __construct(Round $round)
    {
        $this->round = $round;
        $this->number = $round->getSubRoundsPlayed() + 1;
        $this->trump = $round->getTrump();
        $this->eventDispatcher = $round->getGame()->getEventDispatcher();

        $players = $round->getGame()->getRoom()->getPlayers();
        $firstPlayer = $round->getSubRoundWinner();
        if (null !== $firstPlayer) {
            $this->playersOrder = \getPlayersOrder($firstPlayer, $players);
        } else {
            $this->playersOrder = $round->getStakesOrder();
        }
    }

    public function getAwaitedPlayer(): ?Player
    {
        return $this->playersOrder[$this->movesCount] ?? null;
    }

    public function getMovesCount(): int
    {
        return $this->movesCount;
    }

    public function getMovesRemain(): int
    {
        return \count($this->playersOrder) - $this->movesCount;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * @return PlayerMove[]
     */
    public function getPlayerMoves(): array
    {
        return $this->playerMoves;
    }

    public function makeMove(PlayerDeck $playerDeck, int $cardId, ?JokerMove $jokerMove = null): ?Player
    {
        $awaitedPlayer = $this->getAwaitedPlayer();
        if (null === $awaitedPlayer) {
            throw new \LogicException('SubRound finished');
        }

        $player = $playerDeck->getPlayer();

        if ($player->id !== $awaitedPlayer->id) {
            throw new \LogicException('Not your turn');
        }

        // TODO: Add Lock

        $card = $playerDeck->getCard($cardId);
        if ($card->isJoker() && null === $jokerMove) {
            throw new \LogicException('Missing jokerMove');
        }

        if (0 < $this->movesCount && 1 < $playerDeck->size()) {
            $firstMove = $this->playerMoves[0];
            $firstCard = $firstMove->getCard();
            $firstCardSuit = $firstCard->getSuit();

            $playerCardSuit = $card->getSuit();
            if ($playerCardSuit !== $firstCardSuit && $playerDeck->hasCardWithSuit($firstCardSuit)) {
                throw new \LogicException('You have a card with needed suit');
            }

            if (
                $firstCard->isJoker() &&
                JokerMove::MODE_HIGHEST === $firstMove->getJokerMove()->getMode() &&
                $playerDeck->hasHigherCardWithSameSuit($card)
            ) {
                throw new \LogicException('You have a higher card with needed suit');
            }
        }

        $playerDeck->removeCard($cardId);

        $playerMove = new PlayerMove($player, $card, $jokerMove);
        $this->playerMoves[] = $playerMove;
        $this->movesCount++;

        $this->eventDispatcher->dispatch(new PlayerMoveEvent($this, $playerMove, $cardId), PlayerMoveEvent::NAME);

        if ($this->movesCount === \count($this->playersOrder)) {
            $playerMoves = $this->playerMoves;
            \usort($playerMoves, [$this, 'sortWinnerCallback']);

            $winner = $playerMoves[0]->getPlayer();
            go(fn() => $this->eventDispatcher->dispatch(new SubRoundFinished($this, $winner), SubRoundFinished::NAME));

            return $winner;
        }

        return null;
    }

    private function sortWinnerCallback(PlayerMove $a, PlayerMove $b): int
    {
        $cardA = $a->getCard();
        $cardB = $b->getCard();

        if ($cardA->isJoker()) {
            $jokerMove = $a->getJokerMove();

            return $this->getJokerWinner($jokerMove, $cardB);
        }

        if ($cardB->isJoker()) {
            $jokerMove = $b->getJokerMove();

            return $this->getJokerWinner($jokerMove, $cardA) === 1 ? -1 : 1;
        }

        // When trump is joker, there is no trump in the game
        if (!$this->trump->isJoker()) {
            // Card A is trump, Card B is not; Card A - won
            if ($cardA->getSuit() === $this->trump->getSuit() && $cardB->getSuit() !== $this->trump->getSuit()) {
                return -1;
            }

            // Card B is trump, Card A is not; Card B - won
            if ($cardB->getSuit() === $this->trump->getSuit() && $cardA->getSuit() !== $this->trump->getSuit()) {
                return 1;
            }
        }

        // Card A and Card B having the same suit
        if ($cardA->getSuit() === $cardB->getSuit()) {
            return $cardB->getValue() <=> $cardA->getValue();
        }

        // Card A and Card B having different suit; Card A - won
        return -1;
    }

    private function getJokerWinner(JokerMove $jokerMove, Card $cardB): int
    {
        switch ($jokerMove->getMode()) {
            case JokerMove::MODE_LOWEST:
                // Joker suit is different from Card B suit
                if ($jokerMove->getSuit() !== $cardB->getSuit()) {
                    // Card B is trump; Card B - won
                    if ($cardB->getSuit() === $this->trump->getSuit()) {
                        return 1;
                    }

                    // Card B is not trump; Card A - won
                    return -1;
                }

                // Joker suit the same with Card B suit; Card B - won
                return 1;
            case JokerMove::MODE_HIGHEST:
                // joker is a highest trump; Card A - won
                if ($jokerMove->getSuit() === $this->trump->getSuit()) {
                    return -1;
                }

                // Card B is trump, joker is not; Card B - won
                if ($cardB->getSuit() === $this->trump->getSuit()) {
                    return 1;
                }

                // Joker - Won
                return -1;
        }

        throw new \LogicException('Unknown joker state');
    }

    /**
     * @return Player[]
     */
    public function getPlayersOrder(): array
    {
        return $this->playersOrder;
    }

    public function getRound(): Round
    {
        return $this->round;
    }
}