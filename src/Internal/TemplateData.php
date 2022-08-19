<?php

namespace KTemplate\Internal;

/**
 * @kphp-serializable
 */
class TemplateData {
    /**
     * @var int
     * @kphp-serialized-field 1
     */
    public $version;

    /**
     * @var string[]
     * @kphp-serialized-field 2
     **/
    public $string_values = [];
    /**
     * @var string[]
     * @kphp-serialized-field 3
     **/
    public $keys = [];
    /**
     * @var int[] 
     * @kphp-serialized-field 4
     **/
    public $int_values = [];

    /**
     * @var int[]
     * @kphp-serialized-field 5
     **/
    public $code = [];
}