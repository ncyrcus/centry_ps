<?php

namespace CentrySDK;

require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/AbstractResource.php';

/**
 * Description of Categories
 *
 * @author Elías Lama L.
 */
class Categories extends AbstractResource {

    protected function __init() {
        $this->resource = "categories";
        $this->modelClass = "Category";
    }

}
