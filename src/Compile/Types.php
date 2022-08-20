<?php

namespace KTemplate\Compile;

class Types {
    public const UNKNOWN = 0;
    public const BOOL = 1;
    public const INT = 2;
    public const STRING = 3;
    public const NULL = 4;

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
        case self::STRING:
            return 'string';
        case self::NULL:
            return 'null';
        default:
            return 'invalid';
        }
    }
}