<?php

namespace App\Client\Google;

use App\Client\ReadableListClientInterface;
use App\Client\WriteableListClientInterface;
use App\Contact\Contact;
use App\File\FileProvider;
use Google\Client;
use Google\Exception as GoogleException;
use Google\Service\Directory;
use Google\Service\Directory\Member;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class GoogleClient implements
    ReadableListClientInterface,
    WriteableListClientInterface
{
    private const TOKEN_FILENAME = 'google-token.json';

    protected Client $client;
    protected array $configuration;
    protected string $domain;
    protected FileProvider $fileProvider;
    protected Directory $service;
    protected string $varPath;

    /**
     * @param array<string, mixed> $googleConfiguration
     */
    public function __construct(
        Client $client,
        GoogleServiceFactory $googleServiceFactory,
        FileProvider $fileProvider,
        array $googleConfiguration,
        string $googleDomain,
        string $varPath,
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
     * @throws FileNotFoundException
     * @throws GoogleException
     */
    public function initialize(): self
    {
        $this->client->setApplicationName('Contacts Sync');
        $this->client->setScopes([
            Directory::ADMIN_DIRECTORY_GROUP,
            Directory::ADMIN_DIRECTORY_GROUP_MEMBER,
        ]);
        $this->client->setAuthConfig($this->configuration);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');
        $this->client->setHostedDomain($this->domain);

        // try to load the token from the saved file
        try {
            $this->client->setAccessToken($this->getToken());
        } catch (\InvalidArgumentException $invalidArgumentException) {
            throw new InvalidGoogleTokenException($invalidArgumentException);
        }

        if ($this->client->isAccessTokenExpired()) {
            if (!$this->client->getRefreshToken()) {
                throw new InvalidGoogleTokenException();
            }
            $this->client->fetchAccessTokenWithRefreshToken(
                $this->client->getRefreshToken(),
            );
            $this->saveToken();
        }

        return $this;
    }

    public function createAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    public function setAuthCode(string $authCode): void
    {
        $accessToken = $this->client->fetchAccessTokenWithAuthCode($authCode);
        $this->client->setAccessToken($accessToken);

        // Check to see if there was an error.
        if (array_key_exists('error', $accessToken)) {
            $exception = new \RuntimeException(implode(', ', $accessToken));
            throw new InvalidGoogleTokenException($exception);
        }

        $this->saveToken();
    }

    public function getContacts(string $listName): array
    {
        return array_map(
            self::memberToContact(...),
            (array) $this->service->members
                ->listMembers($listName)
                ->getMembers(),
        );
    }

    public function addContact(string $list, Contact $contact): void
    {
        $member = self::contactToMember($contact);
        $this->service->members->insert($list, $member);
    }

    public function removeContact(string $list, Contact $contact): void
    {
        $this->service->members->delete($list, $contact->email);
    }

    private static function contactToMember(Contact $contact): Member
    {
        $member = new Member();
        $member->setEmail($contact->email);

        return $member;
    }

    /**
     * @see getContacts
     */
    private static function memberToContact(Member $member): Contact
    {
        $contact = new Contact();
        $contact->email = $member->getEmail();

        return $contact;
    }

    /**
     * @throws FileNotFoundException
     */
    private function getToken(): array
    {
        return json_decode(
            $this->fileProvider->getContents($this->getTokenPath()),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }

    private function getTokenPath(): string
    {
        return sprintf('%s/%s', $this->varPath, self::TOKEN_FILENAME);
    }

    private function saveToken(): void
    {
        $this->fileProvider->saveContents(
            $this->getTokenPath(),
            json_encode($this->client->getAccessToken(), JSON_THROW_ON_ERROR),
        );
    }
}
