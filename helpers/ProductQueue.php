<?php

namespace CentryModulePS;

require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/model/Product.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/helpers/SyncQueue.php';

/**
 * Description of ProductQueue
 *
 * @author Elías Lama L.
 */
class ProductQueue extends SyncQueue {

    protected function process($id) {
        $product = new \Product($id);
        //if (!property_exists($product, "state") || $product->state == Product::STATE_SAVED) {
        error_log("save product");
            (new \CentrySDK\Products())->save($product);
        //}
    }

    public static function asyncRequest($id , $resource ="product" ) {
        \CentryModulePS\SyncQueue::asyncRequest( $id, $resource);
    }

}
