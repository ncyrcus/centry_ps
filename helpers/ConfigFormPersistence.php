<?php

namespace CentryModulePS;

require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/Webhooks.php';

/**
 * Description of ConfigFormPersistence
 *
 * @author Elías Lama L.
 */
class ConfigFormPersistence {

    private $lang_id;

    function __construct() {
        $this->lang_id = (int) \Configuration::get('PS_LANG_DEFAULT');
    }

    public function saveForm() {
        $this->updateIfPresent('CENTRY_MAESTRO');
        $this->updateIfPresent('CENTRY_PRODUCT_CHARACTERISTICS');
        $this->updateIfPresent('CENTRY_API_APPID');
        $this->updateIfPresent('CENTRY_API_SECRETKEY');
        $this->updateIfPresent('CENTRY_MAX_THREADS');
        ini_set('max_execution_time', 300);
        if (\Configuration::get('CENTRY_MAESTRO')) {
            $this->saveGeneralMasterConfig();
            $this->saveCategoryTranslation();
            $this->saveMaufacturerTranslation();
            $this->saveFeatures();
            $this->saveColors();
            $this->saveSizes();
            $this->saveOrderStates();
            $this->saveLimitOrderCreation();
        } else {
            $this->saveGeneralSlaveConfig();
            $this->saveSynchronizeFieldConfig();
        }
        $this->repairStatus();
    }

    private function updateIfPresent($key) {
        if (\Tools::getValue($key) !== false) {
            \Configuration::updateValue($key, \Tools::getValue($key));
            if ($key == 'CENTRY_MAESTRO') {
                (new \CentrySDK\Webhooks())->registerWH();
            }
        }
    }

    private function saveGeneralSlaveConfig() {
        $fields = array("CENTRY_DESCRIPTION", "CENTRY_SHORT_DESCRIPTION");
        foreach ($fields as $field) {
            \Configuration::updateValue($field . "_ATTRIBUTES_description_attr", \Tools::getValue($field . "_ATTRIBUTES_description_attr"));
            \Configuration::updateValue($field . "_ATTRIBUTES_shortDescription_attr", \Tools::getValue($field . "_ATTRIBUTES_shortDescription_attr"));
            \Configuration::updateValue($field . "_ATTRIBUTES_warranty_attr", \Tools::getValue($field . "_ATTRIBUTES_warranty_attr"));
            \Configuration::updateValue($field . "_ATTRIBUTES_season_attr", \Tools::getValue($field . "_ATTRIBUTES_season_attr"));
            \Configuration::updateValue($field . "_ATTRIBUTES_year_attr", \Tools::getValue($field . "_ATTRIBUTES_year_attr"));
        }
    }

