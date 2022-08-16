<?php

namespace KTemplate;

interface DataProviderInterface {
    /**
     * @param DataKey $key
     * @return mixed
     */
    function getData($key);
}
