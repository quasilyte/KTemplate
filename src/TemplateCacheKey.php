<?php

namespace KTemplate;

/**
 * TemplateCacheKey helps the cache implementation to understand whether
 * cached results are stale or not.
 *
 * For filesystem-based loaders, modification time is usually a filemtime() result;
 * source size is file_size() result.
 * 
 * For other loaders it's up to the loader to preserve the semantics.
 * $modification_time should be increasing for the newer versions.
 */
class TemplateCacheKey {
    /**
     * A field that will not be used by the cache,
     * but can be useful for template path resolution results caching.
     *
     * @var string
     **/
    public $full_name = '';

    /** @var int */
    public $modification_time = 0;

    /** @var int */
    public $source_size = 0;
}
