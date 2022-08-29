<?php

namespace KTemplate;

class ArrayDataProvider implements DataProviderInterface {
    /** @var mixed $data */
    private $data;

    /**
     * @param mixed $data
     */
    public function __construct($data) {
        $this->data = $data;
    }

    public function getData($key) {
        switch ($key->num_parts) {
        case 1:
            return $this->data[$key->part1];
        case 2:
            return $this->data[$key->part1][$key->part2];
        default:
            return $this->data[$key->part1][$key->part2][$key->part3];
        }
    }
}
