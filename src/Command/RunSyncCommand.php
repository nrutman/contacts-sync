<?php

namespace Sync\Command;

use Carbon\Carbon;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Sync\Client\PlanningCenterClient;
use Sync\Client\WebClientFactory;
use Sync\Config\ConfigParser;
use Sync\Contact\Contact;

class RunSyncCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'run';

    /** @var SymfonyStyle */
    protected $io;

    protected function configure()
    {
        $this->setDescription('Syncs contacts from PlanningCenter to Google Groups');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $config = ConfigParser::getConfiguration();

        $planningCenterClient = new PlanningCenterClient(
            $config['integrations']['planning_center'],
            new WebClientFactory()
        );

        $this->log('Retrieving contacts from Planning Center...');

        $contacts = $planningCenterClient->getContacts();

        $this->log('Done!');

        $members = [];
        $men = [];
        $women = [];

        array_walk($contacts, static function (Contact $contact) use (&$members, &$men, &$women) {
            if (!in_array($contact->membership, ['Member', 'Regular Attender'])) {
                return;
            }

            $members[] = $contact;

            if ($contact->gender === 'M') {
                $men[] = $contact;
            } elseif ($contact->gender === 'F') {
                $women[] = $contact;
            }
        });

        $this->io->table([
            'Type',
            'Count',
        ], [
            ['Members & Regular Attenders', count($members)],
            ['Men', count($men)],
            ['Women', count($women)],
        ]);
    }

    private function log(string $message): void
    {
        $timestamp = (new Carbon())->format('H:i:s');
        $this->io->writeln(sprintf(' <info>[%s]</info> %s', $timestamp, $message));
    }
}
