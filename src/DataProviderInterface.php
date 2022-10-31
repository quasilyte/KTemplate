<?php

namespace KTemplate;

/**
 * DataProviderInterface describes the KTemplate data binding mechanism.
 *
 * When template is being rendered, it may need to resolve a name like `x.y.z`,
 * it does so by calling the getData() method with a DataKey object.
 * 
 * See ArrayDataProvider for an example implementation.
 */
interface DataProviderInterface {
    /**
     * @param DataKey $key
     * @return mixed
     */
    public function getData($key);
}
