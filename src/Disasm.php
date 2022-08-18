<?php

namespace KTemplate;

class Disasm {
    /**
     * @param Env $env
     * @param Template $t
     */
    public static function getBytecode($env, $t) {
        $out = [];
        $code = $t->code;

        $label_by_addr = [];
        foreach ($code as $pc => $opdata) {
            $op = $opdata & 0xff;
            if (!OpInfo::isJump($op)) {
                continue;
            }
            $offset = ($opdata >> 8) & 0xff;
            $target_pc = ($pc + $offset) + 1;
            if (!array_key_exists($target_pc, $label_by_addr)) {
                $label_by_addr[$target_pc] = 'L' . count($label_by_addr);
            }
        }

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
                case OpInfo::ARG_SLOT:
                case OpInfo::ARG_CACHE_SLOT:
                    $parts[] = "slot$v";
                    break;
                case OpInfo::ARG_STRING_CONST:
                    $s = addslashes($t->string_values[$v]);
                    $s = str_replace("\n", "\\n", $s);
                    $s = str_replace("\r", "\\r", $s);
                    if (strlen($s) > 32) {
                        $s = substr($s, 0, 29) . '...';
                    } 
                    $parts[] = "`" . $s . "`";
                    break;
                case OpInfo::ARG_INT_CONST:
                    $parts[] = $t->int_values[$v];
                    break;
                case OpInfo::ARG_REL8:
                    $parts[] = $label_by_addr[$pc + $v + 1];
                    break;
                case OpInfo::ARG_IMM8:
                    $parts[] = "\$$v";
                    break;
                case OpInfo::ARG_FILTER_ID:
                    $filter_name = '';
                    switch (OpInfo::filterArity($op)) {
                    case 1:
                        $filter_name = $env->getFilter1Name($v);
                        break;
                    }
                    $parts[] = $filter_name;
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
