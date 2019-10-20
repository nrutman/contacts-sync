<?php

namespace Sync\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Sync\Client\PlanningCenterClient;
use Sync\Client\WebClientFactory;
use Sync\Config\ConfigParser;

class SyncToGoogleCommand extends Command
{
    protected static $defaultName = 'sync:to-google';

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

        $members = $planningCenterClient->getContacts('Member');
        $regulars = $planningCenterClient->getContacts('Regular Attender');
    }
}
