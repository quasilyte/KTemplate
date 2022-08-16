<?php

namespace KTemplate\Compile;

class CompilationException extends \Exception {
    /** @var int */
    public $source_line = -1;

    /** @var string */
    public $source_filename;

    public function getFullMessage() {
        return "$this->source_filename:$this->source_line: $this->message";
    }
}
