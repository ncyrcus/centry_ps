<?php

namespace CentrySDK;

require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/AbstractResource.php';

/**
 * Description of Webhooks
 *
 * @author Nicolás Orellana O.
 */
class Webhooks extends AbstractResource {

    protected function __init() {
        $this->resource = "webhooks";
        $this->modelClass = "Webhook";
    }

    public function getCallbackURL() {
        return \Tools::getShopDomainSsl(true) . __PS_BASE_URI__ . "index.php?fc=module&module=centry_ps&controller=callback";
    }

    public function registerWH() {
        $wh = new Webhook();
        $wh->callback_url = $this->getCallbackURL();
        foreach ($this->all() as $item){
            if(strcmp($item->callback_url,$wh->callback_url)===0){
                $this->doRequest($this->getUrlResource() ."/".$item->_id. ".json", array(),\OAuth2\Client::HTTP_METHOD_DELETE);
            }
        }
        $master = \Configuration::get('CENTRY_MAESTRO');
        $wh->on_product_save = !($master ? true : false);
        $wh->on_product_delete = !($master ? true : false);
        $wh->on_order_save = ($master ? true : false);
        $wh->on_order_delete = ($master ? true : false);
        $wh->on_integration_config_save = true;
        $wh->on_integration_config_delete = true;
        $parameters = $wh->toParametersArray();
        $result = $this->doRequest($this->getUrlResource() . ".json", $parameters, \OAuth2\Client::HTTP_METHOD_POST);
        if (isset($result)) {
            
        }
    }

}
