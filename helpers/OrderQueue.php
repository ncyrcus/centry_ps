<?php

namespace CentryModulePS;

require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/model/Product.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/helpers/SyncQueue.php';

/**
 * Description of OrderQueue
 *
 * @author Elías Lama L.
 */
class OrderQueue extends SyncQueue {

    protected function process($id) {
        $order = new \Order($id);
        (new \CentrySDK\Orders())->save($order);
    }

    public static function asyncRequest($id , $resource ="order" ) {
        \CentryModulePS\SyncQueue::asyncRequest( $id , $resource );
    }

}
