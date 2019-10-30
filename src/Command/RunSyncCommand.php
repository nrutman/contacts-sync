<?php

namespace App\Command;

use App\Client\GoogleClient;
use App\Client\PlanningCenterClient;
use App\Contact\Contact;
use Carbon\Carbon;
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

    /** @var SymfonyStyle */
    protected $io;

    /** @var PlanningCenterClient */
    protected $planningCenterClient;

    public function __construct(
        GoogleClient $googleClient,
        PlanningCenterClient $planningCenterClient
    ) {
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

        $listContacts = [];

        $this->log('Retrieving lists from Planning Center...');

        $this->io->progressStart(count($lists));
        foreach ($lists as $list) {
            $listContacts[$list] = $this->planningCenterClient->getContactsForList($list);
            $this->io->progressAdvance();
        }
        $this->io->progressFinish();

        $this->log('Done!');

        $this->io->table([
            'List',
            'Contacts',
        ], array_map(static function ($contacts, $listName) {
            return [$listName, count($contacts)];
        }, $listContacts, array_keys($listContacts)));

        foreach ($listContacts as $listName => $contacts) {
            $this->syncContactsToGoogleGroup($this->googleClient, $listName, $contacts, $input->getOption('dry-run'));
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

        $groupMembers = $client->getGroupMembers($groupId);

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
        $timestamp = (new Carbon())->format('H:i:s');
        $this->io->writeln(sprintf(' <info>[%s]</info> %s', $timestamp, $message));
    }
}
