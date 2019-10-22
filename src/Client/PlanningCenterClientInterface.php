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
}
