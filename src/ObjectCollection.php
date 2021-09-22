<?php

declare(strict_types=1);

namespace kaasplootz\objectParser;

use InvalidArgumentException;
use ReflectionException;

class ObjectCollection
{
    public function __construct(
        private string $class,
        private array $objects
    ) {}

    public function toJSON(bool $fillWithNull = false): string
    {
        $objectToJsonParser = new ObjectToJsonParser();
        $jsonObjects = [];
        foreach ($this->objects as $collectionObject) {
            if ($collectionObject instanceof ObjectParser) {
                $jsonObjects[] = $objectToJsonParser->toJSON($collectionObject, $fillWithNull);
            } else {
                throw new InvalidArgumentException('Expected instance of ' . $this->class . ' - ' . gettype($collectionObject) . ' given');
            }
        }

        return sprintf(
            '{"%s[]": [%s]}',
            ReflectionHandler::getShortClassName($this->class),
            implode(', ', $jsonObjects)
        );
    }

    public static function fromJSON(string $class, string $json): array
    {
        try {
            if (!isset(json_decode($json, true)[ReflectionHandler::getShortClassName($class) . '[]'])) {
                throw new InvalidArgumentException('Expected collection of type ' . ReflectionHandler::getShortClassName($class) . '[]');
            }
            $objectArray = json_decode($json, true)[ReflectionHandler::getShortClassName($class) . '[]'];
            $jsonToObjectParser = new JsonToObjectParser();

            $objectCollection = [];
            foreach ($objectArray as $collectionObject) {
                $objectCollection[] = $jsonToObjectParser->fromJson(json_encode($collectionObject), $class);
            }
            return $objectCollection;
        } catch (ReflectionException $e) {
            throw new InvalidArgumentException('Could not create collection from Json');
        }
    }

    /**
     * @return array
     */
    public function getObjects(): array
    {
        return $this->objects;
    }
}
