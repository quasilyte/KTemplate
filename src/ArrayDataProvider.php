<?php

namespace KTemplate;

/**
 * ArrayDataProvider implements a simple data mapping for templates.
 *
 * It turns every part of the DataKey into the array access:
 *
 *   "x"     => $data["x"]
 *   "x.y"   => $data["x"]["y"]
 *   "x.y.z" => $data["x"]["y"]["z"]
 */
class ArrayDataProvider implements DataProviderInterface {
    /** @var mixed $data */
    private $data;

    /**
     * @param mixed $data - the array that will be mapped
     */
    public function __construct($data) {
        if (!is_array($data)) {
            throw new \Exception("data should be an array");
        }
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
