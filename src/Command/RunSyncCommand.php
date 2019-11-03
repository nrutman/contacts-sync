<?php

namespace App\Command;

use App\Client\Google\GoogleClient;
use App\Client\PlanningCenter\PlanningCenterClient;
use App\Contact\Contact;
use App\Contact\ContactListDiff;
use DateTime;
use Exception;
use Google_Service_Directory_Member;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RunSyncCommand extends Command
{
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
        parent::__construct('sync:run');
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

        if ($input->getOption('dry-run')) {
            $this->log('NOTE: This is a dry run. The destination list will not be altered!'.PHP_EOL);
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
     * @param GoogleClient $client
     * @param string $groupId
     * @param Contact[] $contacts
     * @param bool $isDryRun
     *
     * @throws Exception
     */
    private function syncContactsToGoogleGroup(
        GoogleClient $client,
        string $groupId,
        array $contacts,
        bool $isDryRun = false
    ): void {
        if ($isDryRun) {
            $this->log(sprintf('<info>Syncing %s (dry run)</info>', $groupId));
        } else {
            $this->log(sprintf('<info>Syncing %s</info>', $groupId));
        }

        $groupMembers = $client->getContacts($groupId);

        /** @var Contact[] $contactsMappedByEmail */
        $contactsMappedByEmail = [];
        foreach ($contacts as $contact) {
            $contactsMappedByEmail[strtolower($contact->email)] = $contact;
        }

        $groupMembersMappedByEmail = [];
        foreach ($groupMembers as $member) {
            $groupMembersMappedByEmail[strtolower($member->getEmail())] = $member;
        }

        /** @var Contact[] $toAdd */
        $toAdd = [];
        /** @var Google_Service_Directory_Member[] $toRemove */
        $toRemove = [];

        // Find any emails in the contact list that are not in the Google group. These should be added.
        array_push($toAdd, ...array_filter($contacts, static function (Contact $contact) use ($groupMembersMappedByEmail) {
            return !isset($groupMembersMappedByEmail[strtolower($contact->email)]);
        }));

        // Find any Google group members that are not in the contact list. These should be removed.
        array_push($toRemove, ...array_filter($groupMembers, static function (Google_Service_Directory_Member $member) use ($contactsMappedByEmail) {
            return !isset($contactsMappedByEmail[strtolower($member->getEmail())]);
        }));

        $this->io->text(sprintf('Adding %d contacts...', count($toAdd)));
        $this->io->listing(array_map(static function (Contact $contact) {
            return sprintf('%s %s (%s)', $contact->firstName, $contact->lastName, $contact->email);
        }, $toAdd));

        $this->io->text(sprintf('Removing %d contacts...', count($toRemove)));
        $this->io->listing(array_map(static function (Google_Service_Directory_Member $member) {
            return $member->getEmail();
        }, $toRemove));
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
