<?php

namespace KTemplate;

class OpInfo {
    public const ARG_SLOT = 0;
    public const ARG_STRING_CONST = 1;
    public const ARG_INT_CONST = 2;
    public const ARG_REL8 = 3;

    public const FLAG_IMPLICIT_SLOT0 = 1 << 0;

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
