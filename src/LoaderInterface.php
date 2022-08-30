<?php

namespace KTemplate;

interface LoaderInterface {
    /**
     * @param string $path
     * @param string $full_name
     * @return string
     */
    public function load($path, $full_name);

    /**
     * @param string $path
     * @param TemplateCacheKey $key
     */
    public function updateCacheKey($path, $key);
}