    private function saveSynchronizeFieldConfig() {

        \Configuration::updateValue("CENTRY_SYNCHRONIZATION_FIELDS_name", \Tools::getValue("CENTRY_SYNCHRONIZATION_FIELDS_name"));
        \Configuration::updateValue("CENTRY_SYNCHRONIZATION_FIELDS_description", \Tools::getValue("CENTRY_SYNCHRONIZATION_FIELDS_description"));
        \Configuration::updateValue("CENTRY_SYNCHRONIZATION_FIELDS_shortDescription", \Tools::getValue("CENTRY_SYNCHRONIZATION_FIELDS_shortDescription"));
        \Configuration::updateValue("CENTRY_SYNCHRONIZATION_FIELDS_price_compare", \Tools::getValue("CENTRY_SYNCHRONIZATION_FIELDS_price_compare"));
        // \Configuration::updateValue("CENTRY_SYNCHRONIZATION_FIELDS_price", \Tools::getValue("CENTRY_SYNCHRONIZATION_FIELDS_price"));
        \Configuration::updateValue("CENTRY_SYNCHRONIZATION_FIELDS_condition", \Tools::getValue("CENTRY_SYNCHRONIZATION_FIELDS_condition"));
        \Configuration::updateValue("CENTRY_SYNCHRONIZATION_FIELDS_package_dimensions", \Tools::getValue("CENTRY_SYNCHRONIZATION_FIELDS_package_dimensions"));
        \Configuration::updateValue("CENTRY_SYNCHRONIZATION_FIELDS_package_weight", \Tools::getValue("CENTRY_SYNCHRONIZATION_FIELDS_package_weight"));
        \Configuration::updateValue("CENTRY_SYNCHRONIZATION_FIELDS_warranty", \Tools::getValue("CENTRY_SYNCHRONIZATION_FIELDS_warranty"));
        \Configuration::updateValue("CENTRY_SYNCHRONIZATION_FIELDS_barcode", \Tools::getValue("CENTRY_SYNCHRONIZATION_FIELDS_barcode"));
        \Configuration::updateValue("CENTRY_SYNCHRONIZATION_FIELDS_sku", \Tools::getValue("CENTRY_SYNCHRONIZATION_FIELDS_sku"));
        \Configuration::updateValue("CENTRY_SYNCHRONIZATION_FIELDS_images", \Tools::getValue("CENTRY_SYNCHRONIZATION_FIELDS_images"));
        \Configuration::updateValue("CENTRY_CATEGORYS_BEHAVIOR", \Tools::getValue("CENTRY_CATEGORYS_BEHAVIOR"));
        \Configuration::updateValue("CENTRY_PRICE_OFFER_BEHAVIOR", \Tools::getValue("CENTRY_PRICE_OFFER_BEHAVIOR"));

    }

    private function saveLimitOrderCreation() {
        \Configuration::updateValue("CENTRY_LIMIT_ORDER_CREATION", \Tools::getValue("CENTRY_LIMIT_ORDER_CREATION"));
    }

    private function saveGeneralMasterConfig() {
        $this->updateIfPresent('CENTRY_CATEGORY_SOURCE');
    }

    private function saveCategoryTranslation() {
        if (is_array(\Tools::getValue('categories'))) {
            foreach (\Tools::getValue('categories') as $id_category => $id_centry) {
                $category = new \Category($id_category);
                $category->id_centry = $id_centry;
                $category->save();
            }
        }
    }

    private function saveMaufacturerTranslation() {
        if (is_array(\Tools::getValue('manufacturers'))) {
            foreach (\Tools::getValue('manufacturers') as $id_manufacturer => $id_centry) {
                $manufacturer = new \Manufacturer($id_manufacturer);
                $manufacturer->id_centry = $id_centry;
                $manufacturer->save();
            }
        }
    }

    private function saveFeatures() {
        $this->updateIfPresent('CENTRY_FEATURE_SEASON_YEAR');
        $this->updateIfPresent('CENTRY_FEATURE_SEASON');
        $this->updateIfPresent('CENTRY_FEATURE_GENDER');
    }

    private function saveColors() {
        if (($group = \AttributeGroup::findByFieldCentry("color")) && \Tools::getValue("color_group") && $group->id != \Tools::getValue("color_group")) {
            foreach (\AttributeGroup::getAttributes((int) (\Configuration::get('PS_LANG_DEFAULT')), $group->id) as $color) {
                $c = new \Attribute($color["id_attribute"]);
                $c->id_centry = null;
                $c->save();
            }
            $group->field_centry = null;
            $group->save();
        } elseif (is_array(\Tools::getValue('color'))) {
            foreach (\Tools::getValue('color') as $id_color => $id_centry) {
                $color = new \Attribute($id_color);
                $color->id_centry = $id_centry;
                $color->save();
            }
        }
        if (\Tools::getValue("color_group") && trim(\Tools::getValue("color_group")) != "") {
            $colorGroup = new \AttributeGroup(\Tools::getValue("color_group"));
            $colorGroup->field_centry = 'color';
            $colorGroup->save();
        }
    }

