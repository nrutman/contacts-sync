<?php

namespace App\Contact;

class ContactListDiff
{
    /** @var Contact[] */
    private $toAdd = [];

    /** @var Contact[] */
    private $toRemove = [];

    /**
     * ContactListDiff constructor.
     *
     * @param Contact[] $sourceList
     * @param Contact[] $destList
     */
    public function __construct(array $sourceList, array $destList)
    {
        $sourceByEmail = self::mapContactsByEmail($sourceList);
        $destByEmail = self::mapContactsByEmail($destList);

        // Find contacts in the source list that are not in the destination list. These should be added.
        array_push($this->toAdd, ...array_filter($sourceList, static function (Contact $contact) use ($destByEmail) {
            return !isset($destByEmail[strtolower($contact->email)]);
        }));

        // Find contacts in the destination list that are not in the source list. These should be removed.
        array_push($this->toRemove, ...array_filter($destList, static function (Contact $contact) use ($sourceByEmail) {
            return !isset($sourceByEmail[strtolower($contact->email)]);
        }));
    }

    /**
     * @return Contact[]
     */
    public function getContactsToAdd(): array
    {
        return $this->toAdd;
    }

    /**
     * @return Contact[]
     */
    public function getContactsToRemove(): array
    {
        return $this->toRemove;
    }

    /**
     * Generates a map keyed by email address for each contact in a list.
     *
     * @param Contact[] $contacts
     *
     * @return array
     */
    private static function mapContactsByEmail(array $contacts): array
    {
        $map = [];

        foreach ($contacts as $contact) {
            $map[strtolower($contact->email)] = $contact;
        }

        return $map;
    }
}
