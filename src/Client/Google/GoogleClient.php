<?php

namespace App\Client\Google;

use App\Client\ReadableListClientInterface;
use App\Client\WriteableListClientInterface;
use App\Contact\Contact;
use App\File\FileProvider;
use Google_Client;
use Google_Exception;
use Google_Service_Directory;
use Google_Service_Directory_Member;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class GoogleClient implements ReadableListClientInterface, WriteableListClientInterface
{
    private const TOKEN_FILENAME = 'google-token.json';

    /** @var Google_Client */
    protected $client;

    /** @var array */
    protected $configuration;

    /** @var string */
    protected $domain;

    /** @var FileProvider */
    protected $fileProvider;

    /** @var Google_Service_Directory */
    protected $service;

    /** @var string */
    protected $varPath;

    /**
     * @param Google_Client $client
     * @param GoogleServiceFactory $googleServiceFactory
     * @param FileProvider $fileProvider
     * @param array $googleConfiguration
     * @param string $googleDomain
     * @param string $varPath
     */
    public function __construct(
        Google_Client $client,
        GoogleServiceFactory $googleServiceFactory,
        FileProvider $fileProvider,
        array $googleConfiguration,
        string $googleDomain,
        string $varPath
    ) {
        $this->client = $client;
        $this->service = $googleServiceFactory->create($this->client);
        $this->fileProvider = $fileProvider;
        $this->configuration = $googleConfiguration;
        $this->domain = $googleDomain;
        $this->varPath = $varPath;
    }

    /**
     * Initializes a Google Client based on configuration.
     *
     * @see https://developers.google.com/admin-sdk/directory/v1/quickstart/php
     *
     * @return GoogleClient
     *
     * @throws FileNotFoundException
     * @throws Google_Exception
     */
    public function initialize(): self
    {
        $this->client->setApplicationName('Contacts Sync');
        $this->client->setScopes([
            Google_Service_Directory::ADMIN_DIRECTORY_GROUP,
            Google_Service_Directory::ADMIN_DIRECTORY_GROUP_MEMBER,
        ]);
        $this->client->setAuthConfig($this->configuration);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');
        $this->client->setHostedDomain($this->domain);

        // try to load the token from the saved file
        try {
            $this->client->setAccessToken($this->getToken());
        } catch (InvalidArgumentException $invalidArgumentException) {
            throw new InvalidGoogleTokenException($invalidArgumentException);
        }

        if ($this->client->isAccessTokenExpired()) {
            if (!$this->client->getRefreshToken()) {
                throw new InvalidGoogleTokenException();
            }
            $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
            $this->saveToken();
        }

        return $this;

//        $tokenPath = $this->getTokenPath();
//        if (file_exists($tokenPath)) {
//            $accessToken = \GuzzleHttp\json_decode(file_get_contents($tokenPath), true);
//            $this->client->setAccessToken($accessToken);
//        }
//
//        // If there is no previous token or it's expired.
//        if ($this->client->isAccessTokenExpired()) {
//            // Refresh the token if possible, else fetch a new one.
//            if ($this->client->getRefreshToken()) {
//                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
//            } else {
//                // Request authorization from the user.
//                $authUrl = $this->client->createAuthUrl();
//                printf("Open the following link in your browser:\n%s\n", $authUrl);
//                echo 'Enter verification code: ';
//                $authCode = trim(fgets(STDIN));
//
//                // Exchange authorization code for an access token.
//                $accessToken = $this->client->fetchAccessTokenWithAuthCode($authCode);
//                $this->client->setAccessToken($accessToken);
//
//                // Check to see if there was an error.
//                if (array_key_exists('error', $accessToken)) {
//                    throw new RuntimeException(implode(', ', $accessToken));
//                }
//            }
//            // Save the token to a file.
//            file_put_contents($tokenPath, \GuzzleHttp\json_encode($this->client->getAccessToken()));
//        }
    }

    /**
     * @return string
     */
    public function createAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    /**
     * @param string $authCode
     */
    public function setAuthCode(string $authCode)
    {
        $accessToken = $this->client->fetchAccessTokenWithAuthCode($authCode);
        $this->client->setAccessToken($accessToken);

        // Check to see if there was an error.
        if (array_key_exists('error', $accessToken)) {
            $exception = new RuntimeException(implode(', ', $accessToken));
            throw new InvalidGoogleTokenException($exception);
        }

        $this->saveToken();
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
        $this->service->members->delete($list, $contact->email);
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
     * @see getContacts
     *
     * @return Contact
     */
    private static function memberToContact(Google_Service_Directory_Member $member): Contact
    {
        $contact = new Contact();
        $contact->email = $member->getEmail();

        return $contact;
    }

    /**
     * @return array
     *
     * @throws FileNotFoundException
     */
    private function getToken(): array
    {
        return \GuzzleHttp\json_decode($this->fileProvider->getContents($this->getTokenPath()), true);
    }

    /**
     * @return string
     */
    private function getTokenPath(): string
    {
        return sprintf('%s/%s', $this->varPath, self::TOKEN_FILENAME);
    }

    private function saveToken(): void
    {
        $this->fileProvider->saveContents($this->getTokenPath(), \GuzzleHttp\json_encode($this->client->getAccessToken()));
    }
}
