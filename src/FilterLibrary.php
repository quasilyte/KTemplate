<?php

namespace KTemplate;

class FilterLibrary {
    /**
     * @param string $s
     * @param string $strategy
     * @kphp-required
     */
    public static function escape($s, $strategy) {
        switch ($strategy) {
        case 'html':
            // TODO: should add \ENT_SUBSTITUTE flag too.
            $flags = \ENT_QUOTES;
            return htmlspecialchars($s, $flags);
        case 'url':
            return rawurlencode($s);
        }
        return $s;
    }
}
