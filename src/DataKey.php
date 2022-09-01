<?php

namespace KTemplate;

/**
 * DataKey describes an external data lookup request during the
 * template rendering process.
 *
 * It's up to the DataProviderInterface implementation to resolve this request.
 */
class DataKey {
    /**
     * How many parts are relevant.
     *
     * If num_parts is 1, you should not check $part2, etc
     * as their values will be undefined.
     * 
     * The $part1 is always in some defined state as 1 part
     * is the minimal number of the parts there could be.
     * 
     * @var int
     */
    public $num_parts;

    /**
     * A first part of the data key.
     * Example: "a"       => "a"
     * Example: "a.b.c"   => "a"
     * @var string
     */
    public $part1;

    /**
     * A second part of the data key.
     * Example: "a"       => ""
     * Example: "a.b.c"   => "b"
     * @var string
     */
    public $part2;

    /**
     * A third part of the data key.
     * Example: "a"       => ""
     * Example: "a.b.c"   => "c"
     * @var string
     */
    public $part3;
}
