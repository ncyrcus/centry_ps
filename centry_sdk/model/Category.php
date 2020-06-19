<?php

namespace CentrySDK;

require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/model/AbstractModel.php';
include_once _PS_MODULE_DIR_ . 'centry_ps/include/utils.php';

/**
 * Description of Category
 *
 * @author Elías Lama L.
 */
class Category extends AbstractModel {

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

    /**
     * Crea una Categoría de Prestashop en base a esta Categoría de Centry y la
     * retorna.
     * @return \Category
     */
    public function saveInPrestashop() {
        $id_lang = (int) \Configuration::get('PS_LANG_DEFAULT');
        $cat = new \Category();
        $cat->id_centry = $this->_id;
        $cat->name = array($id_lang => $this->name);
        $cat->description = array($id_lang => $this->name);
        $cat->id_parent = 2; // Papá siempre es 2, hasta que tenga otra categoría Padre
        $cat->is_root_category = false; // ???
        $cat->link_rewrite = array($id_lang => generateLinkRewrite($this->name));
        $cat->active = true;
        $cat->id_shop_default = (int) \Configuration::get('PS_SHOP_DEFAULT');
        $cat->meta_description = array($id_lang => $this->name);
        $cat->meta_keywords = array($id_lang => generateLinkRewrite($this->name));
        $cat->meta_title = array($id_lang => $this->name);
        $cat->date_add = $this->created_at;
        $cat->date_upd = $this->updated_at;
//        $cat->add();
        $cat->save();
        return $cat;
    }

}
