<?php

namespace App\Contact;

use DateTime;

class Contact
{
    /** @var string */
    public $firstName;

    /** @var string */
    public $lastName;

    /** @var string|null */
    public $email;

    /** @var string */
    public $membership;

    /** @var string */
    public $gender;

    /** @var DateTime */
    public $createdAt;

    /** @var DateTime */
    public $updatedAt;

    /**
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @param string|null $membership
     * @param string|null $gender
     * @param DateTime $createdAt
     * @param DateTime $updatedAt
     */
    public function __construct(
        string $firstName,
        string $lastName,
        string $email,
        ?string $membership,
        ?string $gender,
        DateTime $createdAt,
        DateTime $updatedAt
    ) {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->membership = $membership;
        $this->gender = $gender;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }
}
