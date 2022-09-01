<?php

namespace KTemplate;

/**
 * Context is a main KTemplate configuration point.
 * 
 * Per every Engine, there is a context.
 * 
 * See every public field documentation to learn what can be configured.
 */
class Context {
    /**
     * @var string
     */
    public $cache_dir = '';

    /**
     * @var bool
     */
    public $cache_recheck = false;

    /**
     * Set the implied text encoding.
     * Used as an argument to mb_* functions.
     *
     * "UTF-8" is used by default.
     *
     * @param string $encoding
     */
    public $encoding = 'UTF-8';

    /**
     * A function to be used for escape filter (both auto-escape and explicit).
     * This function signature is (string $s, string $strategy) => string.
     * The strategy could be 'html', 'url', etc.
     *
     * By default, FilterLib::escape function is used.
     *
     * If null, no escaping will be performed.
     *
     * @var callable(string,string):string
     */
    public $escape_func;

    /**
     * @var string
     */
    public $default_escape_strategy = 'html';

    /**
     * Whether to escape the data outside of the {{ }} tags.
     * Usually, this would be an overkill, but that may depend
     * on the output format and application. For example, you may want to
     * make sure that even manually constructed values don't contain
     * some sensitive information.
     *
     * @var bool
     */
    public $auto_escape_text = false;

    /**
     * Whether const values should be escaped.
     *
     * @var bool
     */
    public $auto_escape_const_expr = false;

    /**
     * Whether to escape results of {{ }} tags.
     * This option is a fallback if KTemplate was unable to infer
     * a proper type of the expression.
     * Bool, int, float and null typed expressions are never escaped.
     *
     * @var bool
     */
    public $auto_escape_expr = true;

    /**
     * Whether to validate `matches` operator regular expressions
     * at template compile time.
     *
     * This is usually a good idea, unless you have templates with tons
     * of regular expressions with minority of them being actually ever executed.
     *
     * This option can only increase the template compilation speed, not rendering speed.
     *
     * @var bool
     */
    public $validate_regexp = true;

    public function __construct() {
        $this->escape_func = [FilterLib::class, 'escape'];
    }
}
