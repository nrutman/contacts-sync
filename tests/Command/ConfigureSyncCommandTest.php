<?php

namespace App\Tests\Command;

use App\Client\Google\GoogleClient;
use App\Client\Google\InvalidGoogleTokenException;
use App\Command\ConfigureSyncCommand;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;
use Symfony\Component\Console\Tester\CommandTester;

class ConfigureSyncCommandTest extends MockeryTestCase
{
    private const AUTH_URL = 'https://accounts.google.com/auth';
    private const AUTH_CODE = 'test-auth-code';
    private const DOMAIN = 'example.com';

    /** @var GoogleClient|m\LegacyMockInterface|m\MockInterface */
    private $googleClient;

    /** @var ConfigureSyncCommand */
    private $command;

    public function setUp(): void
    {
        $this->googleClient = m::mock(GoogleClient::class);

        $this->command = new ConfigureSyncCommand(
            $this->googleClient,
            self::DOMAIN
        );
    }

    public function testExecuteAlreadyConfigured(): void
    {
        $this->googleClient
            ->shouldReceive('initialize')
            ->once()
            ->andReturn($this->googleClient);

        $tester = new CommandTester($this->command);
        $tester->execute([]);

        self::assertEquals(0, $tester->getStatusCode());
        self::assertStringContainsString('already configured', $tester->getDisplay());
    }

    public function testExecuteAlreadyConfiguredWithForce(): void
    {
        $this->googleClient
            ->shouldReceive('initialize')
            ->once()
            ->andReturn($this->googleClient);

        $this->googleClient
            ->shouldReceive('createAuthUrl')
            ->once()
            ->andReturn(self::AUTH_URL);

        $this->googleClient
            ->shouldReceive('setAuthCode')
            ->once()
            ->with(self::AUTH_CODE);

        $tester = new CommandTester($this->command);
        $tester->setInputs([self::AUTH_CODE]);
        $tester->execute(['--force' => true]);

        self::assertEquals(0, $tester->getStatusCode());
        self::assertStringContainsString(self::AUTH_URL, $tester->getDisplay());
    }

    public function testExecuteNotConfiguredProvidesAuthCode(): void
    {
        $this->googleClient
            ->shouldReceive('initialize')
            ->once()
            ->andThrow(new InvalidGoogleTokenException());

        $this->googleClient
            ->shouldReceive('createAuthUrl')
            ->once()
            ->andReturn(self::AUTH_URL);

        $this->googleClient
            ->shouldReceive('setAuthCode')
            ->once()
            ->with(self::AUTH_CODE);

        $tester = new CommandTester($this->command);
        $tester->setInputs([self::AUTH_CODE]);
        $tester->execute([]);

        self::assertEquals(0, $tester->getStatusCode());
        self::assertStringContainsString(self::AUTH_URL, $tester->getDisplay());
        self::assertStringContainsString(self::DOMAIN, $tester->getDisplay());
    }

    public function testExecuteNotConfiguredEmptyAuthCode(): void
    {
        $this->googleClient
            ->shouldReceive('initialize')
            ->once()
            ->andThrow(new InvalidGoogleTokenException());

        $this->googleClient
            ->shouldReceive('createAuthUrl')
            ->once()
            ->andReturn(self::AUTH_URL);

        $tester = new CommandTester($this->command);
        $tester->setInputs(['']);
        $tester->execute([]);

        self::assertEquals(1, $tester->getStatusCode());
        self::assertStringContainsString('authentication code must be provided', $tester->getDisplay());
    }
}
