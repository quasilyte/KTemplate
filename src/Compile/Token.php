<?php

namespace KTemplate\Compile;

class Token {
    public $kind = TokenKind::UNSET;
    public $pos_from = 0;
    public $pos_to = 0;

    public function reset() {
        $this->kind = TokenKind::UNSET;
        $this->pos_from = 0;
        $this->pos_to = 0;
    }

    /**
     * @param Token $other
     */
    public function assign($other) {
        $this->kind = $other->kind;
        $this->pos_from = $other->pos_from;
        $this->pos_to = $other->pos_to;
    }

    /**
     * @return bool
     */
    public function hasValue() {
        return TokenKind::hasValue($this->kind);
    }

    /**
     * @return string
     */
    public function prettyKindName() {
        return TokenKind::prettyName($this->kind);
    }

    /**
     * @return string
     */
    public function kindName() {
        return TokenKind::name($this->kind);
    }
}
