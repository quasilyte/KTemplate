<?php

namespace KTemplate;

interface LoaderInterface {
    /**
     * @param Env $env
     * @param string $path
     * @return Template
     */
    public function load($env, $path);
}
