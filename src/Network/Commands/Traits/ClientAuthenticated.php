<?php
declare(strict_types=1);

namespace App\Network\Commands\Traits;

use App\Network\Client;

trait ClientAuthenticated
{
    private function checkClientIsAuthenticated(Client $client): void
    {
        if (null === $client->getUser()) {
            throw new \InvalidArgumentException('Please log in');
        }
    }
}