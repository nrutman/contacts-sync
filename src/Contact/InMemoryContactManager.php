<?php

namespace App\Contact;

class InMemoryContactManager
{
    /** @var array<string, Contact[]> */
    private array $contactsByList = [];

    public function __construct(array $inMemoryContacts)
    {
        foreach ($inMemoryContacts as $contact) {
            $lists = is_array($contact['list'])
                ? $contact['list']
                : [$contact['list']];

            foreach ($lists as $list) {
                $list = strtolower($list);

                if (!isset($this->contactsByList[$list])) {
                    $this->contactsByList[$list] = [];
                }

                $contactObj = new Contact();
                $contactObj->email = $contact['email'];

                $this->contactsByList[$list][] = $contactObj;
            }
        }
    }

    /**
     * Returns in-memory contacts for the specified list.
     *
     * @return Contact[]
     */
    public function getContacts(string $list): array
    {
        $key = strtolower($list);
        if (!array_key_exists($key, $this->contactsByList)) {
            return [];
        }

        return $this->contactsByList[$key];
    }
}
