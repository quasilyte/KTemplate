<?php

namespace KTemplate;

class Env {
    /** @var (callable(mixed):mixed)[] */
    public $filters1 = [];
    /** @var int[] */
    private $filter1_id_by_name = [];
    /** @var (callable(mixed,mixed):mixed)[] */
    public $filters2 = [];
    /** @var int[] */
    private $filter2_id_by_name = [];

    /** @var (callable():mixed)[] */
    public $funcs0 = [];
    /** @var int[] */
    private $func0_id_by_name = [];
    /** @var (callable(mixed):mixed)[] */
    public $funcs1 = [];
    /** @var int[] */
    private $func1_id_by_name = [];
    /** @var (callable(mixed,mixed):mixed)[] */
    public $funcs2 = [];
    /** @var int[] */
    private $func2_id_by_name = [];
    /** @var (callable(mixed,mixed,mixed):mixed)[] */
    public $funcs3 = [];
    /** @var int[] */
    private $func3_id_by_name = [];

    /**
     * Implied text encoding.
     * Used as an argument to mb_* functions.
     * 
     * UTF-8 is used by default.
     * 
     * @var string
     */
    public $encoding = 'UTF-8';

    /**
     * @param string $name
     * @param callable():mixed $fn
     */
    public function registerFunction0($name, $fn) {
        $id = count($this->funcs0);
        $this->funcs0[] = $fn;
        $this->func0_id_by_name[$name] = $id;
    }

    /**
     * @param string $name
     * @param callable(mixed):mixed $fn
     */
    public function registerFunction1($name, $fn) {
        $id = count($this->funcs1);
        $this->funcs1[] = $fn;
        $this->func1_id_by_name[$name] = $id;
    }

    /**
     * @param string $name
     * @param callable(mixed,mixed):mixed $fn
     */
    public function registerFunction2($name, $fn) {
        $id = count($this->funcs2);
        $this->funcs2[] = $fn;
        $this->func2_id_by_name[$name] = $id;
    }

    /**
     * @param string $name
     * @param callable(mixed,mixed,mixed):mixed $fn
     */
    public function registerFunction3($name, $fn) {
        $id = count($this->funcs3);
        $this->funcs3[] = $fn;
        $this->func3_id_by_name[$name] = $id;
    }

    /**
     * @param string $name
     * @param callable(mixed):mixed $fn
     */
    public function registerFilter1($name, $fn) {
        $id = count($this->filters1);
        $this->filters1[] = $fn;
        $this->filter1_id_by_name[$name] = $id;
    }

    /**
     * @param string $name
     * @param callable(mixed,mixed):mixed $fn
     */
    public function registerFilter2($name, $fn) {
        $id = count($this->filters2);
        $this->filters2[] = $fn;
        $this->filter2_id_by_name[$name] = $id;
    }

    /**
     * @param int $id
     * @return string
     */
    public function getFilter1Name($id) {
        return (string)array_search($id, $this->filter1_id_by_name);
    }

    /**
     * @param int $id
     * @return string
     */
    public function getFilter2Name($id) {
        return (string)array_search($id, $this->filter2_id_by_name);
    }

    /**
     * @param string $name
     * @return int
     */
    public function getFilter1ID($name) {
        return $this->filter1_id_by_name[$name] ?? -1;
    }

    /**
     * @param string $name
     * @return int
     */
    public function getFilter2ID($name) {
        return $this->filter2_id_by_name[$name] ?? -1;
    }
}
