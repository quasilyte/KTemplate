<?php

namespace KTemplate\Internal;

use KTemplate\Template;

class CachedTemplate {
    public $key_mtime = 0;

    public $key_file_size = 0;

    public $full_path = '';

    public $load_path = '';

    /** @var Template */
    public $template = null;
}
