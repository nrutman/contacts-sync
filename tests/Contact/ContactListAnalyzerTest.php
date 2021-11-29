<?php

namespace App\Test\Contact;

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
}
