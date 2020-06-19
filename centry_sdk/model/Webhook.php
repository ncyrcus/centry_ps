<?php

namespace CentrySDK;

include_once _PS_CONFIG_DIR_ . 'config.inc.php';
include_once _PS_CORE_DIR_ . '/init.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/model/AbstractModel.php';

/**
 * Description of Webhook
 *
 * @author Elías Lama L.
 */
class Webhook extends AbstractModel {

    public $callback_url;
    public $on_product_save;
    public $on_product_delete;
    public $on_order_save;
    public $on_order_delete;
    public $on_integration_config_save;
    public $on_integration_config_delete;
    public $_id;
    public $company_id;
    public $created_at;
    public $updated_at;

    public function __construct($array = null) {
        if (!$array) {
            return;
        }
        foreach ($array as $key => $value) {
            if (!property_exists("\CentrySDK\Webhook", $key)) {
                continue;
            }
            $this->$key = $value;
        }
    }

}
