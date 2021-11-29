<?php

namespace App\Client\PlanningCenter;

use App\Client\ReadableListClientInterface;
use App\Client\WebClientFactoryInterface;
use App\Contact\Contact;
use Exception;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\Request;
use function GuzzleHttp\Psr7\parse_query;

class PlanningCenterClient implements ReadableListClientInterface
{
    /** @var ClientInterface */
    protected $webClient;

    public function __construct(
        string $planningCenterAppId,
        string $planningCenterAppSecret,
        WebClientFactoryInterface $webClientFactory
    ) {
        $this->webClient = $webClientFactory->create([
            'auth' => [
                $planningCenterAppId,
                $planningCenterAppSecret,
            ],
            'base_uri' => 'https://api.planningcenteronline.com',
        ]);
    }

    /**
     * {@inheritdoc}
     *
     * @throws GuzzleException
     */
    public function getContacts(string $listName): array
    {
        // TODO Throw exception if the list can't be found (add @throws to interface)
        $response = $this->webClient->request('GET', '/people/v2/lists', [
            'query' => [
                'where[name]' => $listName,
            ],
        ]);

        $lists = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
        $list = array_filter($lists['data'], static function ($list) use ($listName) {
            return preg_match(sprintf('/^%s$/i', $listName), $list['attributes']['name']);
        });

        return $this->queryPeopleApi([
            'include' => 'emails',
        ], sprintf('/people/v2/lists/%d/people', array_shift($list)['id']));
    }

    /**
     * Refreshes a list so it contains the most up-to-date contacts.
     *
     * @throws GuzzleException
     * @throws Exception
     */
    public function refreshList(string $listName): void
    {
        $listResponse = $this->webClient->request(Request::METHOD_GET, '/people/v2/lists', [
            'query' => ['where[name]' => $listName],
        ]);

        $body = \GuzzleHttp\json_decode($listResponse->getBody()->getContents());

        if (empty($body->data)) {
            throw new Exception(sprintf('The list `%s` could not be found.', $listName));
        }

        $listId = $body->data[0]->id;

        $this->webClient->request(Request::METHOD_POST, sprintf('/people/v2/lists/%d/run', $listId));
    }

    /**
     * Returns a mapping of email IDs to email addresses.
     */
    private static function createEmailMap(array $emails): array
    {
        $map = [];

        foreach ($emails as $email) {
            if ($email['type'] === 'Email') {
                $map[$email['id']] = $email['attributes']['address'];
            }
        }

        return $map;
    }

    /**
     * Returns an email from a Person array via API response.
     */
    private static function getEmailFromPerson(array $person, array $emailMap): ?string
    {
        $list = $person['relationships']['emails'];

        if (!isset($list['data']) || count($list['data']) === 0) {
            return null;
        }

        $emailId = $list['data'][0]['id'];

        return $emailMap[$emailId];
    }

    /**
     * Queries the Planning Center People API based on the query provided.
     *
     * @see https://developer.planning.center/docs/#/apps/people/2019-01-14/vertices/person
     *
     * @return Contact[]
     *
     * @throws GuzzleException
     */
    private function queryPeopleApi(array $query, string $url = '/people/v2/people'): array
    {
        $contacts = [];

        do {
            $response = $this->webClient->request('GET', $url, [
                'query' => $query,
            ]);

            $data = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);

            $emailMap = self::createEmailMap($data['included']);

            array_walk($data['data'], static function ($person) use ($emailMap, &$contacts) {
                $email = self::getEmailFromPerson($person, $emailMap);

                if (!$email) {
                    return;
                }

                $contact = new Contact();
                $contact->firstName = $person['attributes']['first_name'];
                $contact->lastName = $person['attributes']['last_name'];
                $contact->email = $email;
                $contacts[] = $contact;
            });
        } while (
            // if a NEXT link exists, we want to parse the next query from it...otherwise we assign a blank array and
            // then check against that blank array to exit the loop (to maintain the array type of $query)
            ($query = isset($data['links']['next']) ? self::getQueryFromUrl($data['links']['next']) : []) !== []
        );

        return $contacts;
    }

    /**
     * Extracts a querystring array from a url string.
     */
    private static function getQueryFromUrl(string $url): array
    {
        return parse_query(parse_url($url, PHP_URL_QUERY));
    }
}
