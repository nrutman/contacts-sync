<?php

namespace App\Tests\Command;

use App\Client\Google\GoogleClient;
use App\Client\PlanningCenter\PlanningCenterClient;
use App\Command\RunSyncCommand;
use App\Contact\Contact;
use App\Contact\InMemoryContactManager;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class RunSyncCommandTest extends MockeryTestCase
{
    private const LIST_ONE = 'list1@domain.com';
    private const LIST_TWO = 'list2@domain.com';

    /** @var GoogleClient|m\LegacyMockInterface|m\MockInterface */
    private $googleClient;

    /** @var PlanningCenterClient|m\LegacyMockInterface|m\MockInterface */
    private $planningCenterClient;

    /** @var InMemoryContactManager|m\LegacyMockInterface|m\MockInterface */
    private $inMemoryContactManager;

    public function setUp(): void
    {
        $this->googleClient = m::mock(GoogleClient::class);
        $this->planningCenterClient = m::mock(PlanningCenterClient::class);
        $this->inMemoryContactManager = m::mock(InMemoryContactManager::class);
    }

    public function testExecuteSuccessfulSync(): void
    {
        $sourceContact = $this->makeContact('source@test.com', 'John', 'Doe');
        $destContact = $this->makeContact('old@test.com');

        $this->googleClient
            ->shouldReceive('initialize')
            ->once()
            ->andReturn($this->googleClient);
        $this->planningCenterClient
            ->shouldReceive('getContacts')
            ->with(self::LIST_ONE)
            ->andReturn([$sourceContact]);
        $this->inMemoryContactManager
            ->shouldReceive('getContacts')
            ->with(self::LIST_ONE)
            ->andReturn([]);
        $this->googleClient
            ->shouldReceive('getContacts')
            ->with(self::LIST_ONE)
            ->andReturn([$destContact]);
        $this->googleClient
            ->shouldReceive('removeContact')
            ->once()
            ->with(self::LIST_ONE, $destContact);
        $this->googleClient
            ->shouldReceive('addContact')
            ->once()
            ->with(self::LIST_ONE, $sourceContact);

        $tester = $this->executeSyncCommand([self::LIST_ONE]);

        self::assertEquals(0, $tester->getStatusCode());
        self::assertStringContainsString(
            'source@test.com',
            $tester->getDisplay(),
        );
        self::assertStringContainsString('old@test.com', $tester->getDisplay());
    }

    public function testExecuteDryRun(): void
    {
        $sourceContact = $this->makeContact('source@test.com');
        $destContact = $this->makeContact('old@test.com');

        $this->googleClient
            ->shouldReceive('initialize')
            ->once()
            ->andReturn($this->googleClient);
        $this->planningCenterClient
            ->shouldReceive('getContacts')
            ->with(self::LIST_ONE)
            ->andReturn([$sourceContact]);
        $this->inMemoryContactManager
            ->shouldReceive('getContacts')
            ->with(self::LIST_ONE)
            ->andReturn([]);
        $this->googleClient
            ->shouldReceive('getContacts')
            ->with(self::LIST_ONE)
            ->andReturn([$destContact]);
        $this->googleClient->shouldNotReceive('removeContact');
        $this->googleClient->shouldNotReceive('addContact');

        $tester = $this->executeSyncCommand(
            [self::LIST_ONE],
            ['--dry-run' => true],
        );

        self::assertEquals(0, $tester->getStatusCode());
        self::assertStringContainsString('dry run', $tester->getDisplay());
    }

    public function testExecuteGoogleNotConfigured(): void
    {
        $this->googleClient
            ->shouldReceive('initialize')
            ->once()
            ->andThrow(new FileNotFoundException());

        $tester = $this->executeSyncCommand([self::LIST_ONE]);

        self::assertEquals(1, $tester->getStatusCode());
        self::assertStringContainsString(
            'cannot authenticate',
            $tester->getDisplay(),
        );
    }

    public function testExecuteMultipleLists(): void
    {
        $contact1 = $this->makeContact('a@test.com');
        $contact2 = $this->makeContact('b@test.com');

        $this->googleClient
            ->shouldReceive('initialize')
            ->once()
            ->andReturn($this->googleClient);

        $this->planningCenterClient
            ->shouldReceive('getContacts')
            ->with(self::LIST_ONE)
            ->andReturn([$contact1]);
        $this->inMemoryContactManager
            ->shouldReceive('getContacts')
            ->with(self::LIST_ONE)
            ->andReturn([]);
        $this->googleClient
            ->shouldReceive('getContacts')
            ->with(self::LIST_ONE)
            ->andReturn([$contact1]);

        $this->planningCenterClient
            ->shouldReceive('getContacts')
            ->with(self::LIST_TWO)
            ->andReturn([$contact2]);
        $this->inMemoryContactManager
            ->shouldReceive('getContacts')
            ->with(self::LIST_TWO)
            ->andReturn([]);
        $this->googleClient
            ->shouldReceive('getContacts')
            ->with(self::LIST_TWO)
            ->andReturn([$contact2]);

        $tester = $this->executeSyncCommand([self::LIST_ONE, self::LIST_TWO]);

        self::assertEquals(0, $tester->getStatusCode());
        self::assertStringContainsString('1/2', $tester->getDisplay());
        self::assertStringContainsString('2/2', $tester->getDisplay());
    }

    public function testExecuteMergesInMemoryContacts(): void
    {
        $pcContact = $this->makeContact('pc@test.com');
        $memContact = $this->makeContact('mem@test.com');

        $this->googleClient
            ->shouldReceive('initialize')
            ->once()
            ->andReturn($this->googleClient);
        $this->planningCenterClient
            ->shouldReceive('getContacts')
            ->with(self::LIST_ONE)
            ->andReturn([$pcContact]);
        $this->inMemoryContactManager
            ->shouldReceive('getContacts')
            ->with(self::LIST_ONE)
            ->andReturn([$memContact]);
        $this->googleClient
            ->shouldReceive('getContacts')
            ->with(self::LIST_ONE)
            ->andReturn([]);
        $this->googleClient->shouldReceive('addContact')->twice();

        $tester = $this->executeSyncCommand([self::LIST_ONE]);

        self::assertEquals(0, $tester->getStatusCode());
        self::assertStringContainsString('pc@test.com', $tester->getDisplay());
        self::assertStringContainsString('mem@test.com', $tester->getDisplay());
    }

    public function testExecuteMergeDeduplicatesContacts(): void
    {
        $pcContact = $this->makeContact('same@test.com');
        $memContact = $this->makeContact('same@test.com');

        $this->googleClient
            ->shouldReceive('initialize')
            ->once()
            ->andReturn($this->googleClient);
        $this->planningCenterClient
            ->shouldReceive('getContacts')
            ->with(self::LIST_ONE)
            ->andReturn([$pcContact]);
        $this->inMemoryContactManager
            ->shouldReceive('getContacts')
            ->with(self::LIST_ONE)
            ->andReturn([$memContact]);
        $this->googleClient
            ->shouldReceive('getContacts')
            ->with(self::LIST_ONE)
            ->andReturn([]);
        $this->googleClient->shouldReceive('addContact')->once();

        $tester = $this->executeSyncCommand([self::LIST_ONE]);

        self::assertEquals(0, $tester->getStatusCode());
    }

    public function testExecuteNoChangesNeeded(): void
    {
        $contact = $this->makeContact('shared@test.com');
        $destContact = $this->makeContact('shared@test.com');

        $this->googleClient
            ->shouldReceive('initialize')
            ->once()
            ->andReturn($this->googleClient);
        $this->planningCenterClient
            ->shouldReceive('getContacts')
            ->with(self::LIST_ONE)
            ->andReturn([$contact]);
        $this->inMemoryContactManager
            ->shouldReceive('getContacts')
            ->with(self::LIST_ONE)
            ->andReturn([]);
        $this->googleClient
            ->shouldReceive('getContacts')
            ->with(self::LIST_ONE)
            ->andReturn([$destContact]);
        $this->googleClient->shouldNotReceive('addContact');
        $this->googleClient->shouldNotReceive('removeContact');

        $tester = $this->executeSyncCommand([self::LIST_ONE]);

        self::assertEquals(0, $tester->getStatusCode());
    }

    private function executeSyncCommand(
        array $lists,
        array $options = [],
    ): CommandTester {
        $command = new RunSyncCommand(
            $lists,
            $this->googleClient,
            $this->planningCenterClient,
            $this->inMemoryContactManager,
        );

        $tester = new CommandTester($command);
        $tester->execute($options);

        return $tester;
    }

    private function makeContact(
        string $email,
        ?string $firstName = null,
        ?string $lastName = null,
    ): Contact {
        $contact = new Contact();
        $contact->email = $email;
        $contact->firstName = $firstName;
        $contact->lastName = $lastName;

        return $contact;
    }
}
