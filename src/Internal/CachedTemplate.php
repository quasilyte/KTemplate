<?php

namespace KTemplate\Internal;

use KTemplate\Template;

class CachedTemplate {
    /** @var int */
    public $key_mtime = 0;

    /** @var int */
    public $key_file_size = 0;

    /** @var string */
    public $full_name = '';

    /** @var string */
    public $load_path = '';

    /** @var Template */
    public $template = null;
}
