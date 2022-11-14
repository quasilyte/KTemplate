<?php

namespace KTemplate\Internal;

use KTemplate\Template;
use KTemplate\DecompiledTemplate;

class Disasm {
    /**
     * @param Env $env
     * @param Template $t
     * @param int $max_str_len
     * @return DecompiledTemplate
     */
    public static function decompile($env, $t, $max_str_len = 32) {
        $header = self::getFrameHeader($env, $t);
        $bytecode = self::getBytecode($env, $t);
        return new DecompiledTemplate($header, $bytecode);
    }

    /**
     * @param Env $env
     * @param Template $t
     * @return string
     */
    public static function getFrameHeader($env, $t) {
        $num_cache_slots = $t->numCacheSlots();
        $num_slots = $t->frameSize() - $num_cache_slots;
        $slots = "slots={cache:$num_cache_slots local:$num_slots}";
        $constants = "constants={s:" . count($t->string_values) . ' i:' . count($t->int_values) . ' f:' . count($t->float_values) . '}';
        return "$slots $constants";
    }

    /**
     * @param Env $env
     * @param Template $t
     * @param int $max_str_len
     * @return string[]
     */
    public static function getBytecode($env, $t, $max_str_len = 32) {
        $out = [];
        $code = $t->code;

        $label_by_addr = [];
        foreach ($code as $pc => $opdata) {
            $op = $opdata & 0xff;
            if (!OpInfo::isJump($op)) {
                continue;
            }
            $offset = ($opdata >> 8) & 0xffff;
            $target_pc = ($pc + $offset) + 1;
            if (!array_key_exists($target_pc, $label_by_addr)) {
                $label_by_addr[$target_pc] = 'L' . count($label_by_addr);
            }
        }

        $num_cache_slots = $t->numCacheSlots();
        foreach ($code as $pc => $opdata) {
            if (array_key_exists($pc, $label_by_addr)) {
                $out[] = $label_by_addr[$pc] . ':';
            }

            $op = $opdata & 0xff;
            $parts = [];
            $op_string = Op::opcodeString($op);
            $op_flags = Op::opcodeFlags($op);
            $parts[] = $op_string;
            if (($op_flags & OpInfo::FLAG_IMPLICIT_SLOT0) !== 0) {
                $parts[] = '*slot0';
            }
            $args = Op::$args[$op];
            $arg_shift = 8; // Skip the first 8 bits as they're occupied by the opcode
            foreach ($args as $a) {
                $arg_mask = 0xff;
                if (OpInfo::argSize($a) == 2) {
                    $arg_mask = 0xffff;
                }
                $v = ($opdata >> $arg_shift) & $arg_mask;
                switch ($a) {
                case OpInfo::ARG_CACHE_SLOT:
                    $parts[] = "[slot$v]";
                    break;
                case OpInfo::ARG_SLOT:
                    if ($v <= $num_cache_slots && $v !== 0) {
                        $parts[] = "[slot$v]";
                    } else if ($v <= $t->frameSize()) {
                        $parts[] = "slot$v";
                    } else {
                        $parts[] = "arg" . ($v - $t->frameSize());
                    }
                    break;
                case OpInfo::ARG_STRING_CONST:
                    $s = addcslashes($t->string_values[$v], "\0\t\"\\\n\r");
                    if (strlen($s) > $max_str_len) {
                        $s = substr($s, 0, $max_str_len - 3) . '...';
                    } 
                    $parts[] = "`" . $s . "`";
                    break;
                case OpInfo::ARG_INT_CONST:
                    $parts[] = $t->int_values[$v];
                    break;
                case OpInfo::ARG_FLOAT_CONST:
                    $parts[] = $t->float_values[$v];
                    break;
                case OpInfo::ARG_REL16:
                    $parts[] = $label_by_addr[$pc + $v + 1];
                    break;
                case OpInfo::ARG_IMM8:
                    $parts[] = "\$$v";
                    break;
                case OpInfo::ARG_FUNC_ID:
                    $parts[] = $env->getFunctionName($v, OpInfo::callArity($op));
                    break;
                case OpInfo::ARG_FILTER_ID:
                    $parts[] = $env->getFilterName($v, OpInfo::callArity($op));
                    break;
                case OpInfo::ARG_KEY_OFFSET:
                    $part = '';
                    $num_parts = OpInfo::numKeyParts($opdata);
                    for ($i = 0; $i < $num_parts; $i++) {
                        if ($i != 0) {
                            $part .= '.';
                        }
                        $part .= $t->keys[$v + $i];
                    }
                    $parts[] = $part;
                    break;
                }
                $arg_shift += OpInfo::argSize($a) * 8;
            }
            $out[] = '  ' . implode(' ', $parts);
            $pc++;
        }
        return $out;
    }
}
