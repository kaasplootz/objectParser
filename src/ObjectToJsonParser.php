<?php

namespace kaasplootz\objectParser;

use InvalidArgumentException;

class ObjectToJsonParser
{
    private bool $fillWithNull;

    public function toJSON(object $object, bool $fillWithNull): string
    {
        $this->fillWithNull = $fillWithNull;
        return json_encode($this->getClassProperties($object));
    }

    private function getClassProperties(object $object): array
    {
        $classVars = ReflectionHandler::getAllClassVars($object::class);
        $classProperties = [];

        foreach ($classVars as $classVar) {
            $propertyName = $classVar->getName();
            if ($classVar->isPublic()) {
                $property = $object->$propertyName;
                $classProperties = $this->addPropertyByType($property, $propertyName, $classProperties);
            } else {
                if ($classVar->getType() == 'bool') {
                    $propertyGetMethodName = 'is' . ucfirst($propertyName);
                } else {
                    $propertyGetMethodName = 'get' . ucfirst($propertyName);
                }
                if (method_exists($object, $propertyGetMethodName)) {
                    $returnType = ReflectionHandler::getReturnType($object::class, $propertyGetMethodName);
                    if ((string) $classVar->getType() === (string) $returnType) {
                        $property = $object->$propertyGetMethodName();
                        $classProperties = $this->addPropertyByType($property, $propertyName, $classProperties);
                    } else {
                        if (!$this->fillWithNull) {
                            throw new InvalidArgumentException('Change return type '. $returnType . ' of ' . $propertyGetMethodName . '() to ' . $classVar->getType());
                        }
                        trigger_error('Change return type of ' . $propertyGetMethodName . '() to ' . $classVar->getType(), E_USER_WARNING);
                        $classProperties[$propertyName] = null;
                    }
                } else {
                    if (!$this->fillWithNull) {
                        throw new InvalidArgumentException('Private property "' . $propertyName . '" has no valid getter. Add ' . $propertyGetMethodName . '() to class "' . $object::class . '"');
                    }
                    trigger_error('Private property "' . $propertyName . '" has no valid getter. Add ' . $propertyGetMethodName . '() to class "' . $object::class . '"', E_USER_WARNING);
                    $classProperties[$propertyName] = null;
                }
            }
        }

        return $classProperties;
    }

    private function goThroughArray(array $array): array
    {
        $preparedArray = [];
        foreach ($array as $value) {
            $preparedArray = $this->addPropertyByType($value, null, $preparedArray);
        }

        return $preparedArray;
    }

    private function addPropertyByType($property, ?string $propertyName, array $classProperties): array
    {
        if (is_object($property)) {
            if ($property instanceof ObjectParser) {
                if (strtolower(ReflectionHandler::getShortClassName($property::class)) === strtolower($propertyName)) {
                    if (count(ReflectionHandler::getAllClassVars($property::class)) < 1) {
                        // without json_decode('{}') it would be an empty array
                        $classProperties[ReflectionHandler::getShortClassName($property::class)] = json_decode('{}');
                    } else {
                        $classProperties[ReflectionHandler::getShortClassName($property::class)] = $this->getClassProperties($property);
                    }
                } else {
                    if (count(ReflectionHandler::getAllClassVars($property::class)) < 1) {
                        // without json_decode('{}') it would be an empty array
                        $classProperties[$propertyName] = [ucfirst(ReflectionHandler::getShortClassName($property::class)) => json_decode('{}')];
                    } else {
                        $classProperties[$propertyName] = [ucfirst(ReflectionHandler::getShortClassName($property::class)) => $this->getClassProperties($property)];
                    }
                }
            } else {
                throw new InvalidArgumentException('Class ' . $property::class . ' must be instance of ObjectParser');
            }
        } else {
            if (is_array($property)) {
                $classProperties[$propertyName] = $this->goThroughArray($property);
            } else {
                $classProperties[$propertyName] = $property;
            }
        }
        if ($propertyName === null) {
            return array_values($classProperties);
        }
        return $classProperties;
    }
}