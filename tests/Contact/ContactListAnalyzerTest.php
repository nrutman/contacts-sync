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

        $this->assertCount(1, $target->getContactsToAdd());
        $this->assertEquals($missingContact, $target->getContactsToAdd()[0]);
        $this->assertCount(1, $target->getContactsToRemove());
        $this->assertEquals($extraContact, $target->getContactsToRemove()[0]);
    }

    public function test_duplicates(): void
    {
        $contact = new Contact();
        $contact->email = 'foo@bar';
        $duplicate = new Contact();
        $duplicate->email = 'foo@bar';

        $target = new ContactListAnalyzer([$contact, $duplicate], []);

        $this->assertCount(1, $target->getContactsToAdd());
    }
}
