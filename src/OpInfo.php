<?php

namespace KTemplate;

class OpInfo {
    public const ARG_SLOT = 0;
    public const ARG_STRING_CONST = 1;
    public const ARG_INT_CONST = 2;
    public const ARG_FLOAT_CONST = 3;
    public const ARG_REL16 = 4;
    public const ARG_IMM8 = 5;
    public const ARG_KEY_OFFSET = 6;
    public const ARG_CACHE_SLOT = 7;
    public const ARG_FILTER_ID = 8;
    public const ARG_FUNC_ID = 9;

    public const FLAG_IMPLICIT_SLOT0 = 1 << 0;
    public const FLAG_HAS_SLOT_ARG = 1 << 1;

    /**
     * @param int $arg
     * @return int
     */
    public static function argSize($arg) {
        switch ($arg) {
        case self::ARG_FILTER_ID:
        case self::ARG_FUNC_ID:
        case self::ARG_REL16:
            return 2;
        default:
            return 1;
        }
    }

    /**
     * @param int $op
     * @return int
     */
    public static function callArity($op) {
        switch ($op) {
        case Op::CALL_FUNC0:
        case Op::CALL_SLOT0_FUNC0:
            return 0;

        case Op::CALL_FILTER1:
        case Op::CALL_SLOT0_FILTER1:
        case Op::CALL_FUNC1:
        case Op::CALL_SLOT0_FUNC1:
            return 1;

        case Op::CALL_FILTER2:
        case Op::CALL_SLOT0_FILTER2:
        case Op::CALL_FUNC2:
        case Op::CALL_SLOT0_FUNC2:
            return 2;

        case Op::CALL_FUNC3:
        case Op::CALL_SLOT0_FUNC3:
            return 3;

        default:
            return -1;
        }
    }

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
        case Op::JUMP_FALSY:
        case Op::JUMP_SLOT0_FALSY:
        case Op::JUMP_TRUTHY:
        case Op::JUMP_SLOT0_TRUTHY:
        case Op::JUMP_NOT_NULL:
        case Op::JUMP_SLOT0_NOT_NULL:
        case Op::FOR_VAL:
        case Op::FOR_KEY_VAL:
            return true;
        default:
            return false;
        }
    }
}
