<?php

namespace KTemplate\Internal;

use KTemplate\Internal\Compile\Compiler;
use KTemplate\LoaderInterface;
use KTemplate\Context;
use KTemplate\Template;
use KTemplate\TemplateCacheKey;

class TemplateCache {
    /** @var Compiler */
    private $compiler;

    /** @var CachedTemplate[] */
    private $cache = [];

    /** @var Context */
    private $ctx;

    /** @var LoaderInterface */
    private $loader;

    /** @var TemplateCacheKey */
    private $cache_key;

    /**
     * @param Context $ctx
     * @param LoaderInterface $loader
     */
    public function __construct($ctx, $loader) {
        $this->compiler = new Compiler();
        $this->ctx = $ctx;
        $this->loader = $loader;
        $this->cache_key = new TemplateCacheKey();
    }
    
    /**
     * @param Env $env
     * @param string $path
     * @return Template
     */
    public function get($env, $path) {
        if (isset($this->cache[$path])) {
            $cache_item = $this->cache[$path];
            if ($this->ctx->cache_recheck) {
                $this->cache_key->full_name = $cache_item->full_name;
                $this->loader->updateCacheKey($path, $this->cache_key);
                if ($cache_item->key_mtime < $this->cache_key->modification_time || $cache_item->key_file_size !== $this->cache_key->source_size) {
                    $cache_item->key_mtime = $this->cache_key->modification_time;
                    $cache_item->key_file_size = $this->cache_key->source_size;
                    $this->loadTemplateInto($env, $cache_item);
                }
            }
            return $cache_item->template;
        }

        $this->loader->updateCacheKey($path, $this->cache_key);

        $cache_item = new CachedTemplate();
        $cache_item->load_path = $path;
        $cache_item->full_name = $this->cache_key->full_name;
        $cache_item->key_mtime = $this->cache_key->modification_time;
        $cache_item->key_file_size = $this->cache_key->source_size;
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
        if ($this->ctx->cache_dir) {
            $load_path_dir = dirname($dst->load_path);
            $load_path_basename = basename($dst->load_path);
            $cache_file_dir = $this->ctx->cache_dir . '/' . $load_path_dir;
            if (defined('KPHP_COMPILER_VERSION')) {
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

        $file_contents = $this->loader->load($dst->load_path, $dst->full_name);
        $dst->template = $this->compiler->compile($env, $dst->load_path, $file_contents);

        if ($cache_filename) {
            if (!file_exists($cache_file_dir)) {
                mkdir($cache_file_dir, 0755, true);
            }
            file_put_contents($cache_filename, $dst->template->serialize());
        }
    }
}
