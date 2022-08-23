<?php

namespace KTemplate;

/**
 * EscapeConfig describes what needs to be auto-escaped.
 * 
 * When some value is about to be escaped, the associated
 * Env::$escape_func will be called on it.
 * See Env::$escape_func documentation for more info. 
 *
 * Note that it's only used during the template compilation.
 * If you change the settings after template is compiled,
 * you won't notice any differences.
 */
class EscapeConfig {
    /**
     * Whether to escape the data outside of the {{ }} tags.
     * Usually, this would be an overkill, but that may depend
     * on the output format and application. For example, you may want to
     * make sure that even manually constructed values don't contain
     * some sensitive information.
     * @var bool
     */
    public $escape_text = false;

    /**
     * Whether const values should be escaped.
     * @var bool
     */
    public $escape_const_expr = false;

    /**
     * Whether to escape results of {{ }} tags.
     * This option is a fallback if KTemplate was unable to infer
     * a proper type of the expression.
     * Bool, int, float and null typed expressions are never escaped.
     */
    public $escape_expr = true;
}
