<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class NotificationControllerTest extends WebTestCase
{
    public function testListUnauthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/notifications');

        self::assertResponseStatusCodeSame(401);
    }

    public function testSubscribeUnauthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/notifications/subscribe');

        self::assertResponseStatusCodeSame(401);
    }
}
