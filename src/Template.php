<?php

namespace KTemplate;

use KTemplate\Internal\TemplateSerializer;

class Template {
    /** @var string[] */
    public $string_values = [];
    /** @var string[] */
    public $keys = [];
    /** @var int[] */
    public $int_values = [];
    /** @var float[] */
    public $float_values = [];

    /** @var int[] */
    public $code = [];

    /**
     * @return string
     */
    public function serialize() {
        return TemplateSerializer::encode($this);
    }

    /**
     * @param string $s
     * @return Template
     * @throws \Exception
     */
    public static function unserialize($s) {
        return TemplateSerializer::decode($s);
    }

    /**
     * @param string $s
     * @param Template $t
     * @throws \Exception
     */
    public static function unserializeInto($t, $s) {
        TemplateSerializer::decodeInto($t, $s);
    } 
}
