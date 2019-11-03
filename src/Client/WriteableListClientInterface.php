<?php

namespace App\Client;

use App\Contact\Contact;

interface WriteableListClientInterface
{
    /**
     * Adds a contact to the list.
     *
     * @param string $list
     * @param Contact $contact
     */
    public function addContact(string $list, Contact $contact): void;

    /**
     * Removes a contact from the list.
     *
     * @param string $list
     * @param Contact $contact
     */
    public function removeContact(string $list, Contact $contact): void;
}
