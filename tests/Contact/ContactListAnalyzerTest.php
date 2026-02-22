<?php

namespace App\Tests\Contact;

use App\Contact\Contact;
use App\Contact\ContactListAnalyzer;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class ContactListAnalyzerTest extends MockeryTestCase
{
    public function test_diff(): void
    {
        $sharedContact = new Contact();
        $sharedContact->email = 'foo@bar';
        $extraContact = new Contact();
        $extraContact->email = 'boo@baz';
        $missingContact = new Contact();
        $missingContact->email = 'shaz@shuz';

        $target = new ContactListAnalyzer([$sharedContact, $missingContact], [$sharedContact, $extraContact]);

        self::assertCount(1, $target->getContactsToAdd());
        self::assertEquals($missingContact, $target->getContactsToAdd()[0]);
        self::assertCount(1, $target->getContactsToRemove());
        self::assertEquals($extraContact, $target->getContactsToRemove()[0]);
    }

    public function test_duplicates(): void
    {
        $contact = new Contact();
        $contact->email = 'foo@bar';
        $duplicate = new Contact();
        $duplicate->email = 'foo@bar';

        $target = new ContactListAnalyzer([$contact, $duplicate], []);

        self::assertCount(1, $target->getContactsToAdd());
    }

    public function test_caseInsensitiveEmailMatching(): void
    {
        $source = new Contact();
        $source->email = 'Foo@Bar';
        $dest = new Contact();
        $dest->email = 'foo@bar';

        $target = new ContactListAnalyzer([$source], [$dest]);

        self::assertCount(0, $target->getContactsToAdd());
        self::assertCount(0, $target->getContactsToRemove());
    }

    public function test_emptySourceList(): void
    {
        $dest = new Contact();
        $dest->email = 'foo@bar';

        $target = new ContactListAnalyzer([], [$dest]);

        self::assertCount(0, $target->getContactsToAdd());
        self::assertCount(1, $target->getContactsToRemove());
        self::assertEquals($dest, $target->getContactsToRemove()[0]);
    }

    public function test_emptyDestinationList(): void
    {
        $source = new Contact();
        $source->email = 'foo@bar';

        $target = new ContactListAnalyzer([$source], []);

        self::assertCount(1, $target->getContactsToAdd());
        self::assertEquals($source, $target->getContactsToAdd()[0]);
        self::assertCount(0, $target->getContactsToRemove());
    }

    public function test_bothListsEmpty(): void
    {
        $target = new ContactListAnalyzer([], []);

        self::assertCount(0, $target->getContactsToAdd());
        self::assertCount(0, $target->getContactsToRemove());
    }

    public function test_identicalLists(): void
    {
        $contact1 = new Contact();
        $contact1->email = 'foo@bar';
        $contact2 = new Contact();
        $contact2->email = 'baz@qux';

        $dest1 = new Contact();
        $dest1->email = 'foo@bar';
        $dest2 = new Contact();
        $dest2->email = 'baz@qux';

        $target = new ContactListAnalyzer([$contact1, $contact2], [$dest1, $dest2]);

        self::assertCount(0, $target->getContactsToAdd());
        self::assertCount(0, $target->getContactsToRemove());
    }

    public function test_duplicatesNotRemoved(): void
    {
        $contact = new Contact();
        $contact->email = 'foo@bar';
        $duplicate = new Contact();
        $duplicate->email = 'foo@bar';

        $target = new ContactListAnalyzer([$contact, $duplicate], [], false);

        self::assertCount(2, $target->getContactsToAdd());
    }
}
