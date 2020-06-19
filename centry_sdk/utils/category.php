<?php

require_once 'abstractresource.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/classes/Log.php';


/**
 * Define como el controlador atenderá las 4 acciones básicas del CRUD de un
 * recurso Category.
 *
 * @author Paulo Sandoval S.
 */
class Centry_psCategoryModuleFrontController extends Centry_psAbstractResource {

    protected function create($params) {
        if (Category::findCategoryByIdCentry($params["_id"])) {
            return false;
        }
        try {
            $cat = new \CentrySDK\Category($params);
            $cat->saveInPrestashop();
            return true;
        } catch (Exception $ex) {
            error_log($ex->getMessage() . "\n" . $ex->getTraceAsString());
            Log::d("Fallo al crear categoría " , $ex->getMessage() . "\n" .$ex->getTraceAsString());
            return false;
        }
    }

    protected function update($params) {
        $cat = Category::findCategoryByIdCentry($params["_id"]);
        if (!$cat) {
            return false;
        }
        if ($cat->delete()) {
            self::create($params);
        } else {
            return false;
        }
    }

    protected function delete($id_centry) {
        $cat = Category::findCategoryByIdCentry($id_centry);
        if (!$cat) {
            return false;
        }
        try {
            if ($cat->delete()) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $ex) {
            error_log($ex->getMessage() . "\n" . $ex->getTraceAsString());
            Log::d("Fallo al eliminar categoría " , $ex->getMessage() . "\n" .$ex->getTraceAsString());
            return false;
        }
    }

    protected function read($id_centry) {
        $cat = Category::findCategoryByIdCentry($id_centry);
        return $cat == false ? false : array(
            "_id" => $cat->id_centry,
            "created_at" => $cat->date_add,
            "name" => $cat->name[(int) Configuration::get('PS_LANG_DEFAULT')],
            "updated_at" => $cat->date_upd
        );
    }

}
