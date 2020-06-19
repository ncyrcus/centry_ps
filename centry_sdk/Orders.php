<?php

namespace CentrySDK;

require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/AbstractResource.php';

/**
 * Description of Products
 *
 * @author Paulo Sandoval S.
 */
class Orders extends AbstractResource {

    protected function __init() {
        $this->resource = "orders";
        $this->modelClass = "Order";
    }

    public function save(\Order $order) {
        (isset($order->id_centry) && trim($order->id_centry) != "" ) ? $this->update($order) : $this->create($order);
    }

    public function create(\Order $order) {
        $parameters = Order::fromPrestashop($order)->toParametersArray();
        $response = $this->doRequest($this->getUrlResource() . ".json", $parameters, \OAuth2\Client::HTTP_METHOD_POST);
        $result = $response['code'] != 200 ? null : $response['result'];
        if (isset($result)) {
            $resp = new Order($result);
            $order->id_centry = $resp->_id;
            $order->updateIdCentry();
        }
    }

    public function update(\Order $order) {
        $parameters = Order::fromPrestashop($order)->toParametersArray();
        $this->doRequest($this->getUrlResource() . "/{$order->id_centry}.json", $parameters, \OAuth2\Client::HTTP_METHOD_PATCH);
    }

    public function delete($id_centry) {
        $this->doRequest($this->getUrlResource() . "/$id_centry.json", array(), \OAuth2\Client::HTTP_METHOD_DELETE);
    }

}
