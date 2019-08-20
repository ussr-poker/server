<?php
declare(strict_types=1);

namespace App\Network\Commands;

use App\Game\Game;
use App\Game\Round\PlayerMove;
use App\Game\Round\PlayerStake;
use App\Game\Round\Round;
use App\Game\ScoreBoard\ScoreBoard;
use App\Game\ScoreBoard\ScoreBoardEntry;
use App\Network\Client;
use App\Network\Commands\Traits\ClientAuthenticated;
use App\Network\Server;

class GetRoomCommand implements CommandInterface
{
    use ClientAuthenticated;

    private Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function handle(Client $client, $data): array
    {
        $this->checkClientIsAuthenticated($client);

        $roomId = (int)($data['roomId'] ?? 0);
        if (0 === $roomId) {
            throw new \InvalidArgumentException('Specify "roomId"');
        }

        $room = $this->server->getRoom($roomId);
        $game = $room->getGame();

        $players = [];
        foreach ($room->getPlayers() as $player) {
            $playerRes = ['id' => $player->id, 'name' => $player->name];

            if (Game::STATE_IN_PROGRESS === $game->getState()) {
                $currentRound = $game->getCurrentRound();
                $playerDeck = $currentRound->getPlayerDeck($player);
                $playerCards = $playerDeck->getCards();

                if ($player->id === $client->getUser()->id) {
                    $playerRes['cards'] = [];

                    foreach ($playerCards as $cardId => $playerCard) {
                        $playerRes['cards'][] = [
                            'id' => $cardId,
                            'suit' => $playerCard->getSuit()->getSuit(),
                            'value' => $playerCard->getValue()
                        ];
                    }
                } else {
                    $playerRes['cardsCount'] = \count($playerCards);
                }
            }

            $players[] = $playerRes;
        }

        return [
            'id' => $room->getId(),
            'playersNeeded' => $room->getPlayersNeeded(),
            'players' => $players,
            'game' => $this->getGameInfo($game)
        ];
    }

    private function getGameInfo(Game $game): array
    {
        $result = [];
        $result['state'] = $game->getState();
        $result['mode'] = $game->getMode();
        $result['deckSize'] = $game->getDeckSize();

        $scoreBoard = $game->getScoreBoard();
        $result['scoreBoard'] = $this->formatScoreBoard($scoreBoard);


        if (Game::STATE_IN_PROGRESS === $game->getState()) {
            $currentRound = $game->getCurrentRound();

            $result['currentRound']['number'] = $currentRound->getNumber();
            $result['currentRound']['state'] = $currentRound->getState();
            $result['currentRound']['type'] = $currentRound->getType();
            $result['currentRound']['cardsCount'] = $currentRound->getCardsToPlayer();
            $result['currentRound']['awaitedPlayerId'] = $currentRound->getPlayerAwaited()->id ?? null;
            $result['currentRound']['stakes'] = \array_map(static function (PlayerStake $playerStake): array {
                return [
                    'playerId' => $playerStake->getPlayer()->id,
                    'stake' => $playerStake->getStake()
                ];
            }, $currentRound->getPlayerStakes());

            if (\in_array($currentRound->getState(), [Round::STATE_IN_PROGRESS, Round::STATE_FINISHED], true)) {
                $result['currentRound']['playerMoves'] = \array_map(static function (PlayerMove $playerMove): array {
                    $card = $playerMove->getCard();
                    $jokerMove = $playerMove->getJokerMove();

                    if ($jokerMove) {
                        $jokerMoveArr = [
                            'mode' => $jokerMove->getMode(),
                            'suit' => $jokerMove->getSuit()->getSuit(),
                        ];
                    } else {
                        $jokerMoveArr = null;
                    }

                    return [
                        'playerId' => $playerMove->getPlayer()->id,
                        'suit' => $card->getSuit()->getSuit(),
                        'value' => $card->getValue(),
                        'jokerMove' => $jokerMoveArr
                    ];
                }, $currentRound->getCurrentSubRound()->getPlayerMoves());
            }

            if (Round::STATE_NEW !== $currentRound->getState()) {
                $trump = $currentRound->getTrump();

                $result['currentRound']['trump']['suit'] = $trump->getSuit()->getSuit();
                $result['currentRound']['trump']['value'] = $trump->getValue();
            }
        }

        return $result;
    }

    private function formatScoreBoard(ScoreBoard $scoreBoard): array
    {
        $result = [];

        foreach ($scoreBoard->getScores() as $boardEntry) {
            $result[] = [
                'round' => [
                    'number' => $boardEntry->getRound()->getNumber(),
                    'type' => $boardEntry->getRound()->getType(),
                    'cardsCount' => $boardEntry->getRound()->getCardsToPlayer(),
                ],
                'players' => $this->formatScoreBoardPlayers($boardEntry)
            ];
        }

        return $result;
    }

    private function formatScoreBoardPlayers(ScoreBoardEntry $entry): array
    {
        $result = [];

        foreach ($entry->getPlayers() as $player) {
            $result[] = [
                'id' => $player->getPlayer()->id,
                'stake' => $player->getStake(),
                'wins' => $player->getWins(),
                'score' => $player->getScore()
            ];
        }

        return $result;
    }
}