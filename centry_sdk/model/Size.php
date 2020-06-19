<?php

namespace CentrySDK;

require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/model/AbstractModel.php';

/**
 * Description of Size
 *
 * @author Elías Lama L.
 */
class Size extends AbstractModel {

    public $_id;
    public $name;
    public $created_at;
    public $updated_at;

    public function __construct($array = null) {
        if ($array) {
            $this->_id = $array["_id"];
            $this->name = $array["name"];
            $this->created_at = $array["created_at"];
            $this->updated_at = $array["updated_at"];
        }
    }

}
