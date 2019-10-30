<?php

namespace App\Client;

use Google_Service_Directory_Member;

interface GoogleClientInterface
{
    /**
     * @param string $groupId
     *
     * @return Google_Service_Directory_Member[]
     */
    public function getGroupMembers(string $groupId): array;
}
