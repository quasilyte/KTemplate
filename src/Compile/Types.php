<?php

namespace KTemplate\Compile;

class Types {
    public const UNKNOWN = 0;
    public const BOOL = 1;
    public const INT = 2;
    public const FLOAT = 3;
    public const NUMERIC = 4; // INT|FLOAT
    public const STRING = 5;
    public const SAFE_STRING = 6;
    public const NULL = 8;
    public const MIXED = 9;

    /**
     * @param int $type
     * @return string
     */
    public static function typeString($type) {
        switch ($type) {
        case self::UNKNOWN:
            return 'unknown';
        case self::BOOL:
            return 'bool';
        case self::INT:
            return 'int';
        case self::FLOAT:
            return 'float';
        case self::NUMERIC:
            return 'int|float';
        case self::STRING:
        case self::SAFE_STRING:
            return 'string';
        case self::NULL:
            return 'null';
        case self::MIXED:
            return 'mixed';
        default:
            return 'invalid';
        }
    }
}
