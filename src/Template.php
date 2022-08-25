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

    /** @var int */
    public $frame_size = 1;

    /**
     * Note: the serialization results are different for PHP and KPHP.
     * You can't share the serialized representations between the two.
     *
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
}
