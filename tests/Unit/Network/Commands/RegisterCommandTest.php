<?php
declare(strict_types=1);

namespace App\Tests\Unit\Network\Commands;

use App\Network\Client;
use App\Network\Commands\RegisterCommand;
use App\Tests\TestCase;

class RegisterCommandTest extends TestCase
{
    protected bool $transaction = true;

    public function testSuccess(): void
    {
        $command = new RegisterCommand();
        $client = new Client(0);

        $res = $command->handle($client, [
            'email' => 'test@mail.com',
            'password' => '123456',
            'name' => 'test'
        ]);

        $this->assertNotNull($res->id);
        $this->assertEquals('test@mail.com', $res->email);
        $this->assertEquals('test', $res->name);
        $this->assertTrue(\password_verify('123456', $res->password));
    }
}