    private function importCentryColors() {
        if (\Tools::getValue('colors')) {
            if (!($group = \AttributeGroup::findByFieldCentry("color"))) {
                $newGroup = new \AttributeGroup();
                $newGroup->group_type = 'color';
                $newGroup->name[$this->lang_id] = 'Color';
                $newGroup->public_name[$this->lang_id] = 'Color';
                $newGroup->field_centry = 'color';
                $newGroup->save();
                $group = $newGroup;
            }

            $group = \AttributeGroup::findByFieldCentry("color");
            $sdk = new \CentrySDK\Colors();
            foreach (\Tools::getValue('colors') as $id_centry) {
                $color = $sdk->findById($id_centry);
                $newAttribute = new \Attribute();
                $newAttribute->name[$this->lang_id] = $color->name;
                $newAttribute->id_centry = $id_centry;
                $newAttribute->id_attribute_group = $group->id;
                $newAttribute->save();
            }
        }
    }

    private function saveSizes() {
        if (($group = \AttributeGroup::findByFieldCentry("size")) && \Tools::getValue("size_group") && $group->id != \Tools::getValue("size_group")) {
            foreach (\AttributeGroup::getAttributes((int) (\Configuration::get('PS_LANG_DEFAULT')), $group->id) as $size) {
                $s = new \Attribute($size["id_attribute"]);
                $s->id_centry = null;
                $s->save();
            }
            $group->field_centry = null;
            $group->save();
        } elseif (is_array(\Tools::getValue('size'))) {
            foreach (\Tools::getValue('size') as $id_size => $id_centry) {
                $size = new \Attribute($id_size);
                $size->id_centry = $id_centry;
                $size->save();
            }
        }
        if (\Tools::getValue("size_group") && trim(\Tools::getValue("size_group")) != "") {
            $sizeGroup = new \AttributeGroup(\Tools::getValue("size_group"));
            $sizeGroup->field_centry = 'size';
            $sizeGroup->save();
        }
    }

    private function importCentrySizes() {
        if (\Tools::getValue('sizes')) {
            if (!($group = \AttributeGroup::findByFieldCentry("size"))) {
                $newGroup = new \AttributeGroup();
                $newGroup->name[$this->lang_id] = 'Talla';
                $newGroup->public_name[$this->lang_id] = 'Talla';
                $newGroup->group_type = 'select';
                $newGroup->field_centry = 'size';
                $newGroup->save();
                $group = $newGroup;
            }

            $group = \AttributeGroup::findByFieldCentry("size");
            $sdk = new \CentrySDK\Sizes();
            foreach (\Tools::getValue('sizes') as $id_centry) {
                $size = $sdk->findById($id_centry);
                $newAttribute = new \Attribute();
                $newAttribute->name[$this->lang_id] = $size->name;
                $newAttribute->id_centry = $id_centry;
                $newAttribute->id_attribute_group = $group->id;
                $newAttribute->save();
            }
        }
    }

    private function saveOrderStates() {
        $this->updateIfPresent('CENTRY_ORDERSTATE_PENDING');
        $this->updateIfPresent('CENTRY_ORDERSTATE_SHIPPED');
        $this->updateIfPresent('CENTRY_ORDERSTATE_RECEIVED');
        \Configuration::updateValue("CENTRY_ORDERSTATE_CANCELLED", \Configuration::get('PS_OS_CANCELED'));
    }

    private function repairStatus() {
        $this->repairTable("attribute_centry", "\\Attribute");
        $this->repairTable("attribute_group_centry", "\\AttributeGroup");
        $this->repairTable("category_centry", "\\Category");
        $this->repairTable("combination_centry", "\\Combination");
        $this->repairTable("image_centry", "\\Image");
        $this->repairTable("manufacturer_centry", "\\Manufacturer");
        $this->repairTable("order_centry", "\\Order");
        $this->repairTable("product_centry", "\\Product");
    }

    private function repairTable($tableName, $className) {
        if (\Tools::getValue("CENTRY_FIELDS_TO_REPAIR_$tableName")) {
            $className::dropTable();
            $className::createTable();
        }
    }

}
