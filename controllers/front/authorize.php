<?php

require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/Brands.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/Categories.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/Products.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/Sizes.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/Colors.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/utils/abstractresource.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/model/Order.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/classes/Log.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/utils/product.php';

class Centry_psAuthorizeModuleFrontController extends FrontController {



    public function initContent() {
        //parent::initContent();
        // header('Content-type: application/json; charset=utf-8');
        header("HTTP/1.0 200 OK");
        header('Content-Type:text/plain');
        $parameters = Tools::getAllValues();
        $code = $parameters['code'];
        $appSDK = new CentrySDK\Webhooks();
        $js = $appSDK->requestAccessToken($code) ? "success" : "fail";
        if ($js == "success") {
          $appSDK->registerWH();
          echo "Authorized";
        }
        else{
          echo "Unauthorized";
        }





        die();
    }
}
