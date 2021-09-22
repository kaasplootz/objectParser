<?php

declare(strict_types=1);

namespace example2;

require_once __DIR__ . '/../vendor/autoload.php';

use kaasplootz\objectParser\ObjectCollection;
use kaasplootz\objectParser\ObjectParser;

class User extends ObjectParser {
    public function __construct(
        public int $id,
        public string $username
    ) {}
}

$users = [
    new User(1, 'User1'),
    new User(2, 'User2')
];

$userCollection = new ObjectCollection(User::class);

echo $userJson = $userCollection->toJSON($users);

echo "\n";

/** @var User[] $user */
$users = $userCollection->fromJSON($userJson);

echo $users[1]->username;