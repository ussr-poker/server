<?php
declare(strict_types=1);

namespace App\Tests\Unit\Network\Commands;

use App\Network\Client;
use App\Network\Commands\LoginCommand;
use App\Network\Commands\RegisterCommand;
use App\Network\Server;
use App\Tests\TestCase;

class LoginCommandTest extends TestCase
{
    protected bool $transaction = true;

    public function testSuccess(): void
    {
        $client = new Client(0);

        $command = new RegisterCommand();
        $command->handle($client, [
            'email' => 'test@mail.com',
            'password' => '123456',
            'name' => 'test'
        ]);

        $server = new Server();
        $command = new LoginCommand($server);

        $user = $command->handle($client, ['username' => 'test', 'password' => '123456']);

        $this->assertEquals('test@mail.com', $user->email);
    }
}
