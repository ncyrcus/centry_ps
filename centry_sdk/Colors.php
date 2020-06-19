<?php

namespace CentrySDK;

require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/AbstractResource.php';

/**
 * Description of Colors
 *
 * @author Elías Lama L.
 */
class Colors extends AbstractResource {

    protected function __init() {
        $this->resource = "colors";
        $this->modelClass = "Color";
    }

}
