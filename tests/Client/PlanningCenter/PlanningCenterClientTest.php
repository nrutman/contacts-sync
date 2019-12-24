<?php

namespace App\Test\Client;

use App\Client\PlanningCenter\PlanningCenterClient;
use App\Client\WebClientFactory;
use App\Client\WebClientFactoryInterface;
use App\Contact\Contact;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class PlanningCenterClientTest extends MockeryTestCase
{
    private const APP_ID = 'id';
    private const APP_SECRET = 'secret';
    private const EMAIL = 'foo@bar';
    private const EMAIL_ID = 1;
    private const LIST_ID = 2;
    private const LIST_NAME = 'list@list.com';
    private const PERSON_ID = 3;
    private const PERSON_FIRST = 'Joe';
    private const PERSON_LAST = 'Smith';

    /** @var WebClientFactoryInterface */
    private $webClientFactory;

    /** @var MockHandler */
    private $webHandler;

    /** @var array */
    private $webHistory = [];

    /** @var PlanningCenterClient */
    private $target;

    public function setUp(): void
    {
        $this->webHandler = new MockHandler();
        $stack = HandlerStack::create($this->webHandler);
        $stack->push(Middleware::history($this->webHistory));
        $this->webClientFactory = new WebClientFactory(['handler' => $stack]);

        $this->target = new PlanningCenterClient(
            self::APP_ID,
            self::APP_SECRET,
            $this->webClientFactory
        );
    }

    public function test_getContactsForList(): void
    {
        // fetch for the list
        $this->webHandler->append(
            new Response(200, [], \GuzzleHttp\json_encode([
                'data' => [[
                    'id' => self::LIST_ID,
                    'attributes' => [
                        'name' => self::LIST_NAME,
                    ],
                ]],
            ]))
        );
        // fetch for the list's contacts
        $this->webHandler->append(
            new Response(200, [], \GuzzleHttp\json_encode([
                'included' => [[
                    'type' => 'Email',
                    'id' => self::EMAIL_ID,
                    'attributes' => [
                        'address' => self::EMAIL,
                    ],
                ]],
                'data' => [[
                    'id' => self::PERSON_ID,
                    'attributes' => [
                        'first_name' => self::PERSON_FIRST,
                        'last_name' => self::PERSON_LAST,
                    ],
                    'relationships' => [
                        'emails' => [
                            'data' => [[
                                'id' => self::EMAIL_ID,
                            ]],
                        ],
                    ],
                ]],
            ]))
        );

        $result = $this->target->getContacts(self::LIST_NAME);

        $this->assertCount(1, $result);

        /** @var Contact $contact */
        $contact = $result[0];

        $this->assertEquals(self::PERSON_FIRST, $contact->firstName);
        $this->assertEquals(self::PERSON_LAST, $contact->lastName);
        $this->assertEquals(self::EMAIL, $contact->email);

        $this->assertCount(2, $this->webHistory);
    }
}
