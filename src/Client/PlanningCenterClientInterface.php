<?php

namespace Sync\Client;

use Sync\Contact\Contact;

interface PlanningCenterClientInterface
{
    /**
     * Returns a list of contacts from the Planning Center API.
     *
     * @param string|null $membershipStatus [description]
     *
     * @return Contact[]
     */
    public function getMembers(?string $membershipStatus = 'Member'): array;
}
