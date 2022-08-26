<?php

namespace KTemplate\Internal;

class Strings {
    /**
     * @param string $s
     * @return bool
     */
    public static function isWhitespaceOnly($s) {
        return strlen(trim($s)) === 0;
    }

    /**
     * @param string $s
     * @param string $prefix
     * @return bool
     */
    public static function hasPrefix($s, $prefix) {
        return strlen($s) >= strlen($prefix) && strncmp($s, $prefix, strlen($prefix)) === 0;
    }
    /**
     * @param string $s
     * @param string $suffix
     * @return bool
     */
    public static function hasSuffix($s, $suffix) {
        return strlen($s) >= strlen($suffix) && substr_compare($s, $suffix, -strlen($suffix)) === 0;
    }

    /**
     * @param string $s
     * @param string $part
     */
    public static function contains($s, $part) {
        return strlen($part) === 0 || strpos($s, $part) !== false;
    }
}
