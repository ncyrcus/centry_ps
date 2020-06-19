<?php

namespace CentrySDK;

require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/AbstractResource.php';

/**
 * Description of Brands
 *
 * @author Elías Lama L.
 */
class Brands extends AbstractResource {

    protected function __init() {
        $this->resource = "brands";
        $this->modelClass = "Brand";
    }

}
