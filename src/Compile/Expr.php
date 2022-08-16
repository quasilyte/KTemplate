<?php

namespace KTemplate\Compile;

class Expr {
    public const BAD = 0;
    public const IDENT = 1;
    public const ADD = 2;
    public const SUB = 3;
    public const MUL = 4;
    public const DIV = 5;
    public const CONCAT = 6;
    public const AND = 7;
    public const OR = 8;
    public const EQ = 9;
    public const NOT_EQ = 10;
    public const LT = 11;
    public const GT = 12;
    public const LT_EQ = 13;
    public const GT_EQ = 14;
    public const NOT = 15;
    public const TRUE_LIT = 16;
    public const FALSE_LIT = 17;
    public const INT_LIT = 18;
    public const FLOAT_LIT = 19;
    public const STRING_LIT = 20;
    public const DOT_ACCESS = 21;

    /** @var int */
    public $kind = 0;
    /** @var mixed */
    public $value;
    /** @var int */
    public $num_members = 0;
    /** @var int */
    public $members_offset = 0;

    public function assign(Expr $other) {
        $this->kind = $other->kind;
        $this->value = $other->value;
        $this->num_members = $other->num_members;
        $this->members_offset = $other->members_offset;
    }
}
