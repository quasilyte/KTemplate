<?php

namespace KTemplate\Internal;

use KTemplate\Template;

class TemplateSerializer {
    private static $format_version = 1;

    /**
     * @param Template $t
     * @return string
     */
    public static function encode($t) {
        $data = new TemplateData();
        $data->version = self::$format_version;

        $data->string_values = $t->string_values;
        $data->keys = $t->keys;
        $data->int_values = $t->int_values;
        $data->float_values = $t->float_values;
        $data->code = $t->code;
        $data->frame_size = $t->frame_size;
        $data->frame_args_size = $t->frame_args_size;
        $data->params = $t->params;

        return self::encodeData($data);
    }

    /**
     * @param string $s
     * @throws \Exception
     * @return Template
     */
    public static function decode($s) {
        $t = new Template();
        $data = self::decodeData($s);
        if ($data->version !== self::$format_version) {
            throw new \Exception("invalid template data version: $data->version vs " . self::$format_version);
        }

        $t->string_values = $data->string_values;
        $t->keys = $data->keys;
        $t->int_values = $data->int_values;
        $t->float_values = $data->float_values;
        $t->code = $data->code;
        $t->frame_size = $data->frame_size;
        $t->frame_args_size = $data->frame_args_size;
        $t->params = $data->params;
        return $t;
    }

    /**
     * @param TemplateData $data
     * @return string
     */
    private static function encodeData($data) {
#ifndef KPHP
        return serialize($data);
#endif
        return (string)instance_serialize($data);
    }

    /**
     * @param string $s
     * @return TemplateData
     */
    private static function decodeData($s) {
#ifndef KPHP
        return unserialize($s);
#endif
        return instance_deserialize($s, TemplateData::class);
    }
}
