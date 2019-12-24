<?php

namespace App\Tests\Contact;

use App\Contact\InMemoryContactManager;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class InMemoryContactManagerTest extends MockeryTestCase
{
    public function test_getList(): void
    {
        $inMemoryContacts = [
            [
                'email' => 'foo@bar',
                'list' => 'list1@list',
            ], [
                'email' => 'bar@baz',
                'list' => ['list1@LIST', 'list2@list'],
            ],
        ];

        $target = new InMemoryContactManager($inMemoryContacts);

        $list1 = $target->getContacts('list1@list');
        $list2 = $target->getContacts('list2@list');

        $this->assertCount(2, $list1);
        $this->assertCount(1, $list2);
    }
}
