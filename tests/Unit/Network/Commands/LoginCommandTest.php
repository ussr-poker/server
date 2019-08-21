<?php
declare(strict_types=1);

namespace App\Tests\Unit\Network\Commands;

use App\Database\Database;
use App\Network\Client;
use App\Network\Commands\LoginCommand;
use App\Network\Commands\RegisterCommand;
use App\Tests\TestCase;

class LoginCommandTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Database::getPdo()->beginTransaction();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        Database::getPdo()->rollBack();
    }

    public function testSuccess(): void
    {
        $client = new Client(0);

        $command = new RegisterCommand();
        $command->handle($client, [
            'email' => 'test@mail.com',
            'password' => '123456',
            'name' => 'test'
        ]);

        $command = new LoginCommand();

        $user = $command->handle($client, ['username' => 'test', 'password' => '123456']);

        $this->assertEquals('test@mail.com', $user->email);
    }
}
