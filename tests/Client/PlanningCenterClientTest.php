<?php

namespace SyncTest\Client;

use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Sync\Client\PlanningCenterClient;
use Sync\Client\WebClientFactory;
use Sync\Contact\Contact;

class PlanningCenterClientTest extends MockeryTestCase
{
    private const APP_ID = 'id';
    private const APP_SECRET = 'secret';
    private const EMAIL = 'foo@bar';
    private const EMAIL_ID = 1;
    private const PERSON_ID = 2;
    private const PERSON_FIRST = 'Joe';
    private const PERSON_LAST = 'Smith';

    /** @var WebClientFactoryInterface **/
    private $webClientFactory;

    /** @var MockHandler **/
    private $webHandler;

    /** @var array **/
    private $webHistory = [];

    /** @var PlanningCenterClient **/
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

    public function test_getEmailFromPerson()
    {
        $personMock = [
            'relationships' => [
                'emails' => [
                    'data' => [
                        0 => [
                            'id' => self::EMAIL_ID,
                        ],
                    ],
                ],
            ],
        ];

        $emailMapMock = [self::EMAIL_ID => self::EMAIL];

        $result = $this->target->getEmailFromPerson($personMock, $emailMapMock);

        $this->assertEquals(self::EMAIL, $result);
    }

    public function test_getMembers()
    {
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
                    'id' => 456,
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

        $result = $this->target->getMembers();

        $this->assertCount(1, $result);

        /** @var Contact $contact **/
        $contact = $result[0];

        $this->assertEquals(self::PERSON_FIRST, $contact->firstName);
        $this->assertEquals(self::PERSON_LAST, $contact->lastName);
        $this->assertEquals(self::EMAIL, $contact->email);

        /** @var RequestInterface $request **/
        $request = $this->webHistory[0]['request'];

        $this->assertEquals('api.planningcenteronline.com', $request->getUri()->getHost());
        $this->assertEquals('/people/v2/people', $request->getUri()->getPath());
        $this->assertEquals('include=emails&where[child]=0&where[membership]=Member', urldecode($request->getUri()->getQuery()));
    }
}
