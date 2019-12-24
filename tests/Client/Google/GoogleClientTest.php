<?php

namespace App\Test\Client\Google;

use App\Client\Google\GoogleClient;
use App\Client\Google\GoogleServiceFactory;
use App\Client\Google\InvalidGoogleTokenException;
use App\Contact\Contact;
use App\File\FileProvider;
use Google_Client;
use Google_Service_Directory;
use Google_Service_Directory_Member;
use Google_Service_Directory_Members;
use Google_Service_Directory_Resource_Members;
use InvalidArgumentException;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;

class GoogleClientTest extends MockeryTestCase
{
    private const AUTH_CODE = 'AUTH:CODE{}';
    private const AUTH_URL = 'http://auth/url';
    private const CONFIGURATION = [
        'authentication' => self::GOOGLE_AUTH,
        'domain' => self::DOMAIN,
    ];
    private const DOMAIN = 'domain';
    private const GOOGLE_AUTH = ['authorize_me'];
    private const GROUP_ID = 'group@domain';
    private const MEMBER_EMAIL = 'foo@bar';
    private const TEMP_PATH = 'tmp/test';
    private const TOKEN_ARRAY = ['token' => 'foobar'];
    private const TOKEN_STRING = '{"token":"foobar"}';
    private const TOKEN_FILENAME = 'google-token.json'; // matches class implementation
    private const TOKEN_REFRESH = 'refresh.token';

    /** @var Google_Client|m\LegacyMockInterface|m\MockInterface */
    private $client;

    /** @var FileProvider|m\LegacyMockInterface|m\MockInterface */
    private $fileProvider;

    /** @var m\LegacyMockInterface|m\MockInterface|Google_Service_Directory */
    private $service;

    /** @var m\LegacyMockInterface|m\MockInterface|GoogleServiceFactory */
    private $serviceFactory;

    /** @var GoogleClient */
    private $target;

    public function setUp(): void
    {
        $this->client = m::mock(Google_Client::class);
        $this->fileProvider = m::mock(FileProvider::class);
        $this->service = m::mock(Google_Service_Directory::class);
        $this->serviceFactory = m::mock(GoogleServiceFactory::class, [
            'create' => $this->service,
        ]);

        $this->target = new GoogleClient(
            $this->client,
            $this->serviceFactory,
            $this->fileProvider,
            self::CONFIGURATION,
            self::DOMAIN,
            self::TEMP_PATH
        );
    }

    public function test_initialize(): void
    {
        $this->client
            ->shouldReceive([
                'setApplicationName' => null,
                'setScopes' => null,
                'setAuthConfig' => null,
                'setAccessType' => null,
                'setPrompt' => null,
                'setHostedDomain' => null,
                'isAccessTokenExpired' => false,
            ]);

        $this->client
            ->shouldReceive('setAccessToken')
            ->with(self::TOKEN_ARRAY);

        $this->fileProvider
            ->shouldReceive('getContents')
            ->with(self::TEMP_PATH.'/'.self::TOKEN_FILENAME)
            ->andReturn(self::TOKEN_STRING);

        $this->target->initialize();
    }

    public function test_initialize_invalidToken(): void
    {
        $this->client
            ->shouldReceive([
                'setApplicationName' => null,
                'setScopes' => null,
                'setAuthConfig' => null,
                'setAccessType' => null,
                'setPrompt' => null,
                'setHostedDomain' => null,
                'isAccessTokenExpired' => false,
            ]);

        $this->client
            ->shouldReceive('setAccessToken')
            ->with(self::TOKEN_ARRAY)
            ->andThrow(new InvalidArgumentException());

        $this->fileProvider
            ->shouldReceive('getContents')
            ->with(self::TEMP_PATH.'/'.self::TOKEN_FILENAME)
            ->andReturn(self::TOKEN_STRING);

        $this->expectException(InvalidGoogleTokenException::class);

        $this->target->initialize();
    }

