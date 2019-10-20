<?php

namespace Sync\Client;

use GuzzleHttp\ClientInterface;
use Sync\Contact\Contact;

class PlanningCenterClient
{
    /** @var string **/
    protected $applicationId;

    /** @var string **/
    protected $applicationSecret;

    /** @var ClientInterface **/
    protected $webClient;

    /**
     * @param string          $applicationId     The application ID from Planning Center
     * @param string          $applicationSecret The secret from PlanningCenter
     * @param ClientInterface $webClient         A Guzzle web client to use to make connections
     */
    public function __construct(
        string $applicationId,
        string $applicationSecret,
        WebClientFactory $webClientFactory
    ){
        $this->applicationId = $applicationId;
        $this->applicationSecret = $applicationSecret;
        $this->webClient = $webClientFactory->create([
            'auth' => [$this->applicationId, $this->applicationSecret],
            'base_uri' => 'https://api.planningcenteronline.com',
        ]);
    }

    /**
     * Returns a list of contacts from the Planning Center API
     * @param  [type] $membershipStatus [description]
     * @return Contacts[]
     */
    public function getContacts(?string $membershipStatus = null): array
    {
        $query = [
            'include' => 'emails',
            'where[child]' => false,
        ];

        if ($membershipStatus !== null) {
            $query['where[membership]'] = $membershipStatus;
        };

        $response = $this->webClient->request('GET', '/people/v2/people', [
            'query' => $query,
        ]);

        $data = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);

        $emailMap = [];
        foreach($data['included'] as $include) {
            if ($include['type'] === 'Email') {
                $emailMap[$include['id']] = $include['attributes']['address'];
            }
        }

        $contacts = array_map(function ($person) use ($emailMap) {
            return new Contact(
                $person['attributes']['first_name'],
                $person['attributes']['last_name'],
                self::getEmailFromPerson($person, $emailMap)
            );
        }, $data['data']);

        print_r($contacts);

        return $contacts;
    }

    /**
     * Returns an email from a Person array via API response
     * @param  array  $person
     * @param  array  $emailMap
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

}
