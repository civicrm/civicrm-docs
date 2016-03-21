<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class NotifyControllerTest extends WebTestCase
{
    public function testNotify()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/Notify');
    }

}
