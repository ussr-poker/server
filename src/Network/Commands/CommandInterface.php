<?php
declare(strict_types=1);

namespace App\Network\Commands;

use App\Network\Client;

interface CommandInterface
{
    public function handle(Client $client, $data);
}