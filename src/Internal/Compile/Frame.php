<?php

namespace KTemplate\Internal\Compile;

use KTemplate\Template;
use KTemplate\DataKey;
use KTemplate\Internal\Assert;

class Frame {
    public const ARG_SLOT_PLACEHOLDER = 255;

    /** @var int */
    public $num_locals;

    /** @var int */
    public $max_num_args;

    /** @var bool[] */
    private $passed_args = [];
    
    /** @var int[] */
    public $cache_slots = [];

    /** @var Template */
    private $template;
    /** @var int */
    private $num_temps;
    /** @var bool */
    private $in_temp_block;
    /** @var int */
    private $id_seq;
    /** @var string[] */
    private $vars = [];
    /** @var int[] */
    private $var_ids = [];
    /** @var int[] */
    private $depths = [];
    /** @var DataKey[] */
    private $data_keys = [];
    /** @var int */
    private $num_data_keys_used = 0;

    public function __construct() {
        $this->data_keys = [
            new DataKey(),
            new DataKey(),
            new DataKey(),
            new DataKey(),
        ];
    }

    /**
     * @param Template $template
     */
    public function reset($template) {
        $this->template = $template;
        $this->num_locals = 1; // Includes slot0
        $this->max_num_args = 0;
        $this->passed_args = [];
        $this->cache_slots = [];
        $this->id_seq = 1;
        $this->num_temps = 0;
        $this->in_temp_block = false;
        $this->popVars(count($this->vars));
        $this->popVarIDs(count($this->var_ids));
        $this->popDepths(count($this->depths));
        $this->num_data_keys_used = 0;
    }

    public function enterTemplateCall() {
        $this->passed_args = [];
    }

    /**
     * @var string $arg_name
     * @return bool
     */
    public function addTemplateArg($arg_name) {
        if (isset($this->passed_args[$arg_name])) {
            return false;
        }
        $this->passed_args[$arg_name] = true;
        return true;
    }

    public function leaveTemplateCall() {
        if (count($this->passed_args) > $this->max_num_args) {
            $this->max_num_args = count($this->passed_args);
        }
    }

    public function enterTempBlock() {
        Assert::true(!$this->in_temp_block, 'nested temp block');
        $this->in_temp_block = true;
    }

    public function leaveTempBlock() {
        Assert::true($this->in_temp_block, 'leaving a non-entered temp block');
        $this->in_temp_block = false;
        $this->id_seq -= $this->num_temps;
        $this->num_temps = 0;
        $this->num_data_keys_used = 0;
    }

    public function enterScope() {
        $this->depths[] = 0;
    }

    public function leaveScope() {
        $depth = $this->depths[count($this->depths)-1];
        array_pop($this->depths);
        $this->popVars($depth);
        $this->popVarIDs($depth);
    }

    /**
     * @param string $name
     * @return int
     */
    public function lookupLocalInCurrentScope($name) {
        $depth = $this->depths[count($this->depths)-1];
        $num_vars = count($this->vars);
        $scope_bottom = $num_vars - $depth;
        for ($i = count($this->vars)-1; $i >= $scope_bottom; $i--) {
            if ($this->vars[$i] === $name) {
                return $this->var_ids[$i];
            }
        }
        return -1;
    }

    /**
     * @param string $name
     * @return int
     */
    public function lookupLocal($name) {
        for ($i = count($this->vars)-1; $i >= 0; $i--) {
            if ($this->vars[$i] === $name) {
                return $this->var_ids[$i];
            }
        }
        return -1;
    }

    /**
     * @param string $p1
     * @param string $p2
     * @param string $p3
     * @return int
     */
    public function lookupExtdataSlot($p1, $p2, $p3) {
        for ($i = 0; $i < $this->num_data_keys_used; $i++) {
            $k = $this->data_keys[$i];
            if ($p1 === $k->part1 && $p2 === $k->part2 && $p3 === $k->part3) {
                return $k->num_parts; // Actually holds a cache slot ID
            }
        }
        return -1;
    }

    /**
     * @param int $slot
     * @param string $p1
     * @param string $p2
     * @param string $p3
     */
    public function saveExtdataSlot($slot, $p1, $p2, $p3) {
        if ($this->num_data_keys_used >= count($this->data_keys)) {
            return;
        }
        $k = $this->data_keys[$this->num_data_keys_used];
        $this->num_data_keys_used++;
        $k->num_parts = $slot;
        $k->part1 = $p1;
        $k->part2 = $p2;
        $k->part3 = $p3;
    }

    /**
     * TODO: just return a tuple(int,int)?
     *
     * @param string $p1
     * @param string $p2
     * @param string $p3
     * @return int
     */
    public function getCacheSlotInfo($p1, $p2, $p3) {
        $key = $p1;
        if ($p2 !== '' && $p3 !== '') {
            $key = "$p1.$p2.$p3";
        } else if ($p2 !== '') {
            $key = "$p1.$p2";
        }

        if (array_key_exists($key, $this->cache_slots)) {
            return $this->cache_slots[$key];
        }
        $id = count($this->cache_slots) + 1;
        $key_offset = count($this->template->keys);
        $slot_info = $id | ($key_offset << 8);
        $this->cache_slots[$key] = $slot_info;
        $this->template->keys[] = $p1;
        if ($p2 !== '') {
            $this->template->keys[] = $p2;
        }
        if ($p3 !== '') {
            $this->template->keys[] = $p3;
        }
        return $slot_info;
    }

    /**
     * @return int
     */
    public function allocSlot() {
        $id = $this->id_seq;
        $this->id_seq++;
        $this->trackID($id);
        return $id;
    }

    /**
     * @return int
     */
    public function allocTempSlot() {
        $id = $this->allocSlot();
        $this->num_temps++;
        return $id;
    }

    /**
     * @param string $name
     * @return int
     */
    public function allocVarSlot($name) {
        $id = $this->allocSlot();
        $this->pushVar($id, $name);
        return $id;
    }

    /**
     * @param int $id
     * @param string $name
     */
    private function pushVar($id, $name) {
        Assert::true(!$this->in_temp_block, "can't declare a var inside temp block");
        $this->depths[count($this->depths)-1]++;
        $this->vars[] = $name;
        $this->var_ids[] = $id;
    }

    /**
     * @param int $id
     */
    private function trackID($id) {
        if ($this->num_locals < $id + 1) {
            $this->num_locals = $id + 1;
        }
    }

    /**
     * @param int $n
     */
    private function popVarIDs($n) {
        array_splice($this->var_ids, count($this->var_ids) - $n, $n);
    }

    /**
     * @param int $n
     */
    private function popVars($n) {
        array_splice($this->vars, count($this->vars) - $n, $n);
    }

    /**
     * @param int $n
     */
    private function popDepths($n) {
        array_splice($this->depths, count($this->depths) - $n, $n);
    }
}
