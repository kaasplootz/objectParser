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
                $classProperties[$propertyName] = $object->$propertyName;
            } else {
                $propertyGetMethodName = 'get' . ucfirst($propertyName);
                if (method_exists($object, $propertyGetMethodName)) {
                    $returnType = ReflectionHandler::getReturnType($object::class, $propertyGetMethodName);
                    if ((string) $classVar->getType() === (string) $returnType) {
                        $property = $object->$propertyGetMethodName();
                        if (is_object($property)) {
                            if ($property instanceof ObjectParser) {
                                $classProperties[$propertyName] = $this->getClassProperties($property);
                            } else {
                                throw new InvalidArgumentException('Class ' . $object::class . ' must be instance of ObjectParser');
                            }
                        } else if (is_array($property)) {
                            $classProperties[$propertyName] = $this->goThroughArray($property);
                        } else {
                            $classProperties[$propertyName] = $property;
                        }
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
            if (is_object($value)) {
                if ($value instanceof ObjectParser) {
                    $preparedArray[] = [ReflectionHandler::getShortClassName($value::class) => $this->getClassProperties($value)];
                } else {
                    throw new InvalidArgumentException('Class ' . $value::class . ' must be instance of ObjectParser');
                }
            } else if (is_array($value)) {
                $preparedArray[] = $this->goThroughArray($value);
            } else {
                $preparedArray[] = $value;
            }
        }

        return $preparedArray;
    }
}