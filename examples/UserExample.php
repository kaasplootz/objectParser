<?php

declare(strict_types=1);

namespace example;

require_once __DIR__ . '/../vendor/autoload.php';

use kaasplootz\objectParser\ObjectParser;

class SameName extends ObjectParser {}
class Friend extends ObjectParser {}

class User extends ObjectParser {
    public function __construct(
        public int $id,
        public string $username,
        private string $private,
        public float $float,
        public SameName $sameName,
        public SameName $otherName,
        public array $friends,
        public ?string $nullable = null
    ) {}

    /**
     * @return string
     */
    public function getPrivate(): string
    {
        return $this->private;
    }
}

$user = new User(
    1,
    'username',
    'privateValue',
    1.0,
    new SameName(),
    new SameName(),
    [
        new Friend()
    ]
);

echo $user->toJSON();

/* @var User $userObject */
$userObject = User::fromJSON($user->toJSON());

echo $userObject->getPrivate();
