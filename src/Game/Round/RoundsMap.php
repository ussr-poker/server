<?php
declare(strict_types=1);

namespace App\Game\Round;

class RoundsMap
{
    public const SHORT_ROUNDS_MAP_TWO_PLAYERS = [
        [1, 1, Round::ROUND_TYPE_REGULAR],
        [2, 2, Round::ROUND_TYPE_REGULAR],
        [3, 3, Round::ROUND_TYPE_REGULAR],
        [4, 4, Round::ROUND_TYPE_REGULAR],
        [5, 5, Round::ROUND_TYPE_REGULAR],
        [6, 6, Round::ROUND_TYPE_REGULAR],
        [7, 7, Round::ROUND_TYPE_REGULAR],
        [8, 8, Round::ROUND_TYPE_REGULAR],
        [9, 9, Round::ROUND_TYPE_REGULAR],
        [10, 10, Round::ROUND_TYPE_REGULAR],
        [11, 10, Round::ROUND_TYPE_REGULAR],
        [12, 9, Round::ROUND_TYPE_REGULAR],
        [13, 8, Round::ROUND_TYPE_REGULAR],
        [14, 7, Round::ROUND_TYPE_REGULAR],
        [15, 6, Round::ROUND_TYPE_REGULAR],
        [16, 5, Round::ROUND_TYPE_REGULAR],
        [17, 4, Round::ROUND_TYPE_REGULAR],
        [18, 3, Round::ROUND_TYPE_REGULAR],
        [19, 2, Round::ROUND_TYPE_REGULAR],
        [20, 1, Round::ROUND_TYPE_REGULAR],
        [21, 10, Round::ROUND_TYPE_GOLDEN],
        [22, 10, Round::ROUND_TYPE_WITHOUT_TRUMP],
        [23, 10, Round::ROUND_TYPE_DARKEN],
        [24, 10, Round::ROUND_TYPE_DARKEN],
    ];
}