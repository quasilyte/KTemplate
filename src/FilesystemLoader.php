<?php

namespace KTemplate;

use KTemplate\Internal\CachedTemplate;
use KTemplate\Internal\Strings;
use KTemplate\Compile\Compiler;

class FilesystemLoader implements LoaderInterface {
    /**
     * @var CachedTemplate[]
     */
    private $cache = [];

    /**
     * @param string
     */
    private $cache_dir = '';

    /**
     * @var string[]
     */
    private $paths = [];

    /**
     * @var Compiler
     */
    private $compiler;

    /**
     * Whether to re-check already cached templates during this request.
     *
     * For typical PHP application this is usually redundant.
     *
     * For applications that have long-running, background-like scripts,
     * this option can be set to true.
     *
     * There is a slight performance penalty when having this option set to true.
     * 
     * When $cache_dir is unset, this option has no effect.
     *
     * @var bool
     */
    public $cache_recheck = false;

    /**
     * @param string[] $paths
     * @param string $cache_dir
     */
    public function __construct($paths, $cache_dir = '') {
        foreach ($paths as $p) {
            $realpath = realpath($p);
            if (!$realpath) {
                throw new \Exception("can't resolve $p lookup path");
            }
            $this->paths[] = str_replace('\\', '/', $p);
        }
        $this->cache_dir = $cache_dir;
        $this->compiler = new Compiler();
    }

    /**
     * @param Env $env
     * @param string $path
     * @return Template
     */
    public function load($env, $path) {
        if (isset($this->cache[$path])) {
            $cache_item = $this->cache[$path];
            if ($this->cache_recheck) {
                $filemtime_result = filemtime($cache_item->full_path);
                if ($filemtime_result === false) {
                    throw new \Exception("cached $cache_item->full_path appears to be unavailable");
                }
                $file_size = (int)filesize($cache_item->full_path);
                $file_mtime = (int)$filemtime_result;
                if ($cache_item->key_mtime < $file_mtime || $cache_item->key_file_size !== $file_size) {
                    $cache_item->key_mtime = $file_mtime;
                    $cache_item->key_file_size = $file_size;
                    $this->loadTemplateInto($env, $cache_item);
                }
            }
            return $cache_item->template;
        }

        $full_filename = $this->resolvePath($path);
        if (!$full_filename) {
            throw new \Exception("can't resolve $path path");
        }
        $file_mtime = (int)filemtime($full_filename);
        $file_size = (int)filesize($full_filename);

        $cache_item = new CachedTemplate();
        $cache_item->full_path = $full_filename;
        $cache_item->load_path = $path;
        $cache_item->key_mtime = $file_mtime;
        $cache_item->key_file_size = $file_size;
        $this->loadTemplateInto($env, $cache_item);
        $this->cache[$path] = $cache_item;
        return $cache_item->template;
    }

    /**
     * @param Env $env
     * @param CachedTemplate $dst
     */
    private function loadTemplateInto($env, $dst) {
        $cache_filename = '';
        $cache_file_dir = '';
        if ($this->cache_dir) {
            $load_path_dir = dirname($dst->load_path);
            $load_path_basename = basename($dst->load_path);
            $cache_file_dir = "$this->cache_dir/$load_path_dir";
            if (KPHP_COMPILER_VERSION) {
                $cache_filename = "$cache_file_dir/$load_path_basename.kphp.$dst->key_mtime.$dst->key_file_size.tdata";
            } else {
                $cache_filename = "$cache_file_dir/$load_path_basename.php.$dst->key_mtime.$dst->key_file_size.tdata";
            }
            if (file_exists($cache_filename)) {
                $cached_template_data = (string)file_get_contents($cache_filename);
                $dst->template = Template::unserialize($cached_template_data);
                return;
            }
        }

        $file_contents = file_get_contents($dst->full_path);
        if ($file_contents === false) {
            throw new \Exception("error reading $dst->full_path");
        }
        $dst->template = $this->compiler->compile($env, $dst->load_path, (string)$file_contents);

        if ($cache_filename) {
            if (!file_exists($cache_file_dir)) {
                mkdir($cache_file_dir, 0755, true);
            }
            file_put_contents($cache_filename, $dst->template->serialize());
        }
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
