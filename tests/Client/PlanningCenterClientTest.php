<?php

namespace App\Test\Client;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Http\Message\RequestInterface;
use App\Client\PlanningCenterClient;
use App\Client\WebClientFactory;
use App\Client\WebClientFactoryInterface;
use App\Contact\Contact;

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
    private const PERSON_MEMBERSHIP = 'Member';
    private const PERSON_GENDER = 'M';
    private const PERSON_CREATED = '2016-02-19T02:00:00Z';
    private const PERSON_UPDATED = '2017-03-19T03:30:00Z';
    private const DATE_FORMAT = 'Y-m-d\TH:i:s\Z';

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
                        'name' => self::LIST_NAME
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
                        'membership' => self::PERSON_MEMBERSHIP,
                        'gender' => self::PERSON_GENDER,
                        'created_at' => self::PERSON_CREATED,
                        'updated_at' => self::PERSON_UPDATED,
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

        $result = $this->target->getContactsForList(self::LIST_NAME);

        $this->assertCount(1, $result);

        /** @var Contact $contact */
        $contact = $result[0];

        $this->assertEquals(self::PERSON_FIRST, $contact->firstName);
        $this->assertEquals(self::PERSON_LAST, $contact->lastName);
        $this->assertEquals(self::EMAIL, $contact->email);
        $this->assertEquals(self::PERSON_CREATED, $contact->createdAt->format(self::DATE_FORMAT));
        $this->assertEquals(self::PERSON_UPDATED, $contact->updatedAt->format(self::DATE_FORMAT));

        /** @var RequestInterface $request */
        $listRequest = $this->webHistory[0]['request'];
        $peopleRequest = $this->webHistory[1]['request'];

        $this->assertEquals('api.planningcenteronline.com', $peopleRequest->getUri()->getHost());
        $this->assertEquals(sprintf('/people/v2/lists/%d/people', self::LIST_ID), $peopleRequest->getUri()->getPath());
        $this->assertEquals('include=emails', urldecode($peopleRequest->getUri()->getQuery()));

    }
}
