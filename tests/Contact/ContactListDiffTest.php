<?php

namespace App\Test\Contact;

use App\Contact\Contact;
use App\Contact\ContactListDiff;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class ContactListDiffTest extends MockeryTestCase
{
    public function test_diff()
    {
        $sharedContact = new Contact();
        $sharedContact->email = 'foo@bar';
        $extraContact = new Contact();
        $extraContact->email = 'boo@baz';
        $missingContact = new Contact();
        $missingContact->email = 'shaz@shuz';

        $target = new ContactListDiff([$sharedContact, $missingContact], [$sharedContact, $extraContact]);

        $this->assertCount(1, $target->getContactsToAdd());
        $this->assertEquals($missingContact, $target->getContactsToAdd()[0]);
        $this->assertCount(1, $target->getContactsToRemove());
        $this->assertEquals($extraContact, $target->getContactsToRemove()[0]);
    }
}
