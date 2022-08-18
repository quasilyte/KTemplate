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

            case Op::OUTPUT_SLOT0:
                $state->buf .= $slot0;
                break;
            case Op::OUTPUT:
                $state->buf .= $state->slots[($opdata >> 8) & 0xff];
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
            
            case Op::JUMP:
                $pc += ($opdata >> 8) & 0xff;
                break;
            case Op::JUMP_ZERO:
                if ((int)$slot0 === 0) {
                    $pc += ($opdata >> 8) & 0xff;
                }
                break;
            case Op::JUMP_NOT_ZERO:
                if ((int)$slot0 !== 0) {
                    $pc += ($opdata >> 8) & 0xff;
                }
                break;

            case Op::NOT:
                $state->slots[($opdata >> 8) & 0xff] = !$state->slots[($opdata >> 16) & 0xff];
                break;
            case Op::NOT_SLOT0:
                $slot0 = !$state->slots[($opdata >> 8) & 0xff];
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
            case Op::GT:
                $state->slots[($opdata >> 8) & 0xff] = $state->slots[($opdata >> 16) & 0xff] > $state->slots[($opdata >> 24) & 0xff];
                break;
            case Op::GT_SLOT0:
                $slot0 = $state->slots[($opdata >> 8) & 0xff] > $state->slots[($opdata >> 16) & 0xff];
                break;
            case Op::LT:
                $state->slots[($opdata >> 8) & 0xff] = $state->slots[($opdata >> 16) & 0xff] < $state->slots[($opdata >> 24) & 0xff];
                break;
            case Op::LT_SLOT0:
                $slot0 = $state->slots[($opdata >> 8) & 0xff] < $state->slots[($opdata >> 16) & 0xff];
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
            case Op::MUL:
                $state->slots[($opdata >> 8) & 0xff] = $state->slots[($opdata >> 16) & 0xff] * $state->slots[($opdata >> 24) & 0xff];
                break;
            case Op::MUL_SLOT0:
                $slot0 = $state->slots[($opdata >> 8) & 0xff] * $state->slots[($opdata >> 16) & 0xff];
                break;

            case Op::CALL_FILTER1:
                $arg1 = $state->slots[($opdata >> 16) & 0xff];
                $filter_id = ($opdata >> 32) & 0xffff;
                $filter = $env->filters1[$filter_id];
                $state->slots[($opdata >> 8) & 0xff] = $filter($arg1);
                break;
            case Op::CALL_SLOT0_FILTER1:
                $arg1 = $state->slots[($opdata >> 8) & 0xff];
                $filter_id = ($opdata >> 24) & 0xffff;
                $filter = $env->filters1[$filter_id];
                $slot0 = $filter($arg1);
                break;

            default:
                return;
            }
        }
    }
}