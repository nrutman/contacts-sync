<?php

namespace SyncTest\Client;

use Google_Client;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Sync\Client\GoogleClient;

class GoogleClientTest extends MockeryTestCase
{
    private const TEMP_PATH = 'tmp';

    /** @var Google_Client|m\LegacyMockInterface|m\MockInterface */
    private $client;

    /** @var GoogleClient */
    private $target;

    public function setUp(): void
    {
        $googleConfiguration = [];

        $this->client = m::mock(Google_Client::class);

        $this->target = new GoogleClient(
            $this->client,
            $googleConfiguration,
            self::TEMP_PATH
        );
    }
}
