<?php

declare(strict_types=1);

namespace example;

use kaasplootz\objectParser\ObjectParser;

class User extends ObjectParser {
    public function __construct(
        public int $id,
        public string $username,
        private string $email
    ) {}

    public function getEmail(): string
    {
        return $this->email;
    }
}

$user = new User(
    1,
    'username',
    'username@email.com'
);

echo $user->toJSON();

/* @var User $userObject */
$userObject = User::fromJSON($user->toJSON());

echo $userObject->getEmail();
