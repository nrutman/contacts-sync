<?php

namespace Sync\Command;

use Carbon\Carbon;
use Google_Client;
use Google_Service_Directory_Member;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Sync\Client\GoogleClient;
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

    protected function configure(): void
    {
        $this->setDescription('Syncs contacts from PlanningCenter to Google Groups');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
        $config = ConfigParser::getConfiguration();

        $planningCenterClient = new PlanningCenterClient(
            $config['integrations']['planning_center'],
            new WebClientFactory()
        );

        $googleClient = new GoogleClient(
            new Google_Client(),
            $config['integrations']['google'],
            dirname(sprintf('%s/../../%s/1', __DIR__, $config['temp']['path']))
        );

        $this->log('Retrieving contacts from Planning Center...');

        $contacts = $planningCenterClient->getContacts();

        $this->log('Done!');

        $church = [];
        $men = [];
        $women = [];

        array_walk($contacts, static function (Contact $contact) use (&$church, &$men, &$women) {
            if (!in_array($contact->membership, ['Member', 'Regular Attender'])) {
                return;
            }

            $church[] = $contact;

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
            ['Members & Regular Attenders', count($church)],
            ['Men', count($men)],
            ['Women', count($women)],
        ]);

        /*
         * Members & Regular Attenders
         */

        $this->syncContactsToGoogleGroup(
            $googleClient,
            'church@provchurch.org',
            $church
        );

        /*
         * Men's Group
         */

        $this->syncContactsToGoogleGroup(
            $googleClient,
            'men@provchurch.org',
            $men
        );

        /*
         * Women's Group
         */

        $this->syncContactsToGoogleGroup(
            $googleClient,
            'women@provchurch.org',
            $women
        );
    }

    /**
     * @param GoogleClient $client
     * @param string $groupId
     * @param Contact[] $contacts
     * @param bool $isDryRun
     */
    private function syncContactsToGoogleGroup(
        GoogleClient $client,
        string $groupId,
        array $contacts,
        bool $isDryRun = false
    ): void {
        $this->log(sprintf('<info>Sycning %s</info>', $groupId));

        $groupMembers = $client->getGroupMembers($groupId);

        /** @var Contact[] $contactsMappedByEmail */
        $contactsMappedByEmail = [];
        foreach ($contacts as $contact) {
            $contactsMappedByEmail[$contact->email] = $contact;
        }

        $groupMembersMappedByEmail = [];
        foreach ($groupMembers as $member) {
            $groupMembersMappedByEmail[$member->getEmail()] = $member;
        }

        /** @var Contact[] $toAdd */
        $toAdd = [];
        /** @var Google_Service_Directory_Member[] $toRemove */
        $toRemove = [];

        // Find any emails in the contact list that are not in the Google group. These should be added.
        array_push($toAdd, ...array_filter($contacts, static function (Contact $contact) use ($groupMembersMappedByEmail) {
            return !isset($groupMembersMappedByEmail[$contact->email]);
        }));

        // Find any Google group members that are not in the contact list. These should be removed.
        array_push($toRemove, ...array_filter($groupMembers, static function (Google_Service_Directory_Member $member) use ($contactsMappedByEmail) {
            return !isset($contactsMappedByEmail[$member->getEmail()]);
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

    private function log(string $message): void
    {
        $timestamp = (new Carbon())->format('H:i:s');
        $this->io->writeln(sprintf(' <info>[%s]</info> %s', $timestamp, $message));
    }
}
