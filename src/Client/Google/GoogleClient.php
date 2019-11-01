<?php

namespace App\Client\Google;

use App\Client\ReadableListClientInterface;
use App\Client\WriteableListClientInterface;
use App\Contact\Contact;
use Google_Client;
use Google_Exception;
use Google_Service_Directory;
use Google_Service_Directory_Member;
use RuntimeException;

class GoogleClient implements ReadableListClientInterface, WriteableListClientInterface
{
    private const TOKEN_FILENAME = 'google-token.json';

    /** @var Google_Client */
    protected $client;

    /** @var array */
    protected $configuration;

    /** @var Google_Service_Directory */
    protected $service;

    /**
     * @param Google_Client $client
     * @param GoogleServiceFactory $googleServiceFactory
     * @param array $googleConfiguration
     * @param string $googleDomain
     * @param string $varPath
     *
     * @throws Google_Exception
     */
    public function __construct(
        Google_Client $client,
        GoogleServiceFactory $googleServiceFactory,
        array $googleConfiguration,
        string $googleDomain,
        string $varPath
    ) {
        $this->client = self::initializeClient($client, $googleConfiguration, $googleDomain, $varPath);
        $this->configuration = $googleConfiguration;
        $this->service = $googleServiceFactory->create($this->client);
    }

    /**
     * Initializes a Google Client based on configuration.
     *
     * @see https://developers.google.com/admin-sdk/directory/v1/quickstart/php
     *
     * @param Google_Client $client
     * @param array $configuration
     * @param string $domain
     * @param string $tempPath
     *
     * @return Google_Client
     *
     * @throws Google_Exception
     */
    protected static function initializeClient(Google_Client $client, array $configuration, string $domain, string $tempPath): Google_Client
    {
        $client->setApplicationName('Contacts Sync');
        $client->setScopes([
            Google_Service_Directory::ADMIN_DIRECTORY_GROUP,
            Google_Service_Directory::ADMIN_DIRECTORY_GROUP_MEMBER,
        ]);
        $client->setAuthConfig($configuration);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        $client->setHostedDomain($domain);

        // Load previously authorized token from a file, if it exists.
        // The file token.json stores the user's access and refresh tokens, and is
        // created automatically when the authorization flow completes for the first
        // time.
        $tokenPath = sprintf('%s/%s', $tempPath, self::TOKEN_FILENAME);
        if (file_exists($tokenPath)) {
            $accessToken = \GuzzleHttp\json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }

        // If there is no previous token or it's expired.
        if ($client->isAccessTokenExpired()) {
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                // Request authorization from the user.
                $authUrl = $client->createAuthUrl();
                printf("Open the following link in your browser:\n%s\n", $authUrl);
                echo 'Enter verification code: ';
                $authCode = trim(fgets(STDIN));

                // Exchange authorization code for an access token.
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);

                // Check to see if there was an error.
                if (array_key_exists('error', $accessToken)) {
                    throw new RuntimeException(implode(', ', $accessToken));
                }
            }
            // Save the token to a file.
            file_put_contents($tokenPath, \GuzzleHttp\json_encode($client->getAccessToken()));
        }

        return $client;
    }

    /**
     * {@inheritdoc}
     */
    public function getContacts(string $listName): array
    {
        return array_map('self::memberToContact', (array) $this->service->members->listMembers($listName)->getMembers());
    }

    /**
     * {@inheritdoc}
     */
    public function addContact(string $list, Contact $contact): void
    {
        $member = self::contactToMember($contact);
        $this->service->members->insert($list, $member);
    }

    /**
     * {@inheritdoc}
     */
    public function removeContact(string $list, Contact $contact): void
    {
        $member = self::contactToMember($contact);
        $this->service->members->delete($list, $member);
    }

    /**
     * @param Contact $contact
     *
     * @return Google_Service_Directory_Member
     */
    private static function contactToMember(Contact $contact): Google_Service_Directory_Member
    {
        $member = new Google_Service_Directory_Member();
        $member->setEmail($contact->email);

        return $member;
    }

    /**
     * @param Google_Service_Directory_Member $member
     *
     * @return Contact
     */
    private static function memberToContact(Google_Service_Directory_Member $member): Contact
    {
        $contact = new Contact();
        $contact->email = $member->getEmail();

        return $contact;
    }
}