    public function test_initialize_refreshToken(): void
    {
        $this->client
            ->shouldReceive([
                'setApplicationName' => null,
                'setScopes' => null,
                'setAuthConfig' => null,
                'setAccessType' => null,
                'setPrompt' => null,
                'setHostedDomain' => null,
                'isAccessTokenExpired' => true,
                'getRefreshToken' => self::TOKEN_REFRESH,
                'fetchAccessTokenWithRefreshToken' => null,
                'getAccessToken' => self::TOKEN_ARRAY,
            ]);

        $this->client
            ->shouldReceive('setAccessToken')
            ->with(self::TOKEN_ARRAY);

        $this->fileProvider
            ->shouldReceive('getContents')
            ->with(self::TEMP_PATH.'/'.self::TOKEN_FILENAME)
            ->andReturn(self::TOKEN_STRING);

        $this->fileProvider
            ->shouldReceive('saveContents')
            ->with(self::TEMP_PATH.'/'.self::TOKEN_FILENAME, m::any());

        $this->target->initialize();
    }

    public function test_initialize_invalidRefreshToken(): void
    {
        $this->client
            ->shouldReceive([
                'setApplicationName' => null,
                'setScopes' => null,
                'setAuthConfig' => null,
                'setAccessType' => null,
                'setPrompt' => null,
                'setHostedDomain' => null,
                'isAccessTokenExpired' => true,
                'getRefreshToken' => null,
            ]);

        $this->client
            ->shouldReceive('setAccessToken')
            ->with(self::TOKEN_ARRAY);

        $this->fileProvider
            ->shouldReceive('getContents')
            ->with(self::TEMP_PATH.'/'.self::TOKEN_FILENAME)
            ->andReturn(self::TOKEN_STRING);

        $this->expectException(InvalidGoogleTokenException::class);

        $this->target->initialize();
    }

    public function test_getContacts(): void
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

        $result = $this->target->getContacts(self::GROUP_ID);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        /** @var Contact $contact */
        $contact = $result[0];
        $this->assertInstanceOf(Contact::class, $contact);
        $this->assertEquals(self::MEMBER_EMAIL, $contact->email);
        $this->assertNull($contact->firstName);
        $this->assertNull($contact->lastName);
    }

    public function test_addContact(): void
    {
        $contact = new Contact();
        $contact->email = self::MEMBER_EMAIL;

        $this->service->members = m::mock(Google_Service_Directory_Resource_Members::class);
        $this->service->members
            ->shouldReceive('insert')
            ->with(self::GROUP_ID, m::type(Google_Service_Directory_Member::class));

        $this->target->addContact(self::GROUP_ID, $contact);
    }

    public function test_removeContact(): void
    {
        $contact = new Contact();
        $contact->email = self::MEMBER_EMAIL;

        $this->service->members = m::mock(Google_Service_Directory_Resource_Members::class);
        $this->service->members
            ->shouldReceive('delete')
            ->with(self::GROUP_ID, self::MEMBER_EMAIL);

        $this->target->removeContact(self::GROUP_ID, $contact);
    }

    public function test_createAuthUrl(): void
    {
        $this->client
            ->shouldReceive('createAuthUrl')
            ->andReturn(self::AUTH_URL);

        $result = $this->target->createAuthUrl();

        $this->assertEquals(self::AUTH_URL, $result);
    }

    public function test_setAuthCode(): void
    {
        $this->client
            ->shouldReceive('fetchAccessTokenWithAuthCode')
            ->with(self::AUTH_CODE)
            ->andReturn(self::TOKEN_ARRAY);

        $this->client
            ->shouldReceive('setAccessToken');

        $this->client
            ->shouldReceive('getAccessToken')
            ->andReturn(self::TOKEN_ARRAY);

        $this->fileProvider
            ->shouldReceive('saveContents');

        $this->target->setAuthCode(self::AUTH_CODE);
    }
}
