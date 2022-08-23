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
     * @var EscapeConfig
     */
    public $escape_config;

    public $escape_default_strategy = 'html';

    /**
     * A function to be used for escape filter (both auto-escape and explicit).
     * This function signature is (string $s, string $strategy) => string.
     * The strategy could be 'html', 'url', etc.
     * 
     * By default, FilterLibrary::escape function is used.
     * 
     * If null, no escaping will be performed.
     * 
     * @var callable(string,string):string
     */
    public $escape_func;

    public function __construct() {
        $this->escape_config = new EscapeConfig();
        $this->escape_func = [FilterLibrary::class, 'escape'];
    }

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
     * @param int $arity
     * @return int
     */
    public function getFunctionID($name, $arity) {
        switch ($arity) {
        case 0:
            return $this->func0_id_by_name[$name] ?? -1;
        case 1:
            return $this->func1_id_by_name[$name] ?? -1;
        case 2:
            return $this->func2_id_by_name[$name] ?? -1;
        case 3:
            return $this->func3_id_by_name[$name] ?? -1;
        default:
            return -1;
        }
    }

    /**
     * @param int $id
     * @param int $arity
     * @return string
     */
    public function getFunctionName($id, $arity) {
        switch ($arity) {
            case 0:
                return (string)array_search($id, $this->func0_id_by_name);
            case 1:
                return (string)array_search($id, $this->func1_id_by_name);
            case 2:
                return (string)array_search($id, $this->func2_id_by_name);
            case 3:
                return (string)array_search($id, $this->func3_id_by_name);
            default:
                return '';
            }
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
     * @param int $arity
     * @return string
     */
    public function getFilterName($id, $arity) {
        switch ($arity) {
        case 1:
            return (string)array_search($id, $this->filter1_id_by_name);
        case 2:
            return (string)array_search($id, $this->filter2_id_by_name);
        default:
            return '';
        }
    }

    /**
     * @param string $name
     * @param int $arity
     * @return int
     */
    public function getFilterID($name, $arity) {
        switch ($arity) {
        case 1:
            return $this->filter1_id_by_name[$name] ?? -1;
        case 2:
            return $this->filter2_id_by_name[$name] ?? -1;
        default:
            return -1;
        }
    }
}
