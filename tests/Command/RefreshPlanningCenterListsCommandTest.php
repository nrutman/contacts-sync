<?php

namespace App\Tests\Command;

use App\Client\PlanningCenter\PlanningCenterClient;
use App\Command\RefreshPlanningCenterListsCommand;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;
use Symfony\Component\Console\Tester\CommandTester;

class RefreshPlanningCenterListsCommandTest extends MockeryTestCase
{
    private const LIST_ONE = 'list1@domain.com';
    private const LIST_TWO = 'list2@domain.com';

    /** @var PlanningCenterClient|m\LegacyMockInterface|m\MockInterface */
    private $planningCenterClient;

    public function setUp(): void
    {
        $this->planningCenterClient = m::mock(PlanningCenterClient::class);
    }

    public function test_execute_refreshSingleList(): void
    {
        $this->planningCenterClient
            ->shouldReceive('refreshList')
            ->once()
            ->with(self::LIST_ONE);

        $command = new RefreshPlanningCenterListsCommand(
            [self::LIST_ONE, self::LIST_TWO],
            $this->planningCenterClient
        );

        $tester = new CommandTester($command);
        $tester->execute(['list-name' => self::LIST_ONE]);

        self::assertEquals(0, $tester->getStatusCode());
        self::assertStringContainsString(self::LIST_ONE, $tester->getDisplay());
    }

    public function test_execute_refreshAllLists(): void
    {
        $this->planningCenterClient
            ->shouldReceive('refreshList')
            ->once()
            ->with(self::LIST_ONE);

        $this->planningCenterClient
            ->shouldReceive('refreshList')
            ->once()
            ->with(self::LIST_TWO);

        $command = new RefreshPlanningCenterListsCommand(
            [self::LIST_ONE, self::LIST_TWO],
            $this->planningCenterClient
        );

        $tester = new CommandTester($command);
        $tester->execute(['list-name' => 'all']);

        self::assertEquals(0, $tester->getStatusCode());
        self::assertStringContainsString(self::LIST_ONE, $tester->getDisplay());
        self::assertStringContainsString(self::LIST_TWO, $tester->getDisplay());
    }

    public function test_execute_unknownList(): void
    {
        $command = new RefreshPlanningCenterListsCommand(
            [self::LIST_ONE],
            $this->planningCenterClient
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unknown list specified: unknown@list');

        $tester = new CommandTester($command);
        $tester->execute(['list-name' => 'unknown@list']);
    }
}
