<?php

namespace KTemplate;

class RendererState {
    public string $buf = '';

    /** @var mixed[] */
    public $slots = [];

    /** @var DataKey */
    public $data_key;

    /** @var DataProviderInterface */
    public $data_provider;

    public function __construct() {
        for ($i = 0; $i < 16; $i++) {
            $this->slots[] = null;
        }
        $this->data_key = new DataKey();
    }

    /**
     * @param DataProviderInterface $data_provider
     */
    public function reset($data_provider) {
        $this->data_provider = $data_provider;
    }
}
