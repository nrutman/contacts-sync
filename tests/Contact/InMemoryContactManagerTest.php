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

        self::assertCount(2, $list1);
        self::assertCount(1, $list2);
    }

    public function test_emptyInput(): void
    {
        $target = new InMemoryContactManager([]);

        self::assertCount(0, $target->getContacts('any@list'));
    }

    public function test_nonExistentList(): void
    {
        $target = new InMemoryContactManager([
            ['email' => 'foo@bar', 'list' => 'list1@list'],
        ]);

        self::assertCount(0, $target->getContacts('nonexistent@list'));
    }

    public function test_caseInsensitiveListQuery(): void
    {
        $target = new InMemoryContactManager([
            ['email' => 'foo@bar', 'list' => 'LIST@DOMAIN'],
        ]);

        $result = $target->getContacts('list@domain');

        self::assertCount(1, $result);
        self::assertEquals('foo@bar', $result[0]->email);
    }
}
