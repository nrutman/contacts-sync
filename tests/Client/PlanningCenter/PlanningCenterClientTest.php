<?php

namespace App\Tests\Client\PlanningCenter;

use App\Client\PlanningCenter\PlanningCenterClient;
use App\Client\WebClientFactory;
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
        $webClientFactory = new WebClientFactory(['handler' => $stack]);

        $this->target = new PlanningCenterClient(
            self::APP_ID,
            self::APP_SECRET,
            $webClientFactory,
        );
    }

    public function testGetContacts(): void
    {
        // fetch for the list
        $this->webHandler->append(
            new Response(
                200,
                [],
                json_encode(
                    [
                        'data' => [
                            [
                                'id' => self::LIST_ID,
                                'attributes' => [
                                    'name' => self::LIST_NAME,
                                ],
                            ],
                        ],
                    ],
                    JSON_THROW_ON_ERROR,
                ),
            ),
        );
        // fetch for the list's contacts
        $this->webHandler->append(
            new Response(
                200,
                [],
                json_encode(
                    [
                        'included' => [
                            [
                                'type' => 'Email',
                                'id' => self::EMAIL_ID,
                                'attributes' => [
                                    'address' => self::EMAIL,
                                ],
                            ],
                        ],
                        'data' => [
                            [
                                'id' => self::PERSON_ID,
                                'attributes' => [
                                    'first_name' => self::PERSON_FIRST,
                                    'last_name' => self::PERSON_LAST,
                                ],
                                'relationships' => [
                                    'emails' => [
                                        'data' => [
                                            [
                                                'id' => self::EMAIL_ID,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    JSON_THROW_ON_ERROR,
                ),
            ),
        );

        $result = $this->target->getContacts(self::LIST_NAME);

        self::assertCount(1, $result);

        $contact = $result[0];

        self::assertEquals(self::PERSON_FIRST, $contact->firstName);
        self::assertEquals(self::PERSON_LAST, $contact->lastName);
        self::assertEquals(self::EMAIL, $contact->email);

        self::assertCount(2, $this->webHistory);
    }

    public function testRefreshList(): void
    {
        $this->webHandler->append(
            new Response(
                200,
                [],
                json_encode(
                    [
                        'data' => [
                            [
                                'id' => self::LIST_ID,
                            ],
                        ],
                    ],
                    JSON_THROW_ON_ERROR,
                ),
            ),
        );

        $this->webHandler->append(new Response(204));

        $this->target->refreshList(self::LIST_NAME);

        self::assertCount(2, $this->webHistory);
    }

    public function testGetContactsWithPagination(): void
    {
        // fetch for the list
        $this->webHandler->append(
            new Response(
                200,
                [],
                json_encode(
                    [
                        'data' => [
                            [
                                'id' => self::LIST_ID,
                                'attributes' => [
                                    'name' => self::LIST_NAME,
                                ],
                            ],
                        ],
                    ],
                    JSON_THROW_ON_ERROR,
                ),
            ),
        );
        // first page with a "next" link
        $this->webHandler->append(
            new Response(
                200,
                [],
                json_encode(
                    [
                        'included' => [
                            [
                                'type' => 'Email',
                                'id' => self::EMAIL_ID,
                                'attributes' => [
                                    'address' => self::EMAIL,
                                ],
                            ],
                        ],
                        'data' => [
                            [
                                'id' => self::PERSON_ID,
                                'attributes' => [
                                    'first_name' => self::PERSON_FIRST,
                                    'last_name' => self::PERSON_LAST,
                                ],
                                'relationships' => [
                                    'emails' => [
                                        'data' => [
                                            [
                                                'id' => self::EMAIL_ID,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'links' => [
                            'next' => 'https://api.planningcenteronline.com/people/v2/lists/2/people?offset=25&per_page=25',
                        ],
                    ],
                    JSON_THROW_ON_ERROR,
                ),
            ),
        );
        // second page with no "next" link
        $this->webHandler->append(
            new Response(
                200,
                [],
                json_encode(
                    [
                        'included' => [
                            [
                                'type' => 'Email',
                                'id' => 99,
                                'attributes' => [
                                    'address' => 'page2@test.com',
                                ],
                            ],
                        ],
                        'data' => [
                            [
                                'id' => 4,
                                'attributes' => [
                                    'first_name' => 'Jane',
                                    'last_name' => 'Doe',
                                ],
                                'relationships' => [
                                    'emails' => [
                                        'data' => [
                                            [
                                                'id' => 99,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    JSON_THROW_ON_ERROR,
                ),
            ),
        );

        $result = $this->target->getContacts(self::LIST_NAME);

        self::assertCount(2, $result);
        self::assertEquals(self::EMAIL, $result[0]->email);
        self::assertEquals('page2@test.com', $result[1]->email);
        // list lookup + page 1 + page 2
        self::assertCount(3, $this->webHistory);
    }

    public function testGetContactsSkipsPersonWithoutEmail(): void
    {
        // fetch for the list
        $this->webHandler->append(
            new Response(
                200,
                [],
                json_encode(
                    [
                        'data' => [
                            [
                                'id' => self::LIST_ID,
                                'attributes' => [
                                    'name' => self::LIST_NAME,
                                ],
                            ],
                        ],
                    ],
                    JSON_THROW_ON_ERROR,
                ),
            ),
        );
        // person with no emails
        $this->webHandler->append(
            new Response(
                200,
                [],
                json_encode(
                    [
                        'included' => [],
                        'data' => [
                            [
                                'id' => self::PERSON_ID,
                                'attributes' => [
                                    'first_name' => self::PERSON_FIRST,
                                    'last_name' => self::PERSON_LAST,
                                ],
                                'relationships' => [
                                    'emails' => [
                                        'data' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    JSON_THROW_ON_ERROR,
                ),
            ),
        );

        $result = $this->target->getContacts(self::LIST_NAME);

        self::assertCount(0, $result);
    }

    public function testGetContactsListNotFound(): void
    {
        $this->webHandler->append(
            new Response(
                200,
                [],
                json_encode(
                    [
                        'data' => [],
                    ],
                    JSON_THROW_ON_ERROR,
                ),
            ),
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'The list `list@list.com` could not be found.',
        );

        $this->target->getContacts(self::LIST_NAME);
    }

    public function testRefreshListListNotFound(): void
    {
        $this->webHandler->append(
            new Response(
                200,
                [],
                json_encode(
                    [
                        'data' => [],
                    ],
                    JSON_THROW_ON_ERROR,
                ),
            ),
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'The list `list@list.com` could not be found.',
        );

        $this->target->refreshList(self::LIST_NAME);
    }
}
