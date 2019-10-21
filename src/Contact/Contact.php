<?php

namespace Sync\Contact;

class Contact
{
    /** @var string * */
    public $firstName;

    /** @var string * */
    public $lastName;

    /** @var string|null * */
    public $email;

    /**
     * @param string $firstName
     * @param string $lastName
     * @param string|null $email
     */
    public function __construct(string $firstName, string $lastName, ?string $email = null)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
    }
}
