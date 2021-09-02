<?php

namespace kaasplootz\objectParser;

use Exception;
use InvalidArgumentException;
use ReflectionException;

class JsonToObjectParser
{
    private string|null $namespace;

    public function fromJson(string $json, string $calledClass): object
    {
        try {
            $this->namespace = ReflectionHandler::getNamespace($calledClass);
            $jsonObject = json_decode($json);
            if ($jsonObject === null) {
                throw new Exception('Object not valid');
            }
            return ReflectionHandler::getInstance(
                $calledClass,
                $this->getClassArguments($jsonObject, $calledClass)
            );
        } catch (ReflectionException $e) {
            throw new InvalidArgumentException("Class $calledClass could not be created");
        } catch (Exception $e) {
            throw new InvalidArgumentException("Class $calledClass could not be created. Reason: " . $e->getMessage());
        }
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    private function getClassArguments(object $object, string $className): array
    {
        $classAttributes = [];
        $classProperties = ReflectionHandler::getAllClassVars($className);

        foreach ($classProperties as $property) {
            $propertyName = $property->getName();
            $propertyType = $property->getType();
            if ((ReflectionHandler::getNamespace($propertyType) !== null)
                && (strtolower(ReflectionHandler::getShortClassName($propertyType)) === strtolower($propertyName))) {
                $propertyName = ucfirst($propertyName);
            } else if ((ReflectionHandler::getNamespace($propertyType) !== null)
                && (strtolower(ReflectionHandler::getShortClassName($propertyType)) !== strtolower($propertyName))) {
                // if object name != property name -> object one layer deeper
                $shortClassName = ReflectionHandler::getShortClassName($propertyType);
                $classAttributes[] = $this->fromJSON(json_encode($object->$propertyName->$shortClassName), $propertyType);
                continue; // skip current because it's already done ^
            }
            if (isset($object->$propertyName)) {
                if (ReflectionHandler::isAllowedType($propertyType, $object->$propertyName)) {
                    if (is_object($object->$propertyName)) {
                        try {
                            $instance = ReflectionHandler::getInstance($propertyType);
                            if ($instance instanceof ObjectParser) {
                                $classAttributes[] = $this->fromJSON(json_encode($object->$propertyName), $instance::class);
                            } else {
                                throw new InvalidArgumentException('Class ' . $instance::class . ' must be instance of ObjectParser');
                            }
                        } catch (ReflectionException $e) {
                            throw new InvalidArgumentException('Class ' . $this->namespace . '\\' . key($object->$propertyName) . ' could not be created (2)');
                        }
                    } else if (is_array($object->$propertyName)) {
                        $classAttributes[] = $this->goThroughArray($object->$propertyName);
                    } else {
                        $classAttributes[] = $object->$propertyName;
                    }
                } else {
                    if ($propertyType->allowsNull()) {
                        trigger_error('Invalid type ' . gettype($object->$propertyName) . '. Set "' . $propertyName . '" to null', E_USER_NOTICE);
                        $classAttributes[] = null;
                    } else {
                        throw new InvalidArgumentException('Type ' . gettype($object->$propertyName) . ' not valid for "' . $propertyName . '"');
                    }
                }
            } else {
                throw new Exception('"' . $propertyName . '" is missing in object');
            }
        }

        return $classAttributes;
    }

    /**
     * @param array $array
     * @return mixed
     */
    private function goThroughArray(array $array): array
    {
        $preparedArray = [];
        foreach ($array as $value) {
            if (is_object($value)) {
                try {
                    $arrayObject = ReflectionHandler::getInstance(
                        ReflectionHandler::getClassName(
                            key($value),
                            $this->namespace
                        )
                    );
                    if ($arrayObject instanceof ObjectParser) {
                        $objectName = key($value);
                        $preparedArray[] = $this->fromJSON(json_encode($value->$objectName), $arrayObject::class);
                    } else {
                        throw new InvalidArgumentException('Class ' . $arrayObject::class . ' must be instance of ObjectParser');
                    }
                } catch (ReflectionException $e) {
                    throw new InvalidArgumentException('Class ' . $this->namespace . '\\' .  key($value) . ' could not be created');
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