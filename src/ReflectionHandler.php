<?php

namespace kaasplootz\objectParser;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use ReflectionType;

class ReflectionHandler
{
    /**
     * @throws ReflectionException
     */
    public static function getClassName(string $shortClassName, ?string $namespace = null): string
    {
        if ($namespace !== null) {
            $namespace = $namespace . "\\";
        } else {
            $namespace = '';
        }
        $reflection = new ReflectionClass($namespace . $shortClassName);
        return $reflection->getName();
    }

    /**
     * @throws ReflectionException
     */
    public static function getShortClassName(string $className): string
    {
        $className = str_replace('?', '', $className);
        $reflection = new ReflectionClass($className);
        return $reflection->getShortName();
    }

    /**
     * @throws ReflectionException
     */
    public static function getInstance(string $className, ?array $arguments = null): object
    {
        $className = str_replace('?', '', $className);
        $reflection = new ReflectionClass($className);
        if ($arguments === null) {
            return $reflection->newInstanceWithoutConstructor();
        }
        return $reflection->newInstanceArgs($arguments);
    }

    public static function isInstance(?string $className = null, ?string $shortClassName = null): bool
    {
        if ($className !== null) {
            try {
                ReflectionHandler::getShortClassName($className);
            } catch (ReflectionException $e) {
                return false;
            }
        } else {
            if ($shortClassName !== null) {
                try {
                    ReflectionHandler::getClassName($shortClassName);
                } catch (ReflectionException $e) {
                    return false;
                }
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * @return ReflectionProperty[]
     * @throws ReflectionException
     */
    public static function getAllClassVars(string $className): array
    {
        $className = str_replace('?', '', $className);
        $reflection = new ReflectionClass($className);
        return $reflection->getProperties();
    }

    public static function getNamespace(string $className): string|null
    {
        try {
            $className = str_replace('?', '', $className);
            $reflection = new ReflectionClass($className);
            return $reflection->getNamespaceName();
        } catch (ReflectionException $e) {
            return null;
        }
    }

    public static function isAllowedType(ReflectionType|null $type, mixed $value): bool
    {
        if ($type !== null) {
            $valueType = gettype($value);
            if ($valueType === 'NULL') {
                return $type->allowsNull();
            } else {
                if ($valueType === 'integer') {
                    $valueType = 'int';
                } else {
                    if ($valueType === 'boolean') {
                        $valueType = 'bool';
                    } else {
                        if ($valueType === 'double') {
                            $valueType = 'float';
                        } else {
                            if (ReflectionHandler::isInstance(className: $type)) {
                                return true;
                            }
                        }
                    }
                }
            }
            // for casting problems:
            if (($valueType === 'float' && (string)$type === 'int') || ($valueType === 'int' && (string)$type === 'float'))
                return true;

            return str_contains((string)$type, $valueType);
        } else {
            // no type required:
            return true;
        }
    }

    /**
     * @throws ReflectionException
     */
    public static function getReturnType(string $className, string $methodName): ReflectionType
    {
        $className = str_replace('?', '', $className);
        $reflection = new ReflectionMethod($className, $methodName);
        return $reflection->getReturnType();
    }
}