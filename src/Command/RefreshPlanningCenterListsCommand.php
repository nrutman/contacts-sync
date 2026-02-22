<?php

namespace App\Command;

use App\Client\PlanningCenter\PlanningCenterClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'planning-center:refresh',
    description: 'Refreshes a Planning Center list so it contains the most up-to-date contacts.',
),]
class RefreshPlanningCenterListsCommand extends Command
{
    /**
     * @param string[] $lists
     */
    public function __construct(
        private readonly array $lists,
        private readonly PlanningCenterClient $planningCenterClient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'list-name',
            InputArgument::REQUIRED,
            'The name of the list to refresh. Pass `all` to refresh all lists.',
        );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $listName = $input->getArgument('list-name');

        if (!in_array($listName, array_merge(['all'], $this->lists))) {
            throw new \Exception(sprintf('Unknown list specified: %s', $listName));
        }

        $lists = $listName === 'all' ? $this->lists : [$listName];

        foreach ($lists as $list) {
            $this->refreshList($list, $output);
        }

        return Command::SUCCESS;
    }

    private function refreshList(
        string $listName,
        OutputInterface $output,
    ): void {
        $output->writeln(
            sprintf('Refreshing list <comment>%s</comment>', $listName),
        );
        $this->planningCenterClient->refreshList($listName);
    }
}
