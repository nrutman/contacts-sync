<?php

namespace App\Client\Google;

use Google_Client;
use Google_Service_Directory;

class GoogleServiceFactory
{
    public function create(Google_Client $client): Google_Service_Directory
    {
        return new Google_Service_Directory($client);
    }
}
