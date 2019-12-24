<?php

namespace App\Client;

use App\Contact\Contact;

interface ReadableListClientInterface
{
    /**
     * Gets contacts for a given list.
     *
     * @return Contact[]
     */
    public function getContacts(string $listName): array;
}
