<?php

namespace KTemplate;

/**
 * CompilationException is thrown by a template compiler in case of errors.
 *
 * This exception object contains the error source location,
 * see $source_line and $source_filename public fields.
 *
 * Use getFullMessage() instead of getMessage() to get a message
 * that includes this extra information.
 */
class CompilationException extends \Exception {
    /**
     * A line inside the template source that caused this error.
     * For unknown locations, this value will be set to -1.
     *
     * @var int
     **/
    public $source_line = -1;

    /**
     * An offending template source name.
     * Usually, it's identicall to the template path.
     *
     * @var string
     **/
    public $source_filename;

    /**
     * Returns an error message annotated with error location info.
     *
     * @return string
     */
    public function getFullMessage() {
        return "$this->source_filename:$this->source_line: $this->message";
    }
}
