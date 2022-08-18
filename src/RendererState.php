<?php

namespace KTemplate;

class RendererState {
    public string $buf = '';

    /** @var mixed[] */
    public $slots = [];

    /** @var int */
    public $cache_bitset = 0;

    /** @var DataKey */
    public $data_key;

    /** @var DataProviderInterface */
    public $data_provider;

    public function __construct() {
        for ($i = 0; $i < 32; $i++) {
            $this->slots[] = null;
        }
        $this->data_key = new DataKey();
    }

    /**
     * @param DataProviderInterface $data_provider
     */
    public function reset($data_provider) {
        $this->data_provider = $data_provider;
        $this->cache_bitset = 0;
        $this->buf = '';
    }
}
