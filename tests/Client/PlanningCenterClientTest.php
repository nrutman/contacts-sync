<?php

namespace SyncTest\Client;

use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;
use Sync\Client\PlanningCenterClient;
use Sync\Client\WebClientFactory;

class PlanningCenterClientTest extends MockeryTestCase
{
    private const APP_ID = 'id';
    private const APP_SECRET = 'secret';
    private const EMAIL = 'foo@bar';
    private const EMAIL_ID = 1;

    /** @var WebClientFactoryInterface **/
    private $webClientFactory;

    /** @var PlanningCenterClient **/
    private $target;

    public function setUp(): void
    {
        $this->webClientFactory = new WebClientFactory();

        $this->target = new PlanningCenterClient(
            self::APP_ID,
            self::APP_SECRET,
            $this->webClientFactory
        );
    }

    public function test_getEmailFromPerson()
    {
        $personMock = [
            'relationships' => [
                'emails' => [
                    'data' => [
                        0 => [
                            'id' => self::EMAIL_ID,
                        ],
                    ],
                ],
            ],
        ];

        $emailMapMock = [self::EMAIL_ID => self::EMAIL];

        $result = $this->target->getEmailFromPerson($personMock, $emailMapMock);

        $this->assertEquals(self::EMAIL, $result);
    }
}
