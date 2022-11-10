<?php

namespace KTemplate\Internal;

use KTemplate\DataKey;
use KTemplate\Context;
use KTemplate\Template;
use KTemplate\DataProviderInterface;

class Renderer {
    /**
     * @var Template
     */
    private static $empty_template = null;

    /** @var RendererState */
    private $state;

    /** @var Env */
    private $env;

    /** @var Context */
    private $ctx;

    /**
     * @param Env $env
     */
    public function __construct($env) {
        if (self::$empty_template === null) {
            self::$empty_template = new Template();
            self::$empty_template->setExtraInfo(0, 0, 0);
        }
        $this->state = new RendererState();
        $this->env = $env;
        $this->ctx = $env->ctx;
    }

    /**
     * @param Template $t
     * @param DataProviderInterface $data_provider
     * @return string
     */
    public function render($t, $data_provider) {
        $this->state->reset($data_provider);
        $this->prepareTemplateFrame($t, 0);
        $this->execTemplate(self::$empty_template, $t);
        $this->state->clearSlots();
        return $this->state->buf;
    }

    /**
     * @param Template $t
     */
    private function prepareTemplateFrame($t, $slot_offset) {
        $state = $this->state;

        $need_slots = $t->frameSize() + $t->frameArgsSize() + $slot_offset;
        if ($need_slots > $state->slots_used) {
            $state->slots_used = $need_slots;
        }
        $state->reserve($need_slots);

        // Now bind the default template param values.
        $slot_offset++; // Skip slot0
        $i = 0;
        foreach ($t->params as $v) {
            $state->slots[$slot_offset + $i] = $v;
            $i++;
        }
    }

    /**
     * @param Template $current_template
     * @param Template $t
     */
    private function execTemplate($current_template, $t) {
        $cache_bitset = $this->state->cache_bitset;
        $this->state->cache_bitset = 0;
        $this->state->slot_offset += $current_template->frameSize();
        $this->eval($t);
        $this->state->slot_offset -= $current_template->frameSize();
        $this->state->cache_bitset = $cache_bitset;
    }

