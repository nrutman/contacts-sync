<?php

namespace Sync\Client;

use Carbon\Carbon;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Sync\Contact\Contact;
use function GuzzleHttp\Psr7\parse_query;

class PlanningCenterClient implements PlanningCenterClientInterface
{
    /** @var array */
    protected $configuration;

    /** @var ClientInterface */
    protected $webClient;

    /**
     * @param array $planningCenterConfiguration
     * @param WebClientFactoryInterface $webClientFactory
     */
    public function __construct(
        array $planningCenterConfiguration,
        WebClientFactoryInterface $webClientFactory
    ) {
        $this->configuration = $planningCenterConfiguration;
        $this->webClient = $webClientFactory->create([
            'auth' => [
                $planningCenterConfiguration['authentication']['application_id'],
                $planningCenterConfiguration['authentication']['secret'],
            ],
            'base_uri' => 'https://api.planningcenteronline.com',
        ]);
    }

    /**
     * {@inheritdoc}
     *
     * @throws GuzzleException
     */
    public function getContacts(): array
    {
        $query = [
            'include' => 'emails',
            'where[child]' => false,
            'where[status]' => 'active',
        ];

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
                $email = self::getEmailFromPerson($person, $emailMap);

                if (!$email) {
                    return;
                }

                $contacts[] = new Contact(
                    $person['attributes']['first_name'],
                    $person['attributes']['last_name'],
                    $email,
                    $person['attributes']['membership'],
                    $person['attributes']['gender'],
                    new Carbon($person['attributes']['created_at']),
                    new Carbon($person['attributes']['updated_at'])
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
