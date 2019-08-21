<?php
declare(strict_types=1);

namespace App\Network\Notifications;

interface NotificationInterface
{
    public function handle($data);

    public function getId(): int;
}
