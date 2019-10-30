<?php

namespace Sync\Client;

use Sync\Contact\Contact;

interface PlanningCenterClientInterface
{
    /**
     * Returns a list of contacts from the Planning Center API.
     *
     * @return Contact[]
     */
    public function getContacts(): array;

    /**
     * Returns a list of contacts from a Planning Center list.
     *
     * @param string $listName
     *
     * @return Contact[]
     */
    public function getContactsForList(string $listName): array;
}
