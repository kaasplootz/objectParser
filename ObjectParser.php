<?php

namespace kaasplootz\objectParser;

class ObjectParser
{
    /**
     * Fill with null if (not public-)property is not accessible because of missing/invalid getter.
     * Warning: If enabled it may cause type errors while parsing back!
     * @param bool $fillWithNull
     * @return string
     */
    public function toJSON(bool $fillWithNull = false): string
    {
        $parser = new ObjectToJsonParser();
        return $parser->toJSON($this, $fillWithNull);
    }

    /**
     * @param string $json
     * @return object
     */
    public static function fromJSON(string $json): object
    {
        $parser = new JsonToObjectParser();
        return $parser->fromJson($json, get_called_class());
    }
}