<?php

namespace Sync\Client;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

class WebClientFactory implements WebClientFactoryInterface
{
    /** @var array */
    protected $defaultConfiguration = [];

    /**
     * @param array $defaultConfiguration default configuration for creating Guzzle clients
     */
    public function __construct(array $defaultConfiguration = [])
    {
        $this->defaultConfiguration = $defaultConfiguration;
    }

    /**
     * Creates a new web client.
     *
     * @param array $guzzleConfiguration Configuration for the Guzzle Client
     *
     * @return ClientInterface The instantiated Guzzle Client
     */
    public function create(array $guzzleConfiguration = []): ClientInterface
    {
        return new Client(array_merge($guzzleConfiguration, $this->defaultConfiguration));
    }
}
