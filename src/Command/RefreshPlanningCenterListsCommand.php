<?php

namespace App\Command;

use App\Client\PlanningCenter\PlanningCenterClient;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshPlanningCenterListsCommand extends Command
{
    /** @var string[] */
    private $lists;

    /** @var PlanningCenterClient */
    private $planningCenterClient;

    public function __construct(
        array $lists,
        PlanningCenterClient $planningCenterClient
    ) {
        $this->lists = $lists;
        $this->planningCenterClient = $planningCenterClient;

        parent::__construct();
    }

    public function configure()
    {
        $this->setName('planning-center:refresh');
        $this->setDescription('Refreshes a Planning Center list so it contains the most up-to-date contacts.');

        $this->addArgument('list-name', InputArgument::REQUIRED, 'The name of the list to refresh. Pass `all` to refresh all lists.');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $listName = $input->getArgument('list-name');

        if (!in_array($listName, array_merge(['all'], $this->lists))) {
            throw new Exception(sprintf('Unknown list specified: %s', $listName));
        }

        $lists = 'all' === $listName ? $this->lists : [$listName];

        foreach ($lists as $list) {
            $this->refreshList($list, $output);
        }

        return 0;
    }

    private function refreshList(string $listName, OutputInterface $output): void
    {
        $output->writeln(sprintf('Refreshing list <comment>%s</comment>', $listName));
        $this->planningCenterClient->refreshList($listName);
    }
}
