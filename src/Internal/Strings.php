<?php

namespace KTemplate\Internal;

class Strings {
    /**
     * @param string $s
     * @param string $prefix
     * @return bool
     */
    public static function hasPrefix($s, $prefix) {
        return strncmp($s, $prefix, strlen($prefix)) === 0;
    }
    /**
     * @param string $s
     * @param string $suffix
     * @return bool
     */
    public static function hasSuffix($s, $suffix) {
        return substr_compare($s, $suffix, -strlen($suffix)) === 0;
    }

    /**
     * @param string $s
     * @param string $part
     */
    public static function contains($s, $part) {
        return strlen($part) === 0 || strpos($s, $part) !== false;
    }
}
