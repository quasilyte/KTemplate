<?php

namespace KTemplate\Internal;

class Assert {
    /**
     * @param bool $v
     * @param string $message
     */
    public static function true($v, $message) {
#ifndef KPHP
        if (!$v) {
            throw new \Exception("internal KTemplate error: $message");
        }
        return;        
#endif
        if (!$v) {
            critical_error("internal KTemplate error: $message");
        }
    }
}
