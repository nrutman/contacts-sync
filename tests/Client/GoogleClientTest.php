<?php

namespace SyncTest\Client;

use Google_Client;
use Google_Service_Directory;
use Google_Service_Directory_Member;
use Google_Service_Directory_Members;
use Google_Service_Directory_Resource_Members;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Sync\Client\GoogleClient;
use Sync\Client\GoogleServiceFactory;

class GoogleClientTest extends MockeryTestCase
{
    private const DOMAIN = 'domain';
    private const GOOGLE_AUTH = ['authorize_me'];
    private const GROUP_ID = 'group@domain';
    private const TEMP_PATH = 'tmp/test';

    /** @var Google_Client|m\LegacyMockInterface|m\MockInterface */
    private $client;

    /** @var m\LegacyMockInterface|m\MockInterface|Google_Service_Directory */
    private $service;

    /** @var m\LegacyMockInterface|m\MockInterface|GoogleServiceFactory */
    private $serviceFactory;

    /** @var GoogleClient */
    private $target;

    public function setUp(): void
    {
        $googleConfiguration = [
            'authentication' => self::GOOGLE_AUTH,
            'domain' => self::DOMAIN,
        ];
        $this->client = m::mock(Google_Client::class);
        $this->service = m::mock(Google_Service_Directory::class);
        $this->serviceFactory = m::mock(GoogleServiceFactory::class, [
            'create' => $this->service,
        ]);

        $this->client
            ->shouldReceive(
                'setApplicationName',
                'setScopes',
                'setAuthConfig',
                'setAccessType',
                'setPrompt'
            );
        $this->client
            ->shouldReceive([
                'isAccessTokenExpired' => false,
                'getAccessToken' => 'foobar-token',
            ]);

        $this->target = new GoogleClient(
            $this->client,
            $googleConfiguration,
            self::TEMP_PATH,
            $this->serviceFactory
        );
    }

    public function test_getGroupMembers(): void
    {
        $member = m::mock(Google_Service_Directory_Member::class);

        $this->service->members = m::mock(Google_Service_Directory_Resource_Members::class);
        $this->service->members
            ->shouldReceive('listMembers')
            ->with(self::GROUP_ID)
            ->andReturn(m::mock(Google_Service_Directory_Members::class, [
                'getMembers' => $member,
            ]));

        $result = $this->target->getGroupMembers(self::GROUP_ID);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals($member, $result[0]);
    }
}
