# objectParser

[![GitHub stars](https://img.shields.io/github/stars/kaasplootz/objectParser?style=flat-square)](https://github.com/kaasplootz/objectParser/stargazers) [![GitHub issues](https://img.shields.io/github/issues/kaasplootz/objectParser?style=flat-square)](https://github.com/kaasplootz/objectParser/issues) [![GitHub license](https://img.shields.io/github/license/kaasplootz/objectParser?style=flat-square)](https://github.com/kaasplootz/objectParser)

##Example:
PHP8 object to JSON:

    <?php

    namespace example;

    use kaasplootz\objectParser\ObjectParser;

    class User extends ObjectParser {
        public function __construct(
            public int $id,
            public string $username,
            private string $email,
            public array $friends = [],
            public ?string $nullable = null
        ) {}

        public function getEmail(): string
        {
            return $this->email;
        }
    }

    $user = new User(
        1,
        'username',
        'username@email.com',
        [
            new User(2, 'username2', 'username2@email.com')
        ]
    );

    echo $user->toJSON();

JSON result:

    {
        "id": 1,
        "username": "username",
        "email": "username@email.com",
        "friends": [
            {
                "User": {
                    "id": 2,
                    "username": "username2",
                    "email": "username2@email.com",
                    "friends: [],
                    "nullable": null
                } 
            }
        ],
        "nullable": null
    }

JSON to object:

    <?php

    /* @var User $user */
    $user = User::fromJSON('
        {
            "id": 1,
            ...
        }
    ');

    echo $user->getEmail();
    // username@email.com