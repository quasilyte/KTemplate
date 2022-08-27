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
     * Packed extra info.
     * ($extra_info >> 0)  & 0xff => $frame_size
     * ($extra_info >> 8)  & 0xff => $frame_args_size
     * ($extra_info >> 16) & 0xff => $num_cache_slots
     * @var int
     **/
    public $extra_info = 0;

    /**
     * An ordered list of template params with their default values.
     * Keys are param names, values are default initializers.
     *
     * @var mixed[]
     **/
    public $params = [];

    /**
     * @param int $frame_size
     * @param int $frame_args_size
     * @param int $num_cache_slots
     */
    public function setExtraInfo($frame_size, $frame_args_size, $num_cache_slots) {
        $this->extra_info = ($frame_size) |
            ($frame_args_size << 8) |
            ($num_cache_slots << 16);
    }

    /** @return int */
    public function frameSize() { return ($this->extra_info) & 0xff; }

    /** @return int */
    public function frameArgsSize() { return ($this->extra_info >> 8) & 0xff; }

    /** @return int */
    public function numCacheSlots() { return ($this->extra_info >> 16) & 0xff; }

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
