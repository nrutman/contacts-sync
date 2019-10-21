<?php

namespace Sync\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Sync\Contact\Contact;
use function GuzzleHttp\Psr7\parse_query;

class PlanningCenterClient implements PlanningCenterClientInterface
{
    /** @var string * */
    protected $applicationId;

    /** @var string * */
    protected $applicationSecret;

    /** @var ClientInterface * */
    protected $webClient;

    /**
     * @param string $applicationId The application ID from Planning Center
     * @param string $applicationSecret The secret from PlanningCenter
     * @param WebClientFactoryInterface $webClientFactory
     */
    public function __construct(
        string $applicationId,
        string $applicationSecret,
        WebClientFactoryInterface $webClientFactory
    ) {
        $this->applicationId = $applicationId;
        $this->applicationSecret = $applicationSecret;
        $this->webClient = $webClientFactory->create([
            'auth' => [$this->applicationId, $this->applicationSecret],
            'base_uri' => 'https://api.planningcenteronline.com',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getMembers(?string $membershipStatus = 'Member'): array
    {
        $query = [
            'include' => 'emails',
            'where[child]' => false,
            'where[status]' => 'active',
        ];

        if ($membershipStatus !== null) {
            $query['where[membership]'] = $membershipStatus;
        }

        return $this->queryPeopleApi($query);
    }

    /**
     * Returns a mapping of email IDs to email addresses.
     *
     * @param array $emails
     *
     * @return array
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
     *
     * @param array $person
     * @param array $emailMap
     *
     * @return string|null
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
     * @param array $query
     *
     * @return Contact[]
     *
     * @throws GuzzleException
     */
    private function queryPeopleApi(array $query): array
    {
        $contacts = [];

        do {
            $response = $this->webClient->request('GET', '/people/v2/people', [
                'query' => $query,
            ]);

            $data = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);

            $emailMap = self::createEmailMap($data['included']);

            array_walk($data['data'], static function ($person) use ($emailMap, &$contacts) {
                $contacts[] = new Contact(
                    $person['attributes']['first_name'],
                    $person['attributes']['last_name'],
                    self::getEmailFromPerson($person, $emailMap)
                );
            });
        } while (
            // if a NEXT link exists, we want to parse the next query from it...otherwise we assign a blank array and
            // then check against that blank array to exit the loop (to maintain the array type of $query)
            ($query = isset($data['links']['next']) ? self::getQueryFromUrl($data['links']['next']) : []) !== []
        );

        return $contacts;
    }

    private static function getQueryFromUrl(string $url): array
    {
        return parse_query(parse_url($url, PHP_URL_QUERY));
    }
}
