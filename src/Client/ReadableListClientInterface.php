<?php

namespace App\Client;

use App\Contact\Contact;

interface ReadableListClientInterface
{
    /**
     * Gets contacts for a given list.
     *
     * @param string $listName
     *
     * @return Contact[]
     */
    public function getContactsForList(string $listName): array;
}
