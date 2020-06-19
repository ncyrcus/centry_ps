<?php

require_once _PS_MODULE_DIR_ . 'centry_ps/include/utils.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/model/Product.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/Brands.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/Categories.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/classes/Log.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/classes/PendingNotification.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/classes/Processing.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/controllers/front/sendnotification.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/model/Order.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/Orders.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/utils/product.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/utils/order.php';

/**
 * Description of callback
 *
 * @author Nicolás Orellana O.
 */
class Centry_psCallbackModuleFrontController extends FrontController {

    public function __construct()
    {

    }

    public function init() {
        $params = $this->getRequest();
        \PendingNotification::save_notification($params);
        $url = (new \CentrySDK\Webhooks())->getRedirectURI();
        if($url == "urn:ietf:wg:oauth:2.0:oob"){
          $url = "localhost/prestashop";
        }
        $url_curl = $url."/index.php?fc=module&module=centry_ps&controller=sendnotification";
        $ch = curl_init($url_curl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Para que la respuesta del servidor sea retornada por `curl_exec`.
        curl_setopt($ch, CURLOPT_TIMEOUT, 1); // Time out de un segundo.
        $result = curl_exec($ch);
        echo("Notificacion guardada");
        die();
    }
    public function initContent()
    {
        exit;
        // parent::initContent();
    }
    private function getRequest() {

        $cifrado = file_get_contents('php://input');
        $cifrado2 = $_POST;

        if (empty($cifrado2)) {
            return json_decode($cifrado, true);
        }
        return $cifrado2;
    }

}
