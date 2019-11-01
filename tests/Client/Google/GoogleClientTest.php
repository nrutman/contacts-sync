<?php

namespace App\Test\Client\Google;

use Google_Client;
use Google_Service_Directory;
use Google_Service_Directory_Member;
use Google_Service_Directory_Members;
use Google_Service_Directory_Resource_Members;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use App\Client\Google\GoogleClient;
use App\Client\Google\GoogleServiceFactory;

class GoogleClientTest extends MockeryTestCase
{
    private const DOMAIN = 'domain';
    private const GOOGLE_AUTH = ['authorize_me'];
    private const GROUP_ID = 'group@domain';
    private const MEMBER_EMAIL = 'foo@bar';
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
                'setHostedDomain' => null,
            ]);

        $this->target = new GoogleClient(
            $this->client,
            $this->serviceFactory,
            $googleConfiguration,
            self::DOMAIN,
            self::TEMP_PATH
        );
    }

    public function test_getContactsForList(): void
    {
        $member = new Google_Service_Directory_Member();
        $member->setEmail(self::MEMBER_EMAIL);

        $this->service->members = m::mock(Google_Service_Directory_Resource_Members::class);
        $this->service->members
            ->shouldReceive('listMembers')
            ->with(self::GROUP_ID)
            ->andReturn(m::mock(Google_Service_Directory_Members::class, [
                'getMembers' => [$member],
            ]));

        $result = $this->target->getContactsForList(self::GROUP_ID);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(self::MEMBER_EMAIL, $result[0]->email);
    }
}
