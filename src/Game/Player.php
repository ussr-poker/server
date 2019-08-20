<?php
declare(strict_types=1);

namespace App\Game;

use App\Network\Client;

class Player
{
    public int $id;

    public string $name = '(no name)';

    public bool $isOnline = false;

    public Client $client;

    public function __construct(Client $client)
    {
        $this->setClient($client);
    }

    public function setClient(Client $client): void
    {
        $this->client = $client;

        $this->id = $client->getUser()->id;
        $this->name = $client->getUser()->name;
    }

    public function getClient(): Client
    {
        return $this->client;
    }
}