    /**
     * @param Template $t
     * @param int $pc
     */
    private function eval($t, $pc = 0) {
        $state = $this->state;
        $key = $this->state->data_key;
        $fp = $state->slot_offset; // fp stands for "frame pointer"
        $code = $t->code;

        /** @var mixed $slot0 */
        $slot0 = null;

        while (true) {
            $opdata = $code[$pc];
            $op = $opdata & 0xff;
            $pc++;

            switch ($op) {
            case Op::RETURN:
                return;

            case Op::OUTPUT:
                $state->buf .= $this->escape((string)$state->slots[$fp + (($opdata >> 8) & 0xff)]);
                break;
            case Op::OUTPUT_SLOT0:
                $state->buf .= $this->escape((string)$slot0);
                break;
            case Op::OUTPUT_SAFE:
                $state->buf .= $state->slots[$fp + (($opdata >> 8) & 0xff)];
                break;
            case Op::OUTPUT_SAFE_SLOT0:
                $state->buf .= $slot0;
                break;
            case Op::OUTPUT_SAFE_STRING_CONST:
                $state->buf .= $t->string_values[($opdata >> 8) & 0xffff];
                break;
            case Op::OUTPUT_STRING_CONST:
                $state->buf .= $this->escape($t->string_values[($opdata >> 8) & 0xffff]);
                break;
            case Op::OUTPUT_SAFE_INT_CONST:
                $state->buf .= $t->int_values[($opdata >> 8) & 0xffff];
                break;
            case Op::OUTPUT_EXTDATA_1:
                $cache_slot = ($opdata >> 8) & 0xff;
                $cache_mask = 1 << ($cache_slot - 1);
                $escape_bit = ($opdata >> 24) & 0xff;
                if (($state->cache_bitset & $cache_mask) !== 0) {
                    $state->buf .= $escape_bit ? $this->escape((string)$state->slots[$cache_slot]) : $state->slots[$cache_slot];
                    break;
                }
                $key_offset = ($opdata >> 16) & 0xff;
                $key->num_parts = 1;
                $key->part1 = $t->keys[$key_offset];
                $v = $state->data_provider->getData($key);
                $state->cache_bitset |= $cache_mask;
                $state->slots[$cache_slot] = $v;
                $state->buf .= $escape_bit ? $this->escape((string)$v) : $v;
                break;
            case Op::OUTPUT_EXTDATA_2:
                $cache_slot = ($opdata >> 8) & 0xff;
                $cache_mask = 1 << ($cache_slot - 1);
                $escape_bit = ($opdata >> 24) & 0xff;
                if (($state->cache_bitset & $cache_mask) !== 0) {
                    $state->buf .= $escape_bit ? $this->escape((string)$state->slots[$cache_slot]) : $state->slots[$cache_slot];
                    break;
                }
                $key_offset = ($opdata >> 16) & 0xff;
                $key->num_parts = 2;
                $key->part1 = $t->keys[$key_offset];
                $key->part2 = $t->keys[$key_offset+1];
                $v = $state->data_provider->getData($key);
                $state->cache_bitset |= $cache_mask;
                $state->slots[$cache_slot] = $v;
                $state->buf .= $escape_bit ? $this->escape((string)$v) : $v;
                break;
            case Op::OUTPUT_EXTDATA_3:
                $cache_slot = ($opdata >> 8) & 0xff;
                $cache_mask = 1 << ($cache_slot - 1);
                $escape_bit = ($opdata >> 24) & 0xff;
                if (($state->cache_bitset & $cache_mask) !== 0) {
                    $state->buf .= $escape_bit ? $this->escape((string)$state->slots[$cache_slot]) : $state->slots[$cache_slot];
                    break;
                }
                $key_offset = ($opdata >> 16) & 0xff;
                $key->num_parts = 3;
                $key->part1 = $t->keys[$key_offset];
                $key->part2 = $t->keys[$key_offset+1];
                $key->part3 = $t->keys[$key_offset+2];
                $v = $state->data_provider->getData($key);
                $state->cache_bitset |= $cache_mask;
                $state->slots[$cache_slot] = $v;
                $state->buf .= $escape_bit ? $this->escape((string)$v) : $v;
                break;
            
            case Op::MOVE:
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = $state->slots[$fp + (($opdata >> 16) & 0xff)];
                break;
            case Op::MOVE_SLOT0:
                $slot0 = $state->slots[$fp + (($opdata >> 8) & 0xff)];
                break;
            case Op::MOVE_BOOL:
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = (bool)$state->slots[$fp + (($opdata >> 16) & 0xff)];
                break;
            case Op::MOVE_SLOT0_BOOL:
                $slot0 = (bool)$state->slots[$fp + (($opdata >> 8) & 0xff)];
                break;

            case Op::CONV_BOOL:
                $slot = ($opdata >> 8) & 0xff;
                $state->slots[$slot] = (bool)$state->slots[$slot];
                break;
            case Op::CONV_SLOT0_BOOL:
                $slot0 = (bool)$slot0;
                break;

            case Op::LOAD_BOOL:
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = (bool)(($opdata >> 16) & 0xff);
                break;
            case Op::LOAD_SLOT0_BOOL:
                $slot0 = (bool)(($opdata >> 8) & 0xff);
                break;
            case Op::LOAD_INT_CONST:
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = $t->int_values[($opdata >> 16) & 0xffff];
                break;
            case Op::LOAD_SLOT0_INT_CONST:
                $slot0 = (int)$t->int_values[($opdata >> 8) & 0xffff];
                break;
            case Op::LOAD_FLOAT_CONST:
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = $t->float_values[($opdata >> 16) & 0xff];
                break;
            case Op::LOAD_SLOT0_FLOAT_CONST:
                $slot0 = (float)$t->float_values[($opdata >> 8) & 0xff];
                break;
            case Op::LOAD_STRING_CONST:
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = $t->string_values[($opdata >> 16) & 0xffff];
                break;
            case Op::LOAD_SLOT0_STRING_CONST:
                $slot0 = (string)$t->string_values[($opdata >> 8) & 0xffff];
                break;
            case Op::LOAD_NULL:
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = null;
                break;
            case Op::LOAD_SLOT0_NULL:
                $slot0 = null;
                break;
            case Op::LOAD_EXTDATA_1:
                $dst_slot = ($opdata >> 8) & 0xff;
                $cache_slot = ($opdata >> 16) & 0xff;
                $cache_mask = 1 << ($cache_slot - 1);
                if (($state->cache_bitset & $cache_mask) !== 0) {
                    $state->slots[$dst_slot] = $state->slots[$cache_slot];
                    break;
                }
                $key_offset = ($opdata >> 24) & 0xff;
                $key->num_parts = 1;
                $key->part1 = $t->keys[$key_offset];
                $v = $state->data_provider->getData($key);
                $state->cache_bitset |= $cache_mask;
                $state->slots[$cache_slot] = $v;
                $state->slots[$dst_slot] = $v;
                break;
            case Op::LOAD_SLOT0_EXTDATA_1:
                $cache_slot = ($opdata >> 8) & 0xff;
                $cache_mask = 1 << ($cache_slot - 1);
                if (($state->cache_bitset & $cache_mask) !== 0) {
                    $slot0 = $state->slots[$cache_slot];
                    break;
                }
                $key_offset = ($opdata >> 16) & 0xff;
                $key->num_parts = 1;
                $key->part1 = $t->keys[$key_offset];
                $v = $state->data_provider->getData($key);
                $state->cache_bitset |= $cache_mask;
                $state->slots[$cache_slot] = $v;
                $slot0 = $v;
                break;
            case Op::LOAD_EXTDATA_2:
                $dst_slot = ($opdata >> 8) & 0xff;
                $cache_slot = ($opdata >> 16) & 0xff;
                $cache_mask = 1 << ($cache_slot - 1);
                if (($state->cache_bitset & $cache_mask) !== 0) {
                    $state->slots[$dst_slot] = $state->slots[$cache_slot];
                    break;
                }
                $key_offset = ($opdata >> 24) & 0xff;
                $key->num_parts = 2;
                $key->part1 = $t->keys[$key_offset];
                $key->part2 = $t->keys[$key_offset+1];
                $v = $state->data_provider->getData($key);
                $state->cache_bitset |= $cache_mask;
                $state->slots[$cache_slot] = $v;
                $state->slots[$dst_slot] = $v;
                break;
            case Op::LOAD_SLOT0_EXTDATA_2:
                $cache_slot = ($opdata >> 8) & 0xff;
                $cache_mask = 1 << ($cache_slot - 1);
                if (($state->cache_bitset & $cache_mask) !== 0) {
                    $slot0 = $state->slots[$cache_slot];
                    break;
                }
                $key_offset = ($opdata >> 16) & 0xff;
                $key->num_parts = 2;
                $key->part1 = $t->keys[$key_offset];
                $key->part2 = $t->keys[$key_offset+1];
                $v = $state->data_provider->getData($key);
                $state->cache_bitset |= $cache_mask;
                $state->slots[$cache_slot] = $v;
                $slot0 = $v;
                break;
            case Op::LOAD_EXTDATA_3:
                $dst_slot = ($opdata >> 8) & 0xff;
                $cache_slot = ($opdata >> 16) & 0xff;
                $cache_mask = 1 << ($cache_slot - 1);
                if (($state->cache_bitset & $cache_mask) !== 0) {
                    $state->slots[$dst_slot] = $state->slots[$cache_slot];
                    break;
                }
                $key_offset = ($opdata >> 24) & 0xff;
                $key->num_parts = 3;
                $key->part1 = $t->keys[$key_offset];
                $key->part2 = $t->keys[$key_offset+1];
                $key->part3 = $t->keys[$key_offset+2];
                $v = $state->data_provider->getData($key);
                $state->cache_bitset |= $cache_mask;
                $state->slots[$cache_slot] = $v;
                $state->slots[$dst_slot] = $v;
                break;
            case Op::LOAD_SLOT0_EXTDATA_3:
                $cache_slot = ($opdata >> 8) & 0xff;
                $cache_mask = 1 << ($cache_slot - 1);
                if (($state->cache_bitset & $cache_mask) !== 0) {
                    $slot0 = $state->slots[$cache_slot];
                    break;
                }
                $key_offset = ($opdata >> 16) & 0xff;
                $key->num_parts = 3;
                $key->part1 = $t->keys[$key_offset];
                $key->part2 = $t->keys[$key_offset+1];
                $key->part3 = $t->keys[$key_offset+2];
                $v = $state->data_provider->getData($key);
                $state->cache_bitset |= $cache_mask;
                $state->slots[$cache_slot] = $v;
                $slot0 = $v;
                break;
            
            case Op::INDEX_STRING_KEY:
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = $state->slots[$fp + (($opdata >> 16) & 0xff)][$t->string_values[($opdata >> 24) & 0xffff]];
                break;
            case Op::INDEX_SLOT0_STRING_KEY:
                $slot0 = $state->slots[$fp + (($opdata >> 8) & 0xff)][$t->string_values[($opdata >> 16) & 0xffff]];
                break;
            case Op::INDEX_INT_KEY:
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = $state->slots[$fp + (($opdata >> 16) & 0xff)][$t->int_values[($opdata >> 24) & 0xffff]];
                break;
            case Op::INDEX_SLOT0_INT_KEY:
                $slot0 = $state->slots[$fp + (($opdata >> 8) & 0xff)][$t->int_values[($opdata >> 16) & 0xffff]];
                break;
            case Op::INDEX:
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = $state->slots[$fp + (($opdata >> 16) & 0xff)][$state->slots[$fp + (($opdata >> 24) & 0xff)]];
                break;
            case Op::INDEX_SLOT0:
                $slot0 = $state->slots[$fp + (($opdata >> 8) & 0xff)][$state->slots[$fp + (($opdata >> 16) & 0xff)]];
                break;
            
            case Op::JUMP:
                $pc += ($opdata >> 8) & 0xffff;
                break;
            case Op::JUMP_FALSY:
                if (!$state->slots[$fp + (($opdata >> 24) & 0xff)]) {
                    $pc += ($opdata >> 8) & 0xffff;
                }
                break;
            case Op::JUMP_SLOT0_FALSY:
                if (!$slot0) {
                    $pc += ($opdata >> 8) & 0xffff;
                }
                break;
            case Op::JUMP_TRUTHY:
                if ($state->slots[$fp + (($opdata >> 24) & 0xff)]) {
                    $pc += ($opdata >> 8) & 0xffff;
                }
                break;
            case Op::JUMP_SLOT0_TRUTHY:
                if ($slot0) {
                    $pc += ($opdata >> 8) & 0xffff;
                }
                break;
            case Op::JUMP_NOT_NULL:
                if ($state->slots[$fp + (($opdata >> 24) & 0xff)] !== null) {
                    $pc += ($opdata >> 8) & 0xffff;
                }
                break;
            case Op::JUMP_SLOT0_NOT_NULL:
                if ($slot0 !== null) {
                    $pc += ($opdata >> 8) & 0xffff;
                }
                break;

            case Op::FOR_VAL:
                $val_slot = ($opdata >> 24) & 0xff;
                foreach ($slot0 as $v) {
                    $state->slots[$val_slot] = $v;
                    $this->eval($t, $pc);
                }
                $slot0 = count($slot0) !== 0;
                $pc += ($opdata >> 8) & 0xffff;
                break;
            case Op::FOR_KEY_VAL:
                $key_slot = ($opdata >> 24) & 0xff;
                $val_slot = ($opdata >> 32) & 0xff;
                foreach ($slot0 as $k => $v) {
                    $state->slots[$key_slot] = $k;
                    $state->slots[$val_slot] = $v;
                    $this->eval($t, $pc);
                }
                $slot0 = count($slot0) !== 0;
                $pc += ($opdata >> 8) & 0xffff;
                break;

            case Op::NOT:
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = !$state->slots[$fp + (($opdata >> 16) & 0xff)];
                break;
            case Op::NOT_SLOT0:
                $slot0 = !$state->slots[$fp + (($opdata >> 8) & 0xff)];
                break;

            case Op::OR:
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = $state->slots[$fp + (($opdata >> 16) & 0xff)] || $state->slots[$fp + (($opdata >> 24) & 0xff)];
                break;
            case Op::OR_SLOT0:
                $slot0 = $state->slots[$fp + (($opdata >> 8) & 0xff)] || $state->slots[$fp + (($opdata >> 16) & 0xff)];
                break;
            case Op::AND:
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = $state->slots[$fp + (($opdata >> 16) & 0xff)] && $state->slots[$fp + (($opdata >> 24) & 0xff)];
                break;
            case Op::AND_SLOT0:
                $slot0 = $state->slots[$fp + (($opdata >> 8) & 0xff)] && $state->slots[$fp + (($opdata >> 16) & 0xff)];
                break;
            case Op::CONCAT:
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = $state->slots[$fp + (($opdata >> 16) & 0xff)] . $state->slots[$fp + (($opdata >> 24) & 0xff)];
                break;
            case Op::CONCAT_SLOT0:
                $slot0 = $state->slots[$fp + (($opdata >> 8) & 0xff)] . $state->slots[$fp + (($opdata >> 16) & 0xff)];
                break;
            case Op::EQ:
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = $state->slots[$fp + (($opdata >> 16) & 0xff)] == $state->slots[$fp + (($opdata >> 24) & 0xff)];
                break;
            case Op::EQ_SLOT0:
                $slot0 = $state->slots[$fp + (($opdata >> 8) & 0xff)] == $state->slots[$fp + (($opdata >> 16) & 0xff)];
                break;
            case Op::LT:
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = $state->slots[$fp + (($opdata >> 16) & 0xff)] < $state->slots[$fp + (($opdata >> 24) & 0xff)];
                break;
            case Op::LT_SLOT0:
                $slot0 = $state->slots[$fp + (($opdata >> 8) & 0xff)] < $state->slots[$fp + (($opdata >> 16) & 0xff)];
                break;
            case Op::LT_EQ:
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = $state->slots[$fp + (($opdata >> 16) & 0xff)] <= $state->slots[$fp + (($opdata >> 24) & 0xff)];
                break;
            case Op::LT_EQ_SLOT0:
                $slot0 = $state->slots[$fp + (($opdata >> 8) & 0xff)] <= $state->slots[$fp + (($opdata >> 16) & 0xff)];
                break;
            case Op::NOT_EQ:
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = $state->slots[$fp + (($opdata >> 16) & 0xff)] != $state->slots[$fp + (($opdata >> 24) & 0xff)];
                break;
            case Op::NOT_EQ_SLOT0:
                $slot0 = $state->slots[$fp + (($opdata >> 8) & 0xff)] != $state->slots[$fp + (($opdata >> 16) & 0xff)];
                break;
            case Op::ADD:
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = $state->slots[$fp + (($opdata >> 16) & 0xff)] + $state->slots[$fp + (($opdata >> 24) & 0xff)];
                break;
            case Op::ADD_SLOT0:
                $slot0 = $state->slots[$fp + (($opdata >> 8) & 0xff)] + $state->slots[$fp + (($opdata >> 16) & 0xff)];
                break;
            case Op::SUB:
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = $state->slots[$fp + (($opdata >> 16) & 0xff)] - $state->slots[$fp + (($opdata >> 24) & 0xff)];
                break;
            case Op::SUB_SLOT0:
                $slot0 = $state->slots[$fp + (($opdata >> 8) & 0xff)] - $state->slots[$fp + (($opdata >> 16) & 0xff)];
                break;
            case Op::MUL:
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = $state->slots[$fp + (($opdata >> 16) & 0xff)] * $state->slots[$fp + (($opdata >> 24) & 0xff)];
                break;
            case Op::MUL_SLOT0:
                $slot0 = $state->slots[$fp + (($opdata >> 8) & 0xff)] * $state->slots[$fp + (($opdata >> 16) & 0xff)];
                break;
            case Op::QUO:
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = $state->slots[$fp + (($opdata >> 16) & 0xff)] / $state->slots[$fp + (($opdata >> 24) & 0xff)];
                break;
            case Op::QUO_SLOT0:
                $slot0 = $state->slots[$fp + (($opdata >> 8) & 0xff)] / $state->slots[$fp + (($opdata >> 16) & 0xff)];
                break;
            case Op::MOD:
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = $state->slots[$fp + (($opdata >> 16) & 0xff)] % $state->slots[$fp + (($opdata >> 24) & 0xff)];
                break;
            case Op::MOD_SLOT0:
                $slot0 = $state->slots[$fp + (($opdata >> 8) & 0xff)] % $state->slots[$fp + (($opdata >> 16) & 0xff)];
                break;

            case Op::CALL_FILTER1:
                $arg1 = $state->slots[$fp + (($opdata >> 16) & 0xff)];
                $filter_id = ($opdata >> 24) & 0xffff;
                $filter1 = $this->env->filters1[$filter_id];
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = $filter1($arg1);
                break;
            case Op::CALL_SLOT0_FILTER1:
                $arg1 = $state->slots[$fp + (($opdata >> 8) & 0xff)];
                $filter_id = ($opdata >> 16) & 0xffff;
                $filter1 = $this->env->filters1[$filter_id];
                $slot0 = $filter1($arg1);
                break;
            case Op::CALL_FILTER2:
                $arg1 = $state->slots[$fp + (($opdata >> 16) & 0xff)];
                $arg2 = $state->slots[$fp + (($opdata >> 24) & 0xff)];
                $filter_id = ($opdata >> 32) & 0xffff;
                $filter2 = $this->env->filters2[$filter_id];
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = $filter2($arg1, $arg2);
                break;
            case Op::CALL_SLOT0_FILTER2:
                $arg1 = $state->slots[$fp + (($opdata >> 8) & 0xff)];
                $arg2 = $state->slots[$fp + (($opdata >> 16) & 0xff)];
                $filter_id = ($opdata >> 24) & 0xffff;
                $filter2 = $this->env->filters2[$filter_id];
                $slot0 = $filter2($arg1, $arg2);
                break;
            case Op::CALL_FUNC0:
                $func_id = ($opdata >> 16) & 0xffff;
                $func0 = $this->env->funcs0[$func_id];
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = $func0();
                break;
            case Op::CALL_SLOT0_FUNC0:
                $func_id = ($opdata >> 8) & 0xffff;
                $func0 = $this->env->funcs0[$func_id];
                $slot0 = $func0();
                break;
            case Op::CALL_FUNC1:
                $arg1 = $state->slots[$fp + (($opdata >> 16) & 0xff)];
                $func_id = ($opdata >> 24) & 0xffff;
                $func1 = $this->env->funcs1[$func_id];
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = $func1($arg1);
                break;
            case Op::CALL_SLOT0_FUNC1:
                $arg1 = $state->slots[$fp + (($opdata >> 8) & 0xff)];
                $func_id = ($opdata >> 16) & 0xffff;
                $func1 = $this->env->funcs1[$func_id];
                $slot0 = $func1($arg1);
                break;
            case Op::CALL_FUNC2:
                $arg1 = $state->slots[$fp + (($opdata >> 16) & 0xff)];
                $arg2 = $state->slots[$fp + (($opdata >> 24) & 0xff)];
                $func_id = ($opdata >> 32) & 0xffff;
                $func2 = $this->env->funcs2[$func_id];
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = $func2($arg1, $arg2);
                break;
            case Op::CALL_SLOT0_FUNC2:
                $arg1 = $state->slots[$fp + (($opdata >> 8) & 0xff)];
                $arg2 = $state->slots[$fp + (($opdata >> 16) & 0xff)];
                $func_id = ($opdata >> 24) & 0xffff;
                $func2 = $this->env->funcs2[$func_id];
                $slot0 = $func2($arg1, $arg2);
                break;
            case Op::CALL_FUNC3:
                $arg1 = $state->slots[$fp + (($opdata >> 16) & 0xff)];
                $arg2 = $state->slots[$fp + (($opdata >> 24) & 0xff)];
                $arg3 = $state->slots[$fp + (($opdata >> 32) & 0xff)];
                $func_id = ($opdata >> 40) & 0xffff;
                $func3 = $this->env->funcs3[$func_id];
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = $func3($arg1, $arg2, $arg3);
                break;
            case Op::CALL_SLOT0_FUNC3:
                $arg1 = $state->slots[$fp + (($opdata >> 8) & 0xff)];
                $arg2 = $state->slots[$fp + (($opdata >> 16) & 0xff)];
                $arg3 = $state->slots[$fp + (($opdata >> 24) & 0xff)];
                $func_id = ($opdata >> 32) & 0xffff;
                $func3 = $this->env->funcs3[$func_id];
                $slot0 = $func3($arg1, $arg2, $arg3);
                break;
            case Op::LENGTH_FILTER:
                $arg = $state->slots[$fp + (($opdata >> 16) & 0xff)];
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = $this->lengthFilter($arg);
                break;
            case Op::LENGTH_SLOT0_FILTER:
                $arg = $state->slots[$fp + (($opdata >> 8) & 0xff)];
                $slot0 = $this->lengthFilter($arg);
                break;
            case Op::DEFAULT_FILTER:
                $arg1 = $state->slots[$fp + (($opdata >> 16) & 0xff)];
                $arg2 = $state->slots[$fp + (($opdata >> 24) & 0xff)];
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = self::defaultFilter($arg1, $arg2);
                break;
            case Op::DEFAULT_SLOT0_FILTER:
                $arg1 = $state->slots[$fp + (($opdata >> 8) & 0xff)];
                $arg2 = $state->slots[$fp + (($opdata >> 16) & 0xff)];
                $slot0 = self::defaultFilter($arg1, $arg2);
                break;
            case Op::ESCAPE_FILTER1:
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = $this->escape((string)$state->slots[$fp + (($opdata >> 16) & 0xff)]);
                break;
            case Op::ESCAPE_SLOT0_FILTER1:
                $slot0 = $this->escape((string)$state->slots[$fp + (($opdata >> 8) & 0xff)]);
                break;
            case Op::ESCAPE_FILTER2:
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = $this->escapeWithStrategy((string)$state->slots[$fp + (($opdata >> 16) & 0xff)], $t->string_values[($opdata >> 24) & 0xffff]);
                break;
            case Op::ESCAPE_SLOT0_FILTER2:
                $slot0 = $this->escapeWithStrategy((string)$state->slots[$fp + (($opdata >> 8) & 0xff)], $t->string_values[($opdata >> 16) & 0xffff]);
                break;

            case Op::MATCHES:
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = 1 === preg_match($t->string_values[($opdata >> 24) & 0xffff], $state->slots[$fp + (($opdata >> 16) & 0xff)]);
                break;
            case Op::MATCHES_SLOT0:
                $slot0 = 1 === preg_match($t->string_values[($opdata >> 16) & 0xffff], $state->slots[$fp + (($opdata >> 8) & 0xff)]);
                break;
            
            case Op::START_TMP_OUTPUT:
                $state->swapBuf();
                break;
            case Op::FINISH_TMP_OUTPUT:
                $state->slots[$fp + (($opdata >> 8) & 0xff)] = $state->buf;
                $state->swapBuf();
                $state->buf2 = '';
                break;

            case Op::PREPARE_TEMPLATE:
                $state->template = $this->env->getTemplate($t->string_values[($opdata >> 8) & 0xffff]);
                $this->prepareTemplateFrame($state->template, $fp + $t->frameSize());
                break;
            case Op::INCLUDE_TEMPLATE:
                $this->execTemplate($t, $state->template);
                break;

            default:
                return;
            }
        }
    }

    /**
     * @param string $x
     * @return string
     */
    private function escape($x) {
        $escape_filter = $this->ctx->escape_func;
        return $escape_filter($x, $this->ctx->default_escape_strategy);
    }

    /**
     * @param string $x
     * @param string $strategy
     * @return string
     */
    private function escapeWithStrategy($x, $strategy) {
        $escape_filter = $this->ctx->escape_func;
        return $escape_filter($x, $strategy);
    }

    /**
     * @param mixed $x
     * @return bool
     */
    private static function isEmptyPredicate($x) {
        return $x === '' || $x === false || $x === null || $x === [];
    }

    /**
     * @param mixed $x
     * @param mixed $default
     * @return mixed
     */
    private static function defaultFilter($x, $default) {
        if (self::isEmptyPredicate($x)) {
            return $default;
        }
        return $x;
    }

    /**
     * @param mixed $x
     * @return int
     */
    private function lengthFilter($x) {
        if (is_string($x)) {
            return mb_strlen($x, $this->ctx->encoding);
        }
        if (is_array($x)) {
            return count($x);
        }
        return 0;
    }
}