<?php

namespace KTemplate\Internal;

use KTemplate\DataKey;
use KTemplate\DataProviderInterface;
use KTemplate\Template;

class RendererState {
    /** @var string */
    public $buf = '';

    /** @var string */
    public $buf2 = '';

    /** @var mixed[] */
    public $slots = [];

    /** @var int */
    public $slot_offset = 0;

    /** @var int */
    public $cache_bitset = 0;

    /** @var DataKey */
    public $data_key;

    /** @var DataProviderInterface */
    public $data_provider;

    /** @var Template */
    public $template;

    public function __construct() {
        $this->reserve(16);
        $this->data_key = new DataKey();
    }

    /**
     * @param DataProviderInterface $data_provider
     */
    public function reset($data_provider) {
        $this->data_provider = $data_provider;
        $this->cache_bitset = 0;
        $this->buf = '';
        $this->buf2 = '';
        $this->slot_offset = 0;
        $this->template = null;
    }

    public function swapBuf() {
        $tmp = $this->buf2;
        $this->buf2 = $this->buf;
        $this->buf = $tmp;
    }

    public function clearSlots() {
        foreach ($this->slots as $i => $_) {
            $this->slots[$i] = null;
        }
    }

    /**
     * @param int $n
     */
    public function reserve($n) {
        if (count($this->slots) >= $n) {
            return;
        }
        $n -= count($this->slots);
        for ($i = 0; $i < $n; $i++) {
            $this->slots[] = null;
        }
    }
}
