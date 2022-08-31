<?php

namespace KTemplate;

class FilterLib {
    /**
     * @param string $s
     * @param string $strategy
     * @return string
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

    /**
     * @param Context $ctx
     * @param Engine $engine
     */
    public static function registerAllFilters($ctx, $engine) {
        self::registerAbs($ctx, $engine);
        self::registerRound($ctx, $engine);
        self::registerCeil($ctx, $engine);
        self::registerFloor($ctx, $engine);
        self::registerCapitalize($ctx, $engine);
        self::registerFirst($ctx, $engine);
        self::registerLast($ctx, $engine);
        self::registerJoin($ctx, $engine);
        self::registerKeys($ctx, $engine);
        self::registerUpper($ctx, $engine);
        self::registerLower($ctx, $engine);
        self::registerTrim($ctx, $engine);
        self::registerLtrim($ctx, $engine);
        self::registerRtrim($ctx, $engine);
    }

    /**
     * @param Context $ctx
     * @param Engine $engine
     */
    public static function registerAbs($ctx, $engine) {
        $engine->registerFilter1('abs', function ($x) {
            return abs($x);
        });
    }

    /**
     * @param Context $ctx
     * @param Engine $engine
     */
    public static function registerRound($ctx, $engine) {
        $engine->registerFilter1('round', function ($x) {
            return round($x);
        });
    }

    /**
     * @param Context $ctx
     * @param Engine $engine
     */
    public static function registerCeil($ctx, $engine) {
        $engine->registerFilter1('ceil', function ($x) {
            return ceil($x);
        });
    }

    /**
     * @param Context $ctx
     * @param Engine $engine
     */
    public static function registerFloor($ctx, $engine) {
        $engine->registerFilter1('floor', function ($x) {
            return floor($x);
        });
    }

    /**
     * @param Context $ctx
     * @param Engine $engine
     */
    public static function registerCapitalize($ctx, $engine) {
        $engine->registerFilter1('capitalize', function ($s) use ($ctx) {
            // This doesn't look like the most performant implementation for
            // neither PHP or KPHP, but it's something that Twig uses.
            // TODO: make it more efficient?
            if (!is_string($s)) {
                return ''; // TODO: throw exception?
            }
            $first = mb_strtoupper(mb_substr($s, 0, 1, $ctx->encoding), $ctx->encoding);
            $rest = mb_strtolower(mb_substr($s, 1, null, $ctx->encoding), $ctx->encoding);
            return $first . $rest;
        });
    }

    /**
     * @param Context $ctx
     * @param Engine $engine
     */
    public static function registerFirst($ctx, $engine) {
        $engine->registerFilter1('first', function ($x) use ($ctx) {
            if (is_string($x)) {
                if (strlen($x) === 0) {
                    return '';
                }
                return mb_substr($x, 0, 1, $ctx->encoding);
            }
            if (is_array($x)) {
                if (count($x) !== 0) {
                    return array_first_value($x);
                }
            }
            return null;
        });
    }

    /**
     * @param Context $ctx
     * @param Engine $engine
     */
    public static function registerLast($ctx, $engine) {
        $engine->registerFilter1('last', function ($x) use ($ctx) {
            if (is_string($x)) {
                if (strlen($x) === 0) {
                    return '';
                }
                return mb_substr($x, -1, 1, $ctx->encoding);
            }
            if (is_array($x)) {
                if (count($x) !== 0) {
                    return array_last_value($x);
                }
            }
            return null;
        });
    }

    /**
     * @param Context $ctx
     * @param Engine $engine
     */
    public static function registerJoin($ctx, $engine) {
        $engine->registerFilter1('join', function ($arr) {
            return implode('', $arr);
        });
        $engine->registerFilter2('join', function ($arr, $sep) {
            return implode($sep, $arr);
        });
    }

    /**
     * @param Context $ctx
     * @param Engine $engine
     */
    public static function registerKeys($ctx, $engine) {
        $engine->registerFilter1('keys', function ($arr) {
            if (!is_array($arr)) {
                return [];
            }
            return array_keys($arr);
        });
    }

    /**
     * @param Context $ctx
     * @param Engine $engine
     */
    public static function registerUpper($ctx, $engine) {
        $engine->registerFilter1('upper', function ($s) use ($ctx) {
            if (!is_string($s) || strlen($s) === 0) {
                return $s;
            }
            return mb_strtoupper($s, $ctx->encoding);
        });
    }

    /**
     * @param Context $ctx
     * @param Engine $engine
     */
    public static function registerLower($ctx, $engine) {
        $engine->registerFilter1('lower', function ($s) use ($ctx) {
            if (!is_string($s) || strlen($s) === 0) {
                return $s;
            }
            return mb_strtolower($s, $ctx->encoding);
        });
    }

    /**
     * @param Context $ctx
     * @param Engine $engine
     */
    public static function registerTrim($ctx, $engine) {
        $engine->registerFilter1('trim', function ($s) {
            return trim($s, " \t\n\r\0\x0B");
        });
        $engine->registerFilter2('trim', function ($s, $chars) {
            return trim($s, $chars);
        });
    }

    /**
     * @param Context $ctx
     * @param Engine $engine
     */
    public static function registerLtrim($ctx, $engine) {
        $engine->registerFilter1('ltrim', function ($s) {
            return ltrim($s, " \t\n\r\0\x0B");
        });
        $engine->registerFilter2('ltrim', function ($s, $chars) {
            return ltrim($s, $chars);
        });
    }

    /**
     * @param Context $ctx
     * @param Engine $engine
     */
    public static function registerRtrim($ctx, $engine) {
        $engine->registerFilter1('rtrim', function ($s) {
            return rtrim($s, " \t\n\r\0\x0B");
        });
        $engine->registerFilter2('rtrim', function ($s, $chars) {
            return rtrim($s, $chars);
        });
    }
}
