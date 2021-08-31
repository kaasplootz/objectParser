# objectParser

[![GitHub stars](https://img.shields.io/github/stars/kaasplootz/objectParser?style=flat-square)](https://github.com/kaasplootz/objectParser/stargazers) [![GitHub issues](https://img.shields.io/github/issues/kaasplootz/objectParser?style=flat-square)](https://github.com/kaasplootz/objectParser/issues) [![GitHub license](https://img.shields.io/github/license/kaasplootz/objectParser?style=flat-square)](https://github.com/kaasplootz/objectParser) ![Packagist Version](https://img.shields.io/packagist/v/kaasplootz/objectParser?style=flat-square)

Example
-------

PHP8 object to JSON:

```php
<?php

namespace example;

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
```

JSON result:

```json
{
  "id": 1,
  "username": "username",
  "private": "privateValue",
  "float": 1, // do not wonder: JSON can't store 1.0 as float
  "SameName": {},
  "otherName": {
    "SameName": {}
  },
  "friends": [
    {
      "Friend": {}
    }
  ],
  "nullable": null
}
```

JSON to object:

```php
<?php

/* @var User $user */
$user = User::fromJSON('
    {
        "id": 1,
        ...
    }
');

echo $userObject->getPrivate();
// privateValue
```