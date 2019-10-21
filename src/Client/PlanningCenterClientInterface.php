<?php

namespace Sync\Client;

interface PlanningCenterClientInterface
{
    /**
     * Returns a list of contacts from the Planning Center API.
     *
     * @param string|null $membershipStatus [description]
     *
     * @return Contacts[]
     */
    public function getMembers(?string $membershipStatus = 'Member'): array;
}
