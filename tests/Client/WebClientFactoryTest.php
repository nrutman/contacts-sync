<?php

namespace App\Test\Client;

use App\Client\WebClientFactory;
use GuzzleHttp\ClientInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class WebClientFactoryTest extends MockeryTestCase
{
    private const URI = 'https://foobar';

    public function test_create()
    {
        $target = new WebClientFactory();
        $result = $target->create();

        $this->assertInstanceOf(ClientInterface::class, $result);
    }

    public function test_createWithConstructor()
    {
        $target = new WebClientFactory(['base_uri' => self::URI]);
        $result = $target->create();

        $this->assertEquals(self::URI, $result->getConfig('base_uri'));
    }

    public function test_createWithOverride()
    {
        $target = new WebClientFactory();
        $result = $target->create(['base_uri' => self::URI]);

        $this->assertEquals(self::URI, $result->getConfig('base_uri'));
    }
}
