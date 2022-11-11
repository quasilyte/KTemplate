<?php

namespace KTemplate\Internal\Compile;

class Expr {
    /** @var int */
    public $kind = 0;
    /** @var mixed */
    public $value;
    /** @var int */
    public $members_offset = 0;

    /** @param Expr $other */
    public function assign($other) {
        $this->kind = $other->kind;
        $this->value = $other->value;
        $this->members_offset = $other->members_offset;
    }

    /**
     * @param ExprParser $p
     * @param Expr $root
     * @param callable(Expr):bool $f
     */
    public static function walk($p, $root, $f) {
        if (!$f($root)) {
            return;
        }
        $num_args = $root->numArgs();
        for ($i = 0; $i < $num_args; $i++) {
            self::walk($p, $p->getExprMember($root, $i), $f);
        }
    }

    /** @return int */
    public function numArgs() {
        $n = ExprKind::numArgs($this->kind);
        return $n === -1 ? (int)$this->value : $n;
    }
}
