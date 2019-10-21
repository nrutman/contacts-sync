<?php

namespace Sync\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Sync\Client\PlanningCenterClient;
use Sync\Client\WebClientFactory;
use Sync\Config\ConfigParser;

class RunSyncCommand extends Command
{
    protected static $defaultName = 'run';

    protected function configure()
    {
        $this->setDescription('Syncs contacts from PlanningCenter to Google Groups');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = ConfigParser::getConfiguration();

        $planningCenterClient = new PlanningCenterClient(
            $config['integrations']['planningCenter']['applicationId'],
            $config['integrations']['planningCenter']['secret'],
            new WebClientFactory()
        );

        $members = array_merge(
            $planningCenterClient->getMembers(),
            $planningCenterClient->getMembers('Regular Attender')
        );
        print_r($members);
    }
}
