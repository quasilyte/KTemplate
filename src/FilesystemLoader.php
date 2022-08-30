<?php

namespace KTemplate;

use KTemplate\Internal\Strings;
use KTemplate\Compile\Compiler;

class FilesystemLoader implements LoaderInterface {
    /**
     * @var string[]
     */
    private $paths = [];

    /**
     * @param string[] $paths
     */
    public function __construct($paths) {
        foreach ($paths as $p) {
            $realpath = realpath($p);
            if (!$realpath) {
                throw new \Exception("can't resolve $p lookup path");
            }
            $this->paths[] = str_replace('\\', '/', $realpath);
        }
    }

    public function load($path, $full_name) {
        if (!$full_name) {
            $full_name = $this->resolvePath($path);
        }
        return (string)file_get_contents($full_name);
    }

    public function updateCacheKey($path, $key) {
        $key->full_name = $this->resolvePath($path);
        $filemtime_result = filemtime($key->full_name);
        if ($filemtime_result === false) {
            throw new \Exception("cached $key->full_name appears to be unavailable");
        }
        $key->modification_time = (int)$filemtime_result;
        $key->source_size = (int)filesize($key->full_name);
    }

    /**
     * @param string $path
     * @return string
     */
    private function resolvePath($path) {
        if ($this->isAbsPath($path)) {
            return $path;
        }
        foreach ($this->paths as $lookup_path) {
            $full_path = "$lookup_path/$path";
            if (file_exists($full_path)) {
                return $full_path;
            }
        }
        throw new \Exception("can't resolve $path path");
        return '';
    }

    /**
     * @param string $path
     * @return bool
     */
    private function isAbsPath($path) {
        return Strings::hasPrefix($path, '/');
    }
}
