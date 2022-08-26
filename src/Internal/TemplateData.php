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
     * @var float[]
     * @kphp-serialized-field 5
     **/
    public $float_values = [];

    /**
     * @var int[]
     * @kphp-serialized-field 6
     **/
    public $code = [];

    /**
     * @var int
     * @kphp-serialized-field 7
     */
    public $frame_size;

    /**
     * @var int
     * @kphp-serialized-field 8
     */
    public $frame_args_size;

    /**
     * @var mixed[]
     * @kphp-serialized-field 9
     */
    public $params;
}