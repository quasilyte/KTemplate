<?php

namespace KTemplate;

class OpInfo {
    public const ARG_SLOT = 0;
    public const ARG_STRING_CONST = 1;
    public const ARG_INT_CONST = 2;
    public const ARG_REL8 = 3;
    public const ARG_IMM8 = 4;
    public const ARG_KEY_OFFSET = 5;
    public const ARG_CACHE_SLOT = 6;

    public const FLAG_IMPLICIT_SLOT0 = 1 << 0;
    public const FLAG_HAS_SLOT_ARG = 1 << 1;

    /**
     * @param int $opdata
     * @return int
     */
    public static function numKeyParts($opdata) {
        $op = $opdata & 0xff;

        switch ($op) {
        case Op::OUTPUT_EXTDATA_1:
        case Op::LOAD_EXTDATA_1:
        case Op::LOAD_SLOT0_EXTDATA_1:
            return 1;
        case Op::OUTPUT_EXTDATA_2:
        case Op::LOAD_EXTDATA_2:
        case Op::LOAD_SLOT0_EXTDATA_2:
            return 2;
        case Op::OUTPUT_EXTDATA_3:
        case Op::LOAD_EXTDATA_3:
        case Op::LOAD_SLOT0_EXTDATA_3:
            return 3;

        default:
            return 0;
        }
    }

    /**
     * @param int $op
     * @return bool
     */
    public static function isJump($op) {
        switch ($op) {
        case Op::JUMP:
        case Op::JUMP_ZERO:
        case Op::JUMP_NOT_ZERO:
            return true;
        default:
            return false;
        }
    }
}
