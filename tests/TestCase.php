<?php
declare(strict_types=1);

namespace App\Tests;

use App\Database\Database;

require_once __DIR__ . '/../src/bootstrap.php';

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected bool $transaction = false;

    public function setUp(): void
    {
        parent::setUp();

        if ($this->transaction) {
            Database::getPdo()->beginTransaction();
        }
    }

    public function tearDown(): void
    {
        parent::tearDown();

        if ($this->transaction) {
            Database::getPdo()->rollBack();
        }
    }
}
