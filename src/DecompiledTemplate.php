<?php

namespace KTemplate;

class DecompiledTemplate {
    /**
     * A pretty-formatted template frame info.
     * @var string
     **/
    public $header;

    /**
     * A disassembled listing in form of string lines.
     * @var string[][] 
     **/
    public $bytecode;

    /**
     * @param string $header
     * @param string $bytecode
     */
    public function __construct($header, $bytecode) {
        $this->header = $header;
        $this->bytecode = $bytecode;
    }
}
