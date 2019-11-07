<?php

namespace App\Command;

use App\Client\Google\GoogleClient;
use App\Client\PlanningCenter\PlanningCenterClient;
use App\Contact\ContactListDiff;
use DateTime;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class RunSyncCommand extends Command
{
    /** @var string */
    public static $defaultName = 'sync:run';

    /** @var GoogleClient */
    protected $googleClient;

    /** @var string[] */
    protected $lists;

    /** @var SymfonyStyle */
    protected $io;

    /** @var PlanningCenterClient */
    protected $planningCenterClient;

    /**
     * @param string[] $lists
     * @param GoogleClient $googleClient
     * @param PlanningCenterClient $planningCenterClient
     */
    public function __construct(
        array $lists,
        GoogleClient $googleClient,
        PlanningCenterClient $planningCenterClient
    ) {
        $this->lists = $lists;
        $this->googleClient = $googleClient;
        $this->planningCenterClient = $planningCenterClient;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Syncs contacts from PlanningCenter to Google Groups');
        $this->setHelp('Fetches contacts from a PlanningCenter list and syncs its members to a Google Group of the same name.');

        $this->addOption(
            'dry-run',
            'd',
            InputOption::VALUE_NONE,
            'Completes a dry run by showing output but without writing any data.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);

        try {
            $this->googleClient->initialize();
        } catch (FileNotFoundException $fileNotFoundException) {
            $this->io->error(sprintf('The Google Client cannot authenticate with your account. Please run the %s command to setup authentication.', ConfigureSyncCommand::$defaultName));
            return;
        }

        if ($input->getOption('dry-run')) {
            $this->io->success('NOTE: This is a dry run. The destination list will not be altered!'.PHP_EOL);
        }

        foreach ($this->lists as $listIndex => $list) {
            if ($listIndex > 0) {
                $output->writeln('');
            }

            // fetch the contacts from both lists
            $this->log(sprintf('<comment>Processing %s (%d/%d)</comment>'.PHP_EOL, $list, ($listIndex + 1), count($this->lists)));
            $sourceContacts = $this->planningCenterClient->getContacts($list);
            $destContacts = $this->googleClient->getContacts($list);

            // compute a diff
            $diff = new ContactListDiff($sourceContacts, $destContacts);

            $this->io->table(
                ['Source', 'Destination', 'To Remove', 'To Add'],
                [[count($sourceContacts), count($destContacts), count($diff->getContactsToRemove()), count($diff->getContactsToAdd())]]
            );

            // remove extra contacts
            foreach ($diff->getContactsToRemove() as $removeIndex => $contact) {
                $this->log(sprintf('Removing %s (%d/%d)', $contact->email, ($removeIndex + 1), count($diff->getContactsToRemove())));
                if (!$input->getOption('dry-run')) {
                    $this->googleClient->removeContact($list, $contact);
                }
            }

            // add missing contacts
            foreach ($diff->getContactsToAdd() as $addIndex => $contact) {
                $this->log(sprintf('Adding %s (%d/%d)', $contact->email, ($addIndex + 1), count($diff->getContactsToAdd())));
                if (!$input->getOption('dry-run')) {
                    $this->googleClient->addContact($list, $contact);
                }
            }
        }
    }

    /**
     * Custom logging function that adds a timestamp.
     *
     * @param string $message
     *
     * @throws Exception
     */
    private function log(string $message): void
    {
        $timestamp = (new DateTime())->format('H:i:s');
        $this->io->writeln(sprintf(' <info>[%s]</info> %s', $timestamp, $message));
    }
}
