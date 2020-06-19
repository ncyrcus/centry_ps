<?php

namespace CentrySDK;

require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/AbstractResource.php';

/**
 * Description of Sizes
 *
 * @author Elías Lama L.
 */
class Sizes extends AbstractResource {

    protected function __init() {
        $this->resource = "sizes";
        $this->modelClass = "Size";
    }

}
