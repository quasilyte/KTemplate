<?php

namespace KTemplate;

class Renderer {
    /** @var RendererState */
    private $state;

    public function __construct() {
        $this->state = new RendererState();
    }

    /**
     * @param Env $env
     * @param Template $t
     * @param DataProviderInterface $data_provider
     * @return string
     */
    public function render($env, $t, $data_provider) {
        $this->state->reset($data_provider);
        $this->doRender($env, $t);
        return $this->state->buf;
    }

    /**
     * @param Env $env
     * @param Template $t
     */
    private function doRender($env, $t) {
        $state = $this->state;
        $key = $this->state->data_key;
        $pc = 0;
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
                $state->buf .= $state->slots[($opdata >> 8) & 0xff];
                break;
            case Op::OUTPUT_SLOT0:
                $state->buf .= $slot0;
                break;
            case Op::OUTPUT_STRING_CONST:
                $state->buf .= $t->string_values[($opdata >> 8) & 0xff];
                break;
            case Op::OUTPUT_INT_CONST:
                $state->buf .= $t->int_values[($opdata >> 8) & 0xff];
                break;
            case Op::OUTPUT_EXTDATA_1:
                $cache_slot = ($opdata >> 8) & 0xff;
                $cache_mask = 1 << ($cache_slot - 1);
                if (($state->cache_bitset & $cache_mask) !== 0) {
                    $state->buf .= $state->slots[$cache_slot];
                    break;
                }
                $key_offset = ($opdata >> 16) & 0xff;
                $key->num_parts = 1;
                $key->part1 = $t->keys[$key_offset];
                $v = $state->data_provider->getData($key);
                $state->cache_bitset |= $cache_mask;
                $state->slots[$cache_slot] = $v;
                $state->buf .= $v;
                break;
            case Op::OUTPUT_EXTDATA_2:
                $cache_slot = ($opdata >> 8) & 0xff;
                $cache_mask = 1 << ($cache_slot - 1);
                if (($state->cache_bitset & $cache_mask) !== 0) {
                    $state->buf .= $state->slots[$cache_slot];
                    break;
                }
                $key_offset = ($opdata >> 16) & 0xff;
                $key->num_parts = 2;
                $key->part1 = $t->keys[$key_offset];
                $key->part2 = $t->keys[$key_offset+1];
                $v = $state->data_provider->getData($key);
                $state->cache_bitset |= $cache_mask;
                $state->slots[$cache_slot] = $v;
                $state->buf .= $v;
                break;
            case Op::OUTPUT_EXTDATA_3:
                $cache_slot = ($opdata >> 8) & 0xff;
                $cache_mask = 1 << ($cache_slot - 1);
                if (($state->cache_bitset & $cache_mask) !== 0) {
                    $state->buf .= $state->slots[$cache_slot];
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
                $state->buf .= $v;
                break;
            
            case Op::MOVE_BOOL:
                $state->slots[($opdata >> 8) & 0xff] = (bool)$state->slots[($opdata >> 16) & 0xff];
                break;
            case Op::MOVE_SLOT0_BOOL:
               $slot0 = (bool)$state->slots[($opdata >> 16) & 0xff];
                break;

            case Op::CONV_BOOL:
                $slot = ($opdata >> 8) & 0xff;
                $state->slots[$slot] = (bool)$state->slots[$slot];
                break;
            case Op::CONV_SLOT0_BOOL:
                $slot0 = (bool)$slot0;
                break;

            case Op::LOAD_BOOL:
                $state->slots[($opdata >> 8) & 0xff] = (bool)(($opdata >> 16) & 0xff);
                break;
            case Op::LOAD_SLOT0_BOOL:
                $slot0 = (bool)(($opdata >> 8) & 0xff);
                break;
            case Op::LOAD_INT_CONST:
                $state->slots[($opdata >> 8) & 0xff] = $t->int_values[($opdata >> 16) & 0xff];
                break;
            case Op::LOAD_SLOT0_INT_CONST:
                $slot0 = $t->int_values[($opdata >> 8) & 0xff];
                break;
            case Op::LOAD_FLOAT_CONST:
                $state->slots[($opdata >> 8) & 0xff] = $t->float_values[($opdata >> 16) & 0xff];
                break;
            case Op::LOAD_SLOT0_FLOAT_CONST:
                $slot0 = $t->float_values[($opdata >> 8) & 0xff];
                break;
            case Op::LOAD_STRING_CONST:
                $state->slots[($opdata >> 8) & 0xff] = $t->string_values[($opdata >> 16) & 0xff];
                break;
            case Op::LOAD_SLOT0_STRING_CONST:
                $slot0 = $t->string_values[($opdata >> 8) & 0xff];
                break;
            case Op::LOAD_NULL:
                $state->slots[($opdata >> 8) & 0xff] = null;
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
                $state->slots[($opdata >> 8) & 0xff] = $state->slots[($opdata >> 16) & 0xff][$t->string_values[($opdata >> 24) & 0xff]];
                break;
            case Op::INDEX_SLOT0_STRING_KEY:
                $slot0 = $state->slots[($opdata >> 8) & 0xff][$t->string_values[($opdata >> 16) & 0xff]];
                break;
            case Op::INDEX_INT_KEY:
                $state->slots[($opdata >> 8) & 0xff] = $state->slots[($opdata >> 16) & 0xff][$t->int_values[($opdata >> 24) & 0xff]];
                break;
            case Op::INDEX_SLOT0_INT_KEY:
                $slot0 = $state->slots[($opdata >> 8) & 0xff][$t->int_values[($opdata >> 16) & 0xff]];
                break;
            case Op::INDEX:
                $state->slots[($opdata >> 8) & 0xff] = $state->slots[($opdata >> 16) & 0xff][$state->slots[($opdata >> 24) & 0xff]];
                break;
            case Op::INDEX_SLOT0:
                $slot0 = $state->slots[($opdata >> 8) & 0xff][$state->slots[($opdata >> 16) & 0xff]];
                break;
            
            case Op::JUMP:
                $pc += ($opdata >> 8) & 0xffff;
                break;
            case Op::JUMP_FALSY:
                if (!$state->slots[($opdata >> 24) & 0xff]) {
                    $pc += ($opdata >> 8) & 0xffff;
                }
                break;
            case Op::JUMP_SLOT0_FALSY:
                if (!$slot0) {
                    $pc += ($opdata >> 8) & 0xffff;
                }
                break;
            case Op::JUMP_TRUTHY:
                if ($state->slots[($opdata >> 24) & 0xff]) {
                    $pc += ($opdata >> 8) & 0xffff;
                }
                break;
            case Op::JUMP_SLOT0_TRUTHY:
                if ($slot0) {
                    $pc += ($opdata >> 8) & 0xffff;
                }
                break;

            case Op::NOT:
                $state->slots[($opdata >> 8) & 0xff] = !$state->slots[($opdata >> 16) & 0xff];
                break;
            case Op::NOT_SLOT0:
                $slot0 = !$state->slots[($opdata >> 8) & 0xff];
                break;

            case Op::OR:
                $state->slots[($opdata >> 8) & 0xff] = $state->slots[($opdata >> 16) & 0xff] || $state->slots[($opdata >> 24) & 0xff];
                break;
            case Op::OR_SLOT0:
                $slot0 = $state->slots[($opdata >> 8) & 0xff] || $state->slots[($opdata >> 16) & 0xff];
                break;
            case Op::AND:
                $state->slots[($opdata >> 8) & 0xff] = $state->slots[($opdata >> 16) & 0xff] && $state->slots[($opdata >> 24) & 0xff];
                break;
            case Op::AND_SLOT0:
                $slot0 = $state->slots[($opdata >> 8) & 0xff] && $state->slots[($opdata >> 16) & 0xff];
                break;
            case Op::CONCAT:
                $state->slots[($opdata >> 8) & 0xff] = $state->slots[($opdata >> 16) & 0xff] . $state->slots[($opdata >> 24) & 0xff];
                break;
            case Op::CONCAT_SLOT0:
                $slot0 = $state->slots[($opdata >> 8) & 0xff] . $state->slots[($opdata >> 16) & 0xff];
                break;
            case Op::EQ:
                $state->slots[($opdata >> 8) & 0xff] = $state->slots[($opdata >> 16) & 0xff] == $state->slots[($opdata >> 24) & 0xff];
                break;
            case Op::EQ_SLOT0:
                $slot0 = $state->slots[($opdata >> 8) & 0xff] == $state->slots[($opdata >> 16) & 0xff];
                break;
            case Op::LT:
                $state->slots[($opdata >> 8) & 0xff] = $state->slots[($opdata >> 16) & 0xff] < $state->slots[($opdata >> 24) & 0xff];
                break;
            case Op::LT_SLOT0:
                $slot0 = $state->slots[($opdata >> 8) & 0xff] < $state->slots[($opdata >> 16) & 0xff];
                break;
            case Op::LT_EQ:
                $state->slots[($opdata >> 8) & 0xff] = $state->slots[($opdata >> 16) & 0xff] <= $state->slots[($opdata >> 24) & 0xff];
                break;
            case Op::LT_EQ_SLOT0:
                $slot0 = $state->slots[($opdata >> 8) & 0xff] <= $state->slots[($opdata >> 16) & 0xff];
                break;
            case Op::NOT_EQ:
                $state->slots[($opdata >> 8) & 0xff] = $state->slots[($opdata >> 16) & 0xff] != $state->slots[($opdata >> 24) & 0xff];
                break;
            case Op::NOT_EQ_SLOT0:
                $slot0 = $state->slots[($opdata >> 8) & 0xff] != $state->slots[($opdata >> 16) & 0xff];
                break;
            case Op::ADD:
                $state->slots[($opdata >> 8) & 0xff] = $state->slots[($opdata >> 16) & 0xff] + $state->slots[($opdata >> 24) & 0xff];
                break;
            case Op::ADD_SLOT0:
                $slot0 = $state->slots[($opdata >> 8) & 0xff] + $state->slots[($opdata >> 16) & 0xff];
                break;
            case Op::SUB:
                $state->slots[($opdata >> 8) & 0xff] = $state->slots[($opdata >> 16) & 0xff] - $state->slots[($opdata >> 24) & 0xff];
                break;
            case Op::SUB_SLOT0:
                $slot0 = $state->slots[($opdata >> 8) & 0xff] - $state->slots[($opdata >> 16) & 0xff];
                break;
            case Op::MUL:
                $state->slots[($opdata >> 8) & 0xff] = $state->slots[($opdata >> 16) & 0xff] * $state->slots[($opdata >> 24) & 0xff];
                break;
            case Op::MUL_SLOT0:
                $slot0 = $state->slots[($opdata >> 8) & 0xff] * $state->slots[($opdata >> 16) & 0xff];
                break;
            case Op::QUO:
                $state->slots[($opdata >> 8) & 0xff] = $state->slots[($opdata >> 16) & 0xff] / $state->slots[($opdata >> 24) & 0xff];
                break;
            case Op::QUO_SLOT0:
                $slot0 = $state->slots[($opdata >> 8) & 0xff] / $state->slots[($opdata >> 16) & 0xff];
                break;
            case Op::MOD:
                $state->slots[($opdata >> 8) & 0xff] = $state->slots[($opdata >> 16) & 0xff] % $state->slots[($opdata >> 24) & 0xff];
                break;
            case Op::MOD_SLOT0:
                $slot0 = $state->slots[($opdata >> 8) & 0xff] % $state->slots[($opdata >> 16) & 0xff];
                break;

            case Op::CALL_FILTER1:
                $arg1 = $state->slots[($opdata >> 16) & 0xff];
                $filter_id = ($opdata >> 24) & 0xffff;
                $filter1 = $env->filters1[$filter_id];
                $state->slots[($opdata >> 8) & 0xff] = $filter1($arg1);
                break;
            case Op::CALL_SLOT0_FILTER1:
                $arg1 = $state->slots[($opdata >> 8) & 0xff];
                $filter_id = ($opdata >> 16) & 0xffff;
                $filter1 = $env->filters1[$filter_id];
                $slot0 = $filter1($arg1);
                break;
            case Op::CALL_FILTER2:
                $arg1 = $state->slots[($opdata >> 16) & 0xff];
                $arg2 = $state->slots[($opdata >> 24) & 0xff];
                $filter_id = ($opdata >> 32) & 0xffff;
                $filter2 = $env->filters2[$filter_id];
                $state->slots[($opdata >> 8) & 0xff] = $filter2($arg1, $arg2);
                break;
            case Op::CALL_SLOT0_FILTER2:
                $arg1 = $state->slots[($opdata >> 8) & 0xff];
                $arg2 = $state->slots[($opdata >> 16) & 0xff];
                $filter_id = ($opdata >> 24) & 0xffff;
                $filter2 = $env->filters2[$filter_id];
                $slot0 = $filter2($arg1, $arg2);
                break;
            case Op::CALL_FUNC0:
                $func_id = ($opdata >> 16) & 0xffff;
                $func0 = $env->funcs0[$func_id];
                $state->slots[($opdata >> 8) & 0xff] = $func0();
                break;
            case Op::CALL_SLOT0_FUNC0:
                $func_id = ($opdata >> 8) & 0xffff;
                $func0 = $env->funcs0[$func_id];
                $slot0 = $func0();
                break;
            case Op::CALL_FUNC1:
                $arg1 = $state->slots[($opdata >> 16) & 0xff];
                $func_id = ($opdata >> 24) & 0xffff;
                $func1 = $env->funcs1[$func_id];
                $state->slots[($opdata >> 8) & 0xff] = $func1($arg1);
                break;
            case Op::CALL_SLOT0_FUNC1:
                $arg1 = $state->slots[($opdata >> 8) & 0xff];
                $func_id = ($opdata >> 16) & 0xffff;
                $func1 = $env->funcs1[$func_id];
                $slot0 = $func1($arg1);
                break;
            case Op::CALL_FUNC2:
                $arg1 = $state->slots[($opdata >> 16) & 0xff];
                $arg2 = $state->slots[($opdata >> 24) & 0xff];
                $func_id = ($opdata >> 32) & 0xffff;
                $func2 = $env->funcs2[$func_id];
                $state->slots[($opdata >> 8) & 0xff] = $func2($arg1, $arg2);
                break;
            case Op::CALL_SLOT0_FUNC2:
                $arg1 = $state->slots[($opdata >> 8) & 0xff];
                $arg2 = $state->slots[($opdata >> 16) & 0xff];
                $func_id = ($opdata >> 24) & 0xffff;
                $func2 = $env->funcs2[$func_id];
                $slot0 = $func2($arg1, $arg2);
                break;
            case Op::CALL_FUNC3:
                $arg1 = $state->slots[($opdata >> 16) & 0xff];
                $arg2 = $state->slots[($opdata >> 24) & 0xff];
                $arg3 = $state->slots[($opdata >> 32) & 0xff];
                $func_id = ($opdata >> 40) & 0xffff;
                $func3 = $env->funcs3[$func_id];
                $state->slots[($opdata >> 8) & 0xff] = $func3($arg1, $arg2, $arg3);
                break;
            case Op::CALL_SLOT0_FUNC3:
                $arg1 = $state->slots[($opdata >> 8) & 0xff];
                $arg2 = $state->slots[($opdata >> 16) & 0xff];
                $arg3 = $state->slots[($opdata >> 24) & 0xff];
                $func_id = ($opdata >> 32) & 0xffff;
                $func3 = $env->funcs3[$func_id];
                $slot0 = $func3($arg1, $arg2, $arg3);
                break;
            case Op::LENGTH_FILTER:
                $arg = $state->slots[($opdata >> 16) & 0xff];
                $state->slots[($opdata >> 8) & 0xff] = self::lengthFilter($env, $arg);
                break;
            case Op::LENGTH_SLOT0_FILTER:
                $arg = $state->slots[($opdata >> 8) & 0xff];
                $slot0 = self::lengthFilter($env, $arg);
                break;
            case Op::DEFAULT_FILTER:
                $arg1 = $state->slots[($opdata >> 16) & 0xff];
                $arg2 = $state->slots[($opdata >> 24) & 0xff];
                $state->slots[($opdata >> 8) & 0xff] = self::defaultFilter($arg1, $arg2);
                break;
            case Op::DEFAULT_SLOT0_FILTER:
                $arg1 = $state->slots[($opdata >> 8) & 0xff];
                $arg2 = $state->slots[($opdata >> 16) & 0xff];
                $slot0 = self::defaultFilter($arg1, $arg2);
                break;

            default:
                fprintf(STDERR, "%s\n", Op::opcodeString($op));
                return;
            }
        }
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
     * @param Env $env
     * @param mixed $x
     * @return int
     */
    private static function lengthFilter($env, $x) {
        if (is_string($x)) {
            return mb_strlen($x, $env->encoding);
        }
        if (is_array($x)) {
            return count($x);
        }
        return 0;
    }
}