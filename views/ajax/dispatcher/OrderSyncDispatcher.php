<?php

namespace CentryAjax;

require_once _PS_MODULE_DIR_ . 'centry_ps/views/ajax/dispatcher/AbstractDispatcher.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_ps.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/Orders.php';

/**
 * Description of AbstractAdminDispatcher
 *
 * @author Nicolás Orellana O.
 */
class OrderSyncDispatcher extends AbstractDispatcher {

    public function __construct() {
        parent::__construct();
    }

    public function execute() {
        if (!$this->isEmployeeLegedIn()) {
            return new Response("0", Response::CODE_401);
        }
        //error_log(print_r($this->getData["id_order"], true));
        if ($this->getData["id_order"]) {
            $id_order_centry = $this->getData["id_order"];
            $orderSDK = new \CentrySDK\Orders();
            $result = $orderSDK->findById($id_order_centry);
            //error_log("inspect on \$result ajax\\dispatcher\\OrderSyncDispatcher.php " . print_r($result, true));
        }
        return new Response("1");
    }

}
