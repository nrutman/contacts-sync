<?php

namespace App\Tests\Contact;

use App\Contact\Contact;
use App\Contact\ContactListAnalyzer;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ContactListAnalyzerTest extends MockeryTestCase
{
    #[DataProvider('diffProvider')]
    public function testDiff(
        array $sourceEmails,
        array $destEmails,
        int $expectedAdds,
        int $expectedRemoves,
        bool $removeDuplicates = true,
    ): void {
        $source = array_map(static function (string $email) {
            $c = new Contact();
            $c->email = $email;

            return $c;
        }, $sourceEmails);

        $dest = array_map(static function (string $email) {
            $c = new Contact();
            $c->email = $email;

            return $c;
        }, $destEmails);

        $target = new ContactListAnalyzer($source, $dest, $removeDuplicates);

        self::assertCount($expectedAdds, $target->getContactsToAdd());
        self::assertCount($expectedRemoves, $target->getContactsToRemove());
    }

    public static function diffProvider(): array
    {
        return [
            'basic add and remove' => [
                ['foo@bar', 'shaz@shuz'],
                ['foo@bar', 'boo@baz'],
                1,
                1,
            ],
            'removes duplicates from source' => [
                ['foo@bar', 'foo@bar'],
                [],
                1,
                0,
            ],
            'case insensitive matching' => [['Foo@Bar'], ['foo@bar'], 0, 0],
            'empty source removes all dest' => [[], ['foo@bar'], 0, 1],
            'empty dest adds all source' => [['foo@bar'], [], 1, 0],
            'both empty' => [[], [], 0, 0],
            'identical lists' => [
                ['foo@bar', 'baz@qux'],
                ['foo@bar', 'baz@qux'],
                0,
                0,
            ],
            'keeps duplicates when disabled' => [
                ['foo@bar', 'foo@bar'],
                [],
                2,
                0,
                false,
            ],
        ];
    }
}
