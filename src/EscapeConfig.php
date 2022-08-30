<?php

namespace KTemplate;

/**
 * EscapeConfig describes what and how needs to be escaped.
 * 
 * When some value is about to be escaped, the associated
 * EscapeConfig::$escape_func will be called on it.
 * See EscapeConfig::$escape_func documentation for more info. 
 *
 * Note that auto escaping options only used during the template compilation.
 * If you change these settings after template is compiled,
 * you won't notice any differences.
 */
class EscapeConfig {
    /**
     * A function to be used for escape filter (both auto-escape and explicit).
     * This function signature is (string $s, string $strategy) => string.
     * The strategy could be 'html', 'url', etc.
     * 
     * By default, FilterLibrary::escape function is used.
     * 
     * If null, no escaping will be performed.
     * 
     * @var callable(string,string):string
     */
    public $escape_func;

    public $default_strategy = 'html';

    /**
     * Whether to escape the data outside of the {{ }} tags.
     * Usually, this would be an overkill, but that may depend
     * on the output format and application. For example, you may want to
     * make sure that even manually constructed values don't contain
     * some sensitive information.
     * @var bool
     */
    public $auto_escape_text = false;

    /**
     * Whether const values should be escaped.
     * @var bool
     */
    public $auto_escape_const_expr = false;

    /**
     * Whether to escape results of {{ }} tags.
     * This option is a fallback if KTemplate was unable to infer
     * a proper type of the expression.
     * Bool, int, float and null typed expressions are never escaped.
     */
    public $auto_escape_expr = true;

    public function __construct() {
        $this->escape_func = [FilterLibrary::class, 'escape'];
    }
}
