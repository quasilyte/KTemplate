<?php

namespace KTemplate\Compile;

use KTemplate\Internal\Assert;

class Frame {
    /** @var int */
    public $num_locals;

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

    public function reset() {
        $this->num_locals = 1;
        $this->id_seq = 1;
        $this->num_temps;
        $this->in_temp_block = false;
        $this->popVars(count($this->vars));
        $this->popVarIDs(count($this->var_ids));
        $this->popDepths(count($this->depths));
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
        for ($i = 0; $i < $n; $i++) {
            array_pop($this->var_ids);
        }
    }

    /**
     * @param int $n
     */
    private function popVars($n) {
        for ($i = 0; $i < $n; $i++) {
            array_pop($this->vars);
        }
    }

    /**
     * @param int $n
     */
    private function popDepths($n) {
        for ($i = 0; $i < $n; $i++) {
            array_pop($this->depths);
        }
    }
}
