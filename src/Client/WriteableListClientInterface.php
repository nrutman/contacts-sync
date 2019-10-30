<?php

namespace App\Client;

use App\Contact\Contact;

interface WriteableListClientInterface
{
    /**
     * Adds a contact to the list.
     *
     * @param Contact $contact
     */
    public function addContact(Contact $contact): void;

    /**
     * Removes a contact from the list.
     *
     * @param Contact $contact
     */
    public function removeContact(Contact $contact): void;
}
