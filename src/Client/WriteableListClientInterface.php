<?php

namespace App\Client;

use App\Contact\Contact;

interface WriteableListClientInterface
{
    /**
     * Adds a contact to the list.
     */
    public function addContact(string $list, Contact $contact): void;

    /**
     * Removes a contact from the list.
     */
    public function removeContact(string $list, Contact $contact): void;
}
