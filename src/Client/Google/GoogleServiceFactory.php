<?php

namespace App\Client\Google;

use Google\Client;
use Google\Service\Directory;

class GoogleServiceFactory
{
    public function create(Client $client): Directory
    {
        return new Directory($client);
    }
}
