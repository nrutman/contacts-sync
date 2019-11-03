<?php

namespace App\Client;

use GuzzleHttp\ClientInterface;

interface WebClientFactoryInterface
{
    /**
     * Creates a new web client given the specified Guzzle configuration SplObjectStorage.
     *
     * @param array $guzzleConfiguration Guzzle configuration SplObjectStorage
     *
     * @return ClientInterface The web client that was created
     */
    public function create(array $guzzleConfiguration = []): ClientInterface;
}
