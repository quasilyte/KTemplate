<?php

namespace KTemplate\Internal\Compile;

class Expr {
    /** @var int */
    public $kind = 0;
    /** @var mixed */
    public $value;
    /** @var int */
    public $members_offset = 0;

    public function assign(Expr $other) {
        $this->kind = $other->kind;
        $this->value = $other->value;
        $this->members_offset = $other->members_offset;
    }
}
