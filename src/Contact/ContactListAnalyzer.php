<?php

namespace App\Contact;

class ContactListAnalyzer
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
     * @param bool $removeDuplicates
     */
    public function __construct(array $sourceList, array $destList, bool $removeDuplicates = true)
    {
        // Find contacts in the source list that are not in the destination list. These should be added.
        self::buildDiffArray($this->toAdd, $sourceList, $destList, $removeDuplicates);

        // Find contacts in the destination list that are not in the source list. These should be removed.
        self::buildDiffArray($this->toRemove, $destList, $sourceList, $removeDuplicates);
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
     * Builds an array of items that represents a "diff" between two lists.
     *
     * @param Contact[] $diff
     * @param Contact[] $listOfRecord
     * @param Contact[] $listToVerify
     * @param bool $removeDuplicates
     *
     * @return Contact[]
     */
    private static function buildDiffArray(array &$diff, array $listOfRecord, array $listToVerify, bool $removeDuplicates): array
    {
        $listToVerifyByEmail = self::mapContactsByEmail($listToVerify);
        $uniqueEmailMap = [];

        array_push($diff, ...array_filter($listOfRecord, static function (Contact $contact) use ($listToVerifyByEmail, &$uniqueEmailMap, $removeDuplicates) {
            $email = strtolower($contact->email);

            // if duplicates are being removed and we've already processed this email, exclude it from the result
            if ($removeDuplicates && isset($uniqueEmailMap[$email])) {
                return false;
            }

            // record that we've processed this email
            $uniqueEmailMap[$email] = true;

            // add the email to the diff if it is not in the list to verify against the record
            return !isset($listToVerifyByEmail[$email]);
        }));

        return $diff;
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
