<?php
declare(strict_types=1);

namespace App\Game\Exceptions;

class NotYourTurnException extends \LogicException
{
    public function __construct()
    {
        parent::__construct('Not your turn');
    }
}
