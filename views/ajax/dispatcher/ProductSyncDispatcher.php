<?php

namespace CentryAjax;

require_once _PS_MODULE_DIR_ . 'centry_ps/views/ajax/dispatcher/AbstractDispatcher.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_ps.php';

/**
 * Description of AbstractAdminDispatcher
 *
 * @author Elías Lama L.
 */
class ProductSyncDispatcher extends AbstractDispatcher {

    public function __construct() {
        parent::__construct();
        require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/Products.php';
    }

    public function execute() {
        if (!$this->isEmployeeLegedIn()) {
            return new Response("0", Response::CODE_401);
        }
        $product = new \Product($this->getData["id_product"]);
        $productsSDK = new \CentrySDK\Products();

        $productsSDK->save($product);
    
        return new Response("1");
    }

}
