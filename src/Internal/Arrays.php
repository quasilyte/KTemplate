<?php

namespace KTemplate\Internal;

class Arrays {
    /**
     * @param mixed[] $arr
     * @param string $key
     * @return int
     */
    public static function stringKeyOffset($arr, $key) {
        $offset = 0;
        foreach ($arr as $k => $_) {
            if ($k === $key) {
                return $offset;
            }
            $offset++;
        }
        return -1;
    }
}
