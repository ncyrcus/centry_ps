<?php
namespace CentryModulePS;

/**
 * Description of ConfigFormPoblateData
 *
 * @author Elías Lama L.
 */
class ConfigFormPoblateData {

    private $helper;
    private $context;

    function __construct($helper, $context) {
        $this->helper = $helper;
        $this->context = $context;
    }

    function poblate() {
        $this->helper->fields_value['CENTRY_MAESTRO'] = \Configuration::get('CENTRY_MAESTRO');
        $this->helper->fields_value['CENTRY_PRODUCT_CHARACTERISTICS'] = \Configuration::get('CENTRY_PRODUCT_CHARACTERISTICS');
        $this->helper->fields_value['CENTRY_API_APPID'] = \Configuration::get('CENTRY_API_APPID');
        $this->helper->fields_value['CENTRY_API_SECRETKEY'] = \Configuration::get('CENTRY_API_SECRETKEY');
        $this->helper->fields_value['CENTRY_MAX_THREADS'] = \Configuration::get('CENTRY_MAX_THREADS',null,null,null,2);
        $this->helper->fields_value['CENTRY_REDIRECT_URI'] = (new \CentrySDK\Webhooks())->getRedirectURI(); //TODO: formar nueva url de redirect uri para webhooks.
        $this->poblateFeaturesHelper();
        if (\Configuration::get('CENTRY_MAESTRO')) {
            $this->poblateGeneralMasterHelper();
            $this->poblateCategoriesHelper();
            $this->poblateManufacturersHelper();
            $this->poblateColorsHelper();
            $this->poblateSizesHelper();
            $this->poblateOrderStatesHelper();
            $this->poblateSyncHelper();
            $this->poblateLimitOrderCreation();
        } else {
            $this->poblateGeneralSlaveHelper();
            $this->poblateSynchronizeFieldHelper();
        }

        $this->poblateStatusHelper();

        return $this->helper;
    }

    private function poblateGeneralMasterHelper() {
        $this->helper->fields_value['CENTRY_CATEGORY_SOURCE'] = \Configuration::get('CENTRY_CATEGORY_SOURCE');
    }

    private function poblateGeneralSlaveHelper() {
        $fields = array("CENTRY_DESCRIPTION", "CENTRY_SHORT_DESCRIPTION");
        foreach ($fields as $field) {
            $this->helper->fields_value[$field . "_ATTRIBUTES_description_attr"] = \Configuration::get($field . "_ATTRIBUTES_description_attr");
            $this->helper->fields_value[$field . "_ATTRIBUTES_shortDescription_attr"] = \Configuration::get($field . "_ATTRIBUTES_shortDescription_attr");
            $this->helper->fields_value[$field . "_ATTRIBUTES_warranty_attr"] = \Configuration::get($field . "_ATTRIBUTES_warranty_attr");
            $this->helper->fields_value[$field . "_ATTRIBUTES_season_attr"] = \Configuration::get($field . "_ATTRIBUTES_season_attr");
            $this->helper->fields_value[$field . "_ATTRIBUTES_year_attr"] = \Configuration::get($field . "_ATTRIBUTES_year_attr");
        }
    }

    private function poblateSynchronizeFieldHelper() {
        $this->helper->fields_value["CENTRY_SYNCHRONIZATION_FIELDS_name"] = \Configuration::get("CENTRY_SYNCHRONIZATION_FIELDS_name");
        $this->helper->fields_value["CENTRY_SYNCHRONIZATION_FIELDS_description"] = \Configuration::get("CENTRY_SYNCHRONIZATION_FIELDS_description");
        $this->helper->fields_value["CENTRY_SYNCHRONIZATION_FIELDS_shortDescription"] = \Configuration::get("CENTRY_SYNCHRONIZATION_FIELDS_shortDescription");
        $this->helper->fields_value["CENTRY_SYNCHRONIZATION_FIELDS_price_compare"] = \Configuration::get("CENTRY_SYNCHRONIZATION_FIELDS_price_compare");
        // $this->helper->fields_value["CENTRY_SYNCHRONIZATION_FIELDS_price"] = \Configuration::get("CENTRY_SYNCHRONIZATION_FIELDS_price");
        $this->helper->fields_value["CENTRY_SYNCHRONIZATION_FIELDS_condition"] = \Configuration::get("CENTRY_SYNCHRONIZATION_FIELDS_condition");
        $this->helper->fields_value["CENTRY_SYNCHRONIZATION_FIELDS_package_dimensions"] = \Configuration::get("CENTRY_SYNCHRONIZATION_FIELDS_package_dimensions");
        $this->helper->fields_value["CENTRY_SYNCHRONIZATION_FIELDS_package_weight"] = \Configuration::get("CENTRY_SYNCHRONIZATION_FIELDS_package_weight");
        $this->helper->fields_value["CENTRY_SYNCHRONIZATION_FIELDS_warranty"] = \Configuration::get("CENTRY_SYNCHRONIZATION_FIELDS_warranty");
        $this->helper->fields_value["CENTRY_SYNCHRONIZATION_FIELDS_barcode"] = \Configuration::get("CENTRY_SYNCHRONIZATION_FIELDS_barcode");
        $this->helper->fields_value["CENTRY_SYNCHRONIZATION_FIELDS_sku"] = \Configuration::get("CENTRY_SYNCHRONIZATION_FIELDS_sku");
        $this->helper->fields_value["CENTRY_SYNCHRONIZATION_FIELDS_images"] = \Configuration::get("CENTRY_SYNCHRONIZATION_FIELDS_images");
        $this->helper->fields_value["CENTRY_CATEGORYS_BEHAVIOR"] = \Configuration::get("CENTRY_CATEGORYS_BEHAVIOR");
        $this->helper->fields_value["CENTRY_PRICE_OFFER_BEHAVIOR"] = \Configuration::get("CENTRY_PRICE_OFFER_BEHAVIOR");
    }

    private function poblateLimitOrderCreation() {
        $this->helper->fields_value["CENTRY_LIMIT_ORDER_CREATION"] = \Configuration::get("CENTRY_LIMIT_ORDER_CREATION");
    }

    private function poblateCategoriesHelper() {
        $options=array();
        foreach ((new \CentrySDK\Categories())->all() as $category) {
            $options[] = array(
                'id' => $category->_id, // The value of the 'value' attribute of the <option> tag.
                'text' => $category->name      // The value of the text content of the  <option> tag.
            );
        }
        $this->helper->fields_value['CENTRY_CATEGORIES'] = json_encode($options);
        $previus_select = array() ;
        foreach (\Category::getSimpleCategories($this->context->language->id) as $category) {
            $cat = new \Category($category["id_category"]);
            if (isset($cat->id_centry)) {
                $previus_select[] = array(
                    "id_category" => $category['id_category'],
                    "id_centry"   => $cat->id_centry
                );
                //$this->helper->fields_value["categories[{$category['id_category']}]"] = $cat->id_centry;
            }
        }
        $this->helper->fields_value['CENTRY_CATEGORIES_SELECTED'] = json_encode($previus_select);

    }

    private function poblateManufacturersHelper() {
        $options=array();
        foreach ((new \CentrySDK\Brands())->all() as $brand) {
            $options[] = array(
                'id' => $brand->_id, // The value of the 'value' attribute of the <option> tag.
                'text' => $brand->name      // The value of the text content of the  <option> tag.
            );
        }
        $this->helper->fields_value['CENTRY_MANUFACTURER'] = json_encode($options);
        $previus_select = array() ;
        foreach (\Manufacturer::getManufacturers() as $manufacturer) {
            $manu = new \Manufacturer($manufacturer["id_manufacturer"]);
            if (isset($manu->id_centry)) {
                $previus_select[] = array(
                    "id_manufacturer" => $manufacturer['id_manufacturer'],
                    "id_centry"   => $manu->id_centry
                );
                // $this->helper->fields_value["manufacturers[{$manu->id}]"] = $manu->id_centry;
            }
        }
        $this->helper->fields_value['CENTRY_MANUFACTURER_SELECTED'] = json_encode($previus_select);

    }

    private function poblateFeaturesHelper() {
        $this->helper->fields_value['CENTRY_FEATURE_SEASON_YEAR'] = \Configuration::get('CENTRY_FEATURE_SEASON_YEAR');
        $this->helper->fields_value['CENTRY_FEATURE_SEASON'] = \Configuration::get('CENTRY_FEATURE_SEASON');
        $this->helper->fields_value['CENTRY_FEATURE_GENDER'] = \Configuration::get('CENTRY_FEATURE_GENDER');
    }

    private function poblateColorsHelper() {
        if (($group = \AttributeGroup::findByFieldCentry("color"))) {
            $this->helper->fields_value["color_group"] = $group->id;

            foreach (\AttributeGroup::getAttributes((int) (\Configuration::get('PS_LANG_DEFAULT')), $group->id) as $color) {
                $c = new \Attribute($color["id_attribute"]);
                if (isset($c->id_centry)) {
                    $this->helper->fields_value["color[{$c->id}]"] = $c->id_centry;
                }
            }
        }
    }

    private function poblateSizesHelper() {
        if (($group = \AttributeGroup::findByFieldCentry("size"))) {
            $this->helper->fields_value["size_group"] = $group->id;

            foreach (\AttributeGroup::getAttributes((int) (\Configuration::get('PS_LANG_DEFAULT')), $group->id) as $size) {
                $s = new \Attribute($size["id_attribute"]);
                if (isset($s->id_centry)) {
                    $this->helper->fields_value["size[{$s->id}]"] = $s->id_centry;
                }
            }
        }
    }

    private function poblateOrderStatesHelper() {
        $this->helper->fields_value['CENTRY_ORDERSTATE_PENDING'] = \Configuration::get('CENTRY_ORDERSTATE_PENDING');
        $this->helper->fields_value['CENTRY_ORDERSTATE_SHIPPED'] = \Configuration::get('CENTRY_ORDERSTATE_SHIPPED');
        $this->helper->fields_value['CENTRY_ORDERSTATE_RECEIVED'] = \Configuration::get('CENTRY_ORDERSTATE_RECEIVED');
    }

    private function poblateSyncHelper() {
        $products = (new \Product())->getProducts($this->context->language->id, 0, 0, 'id_product', 'ASC');
        $ids = array();
        foreach ($products as $product) {
            $ids[] = $product["id_product"];
        }
        $this->helper->fields_value['CENTRY_SYNC_PRODUCTS_IDS'] = implode(",", $ids);
        $this->helper->fields_value['CENTRY_AJAX_URL'] = \Tools::getShopDomainSsl(true) . __PS_BASE_URI__ . 'modules/centry_ps/views/ajax/ajax-call.php';
    }

    private function poblateStatusHelper() {
        $status = "Datos del servidor (no reparables desde el módulo):\n"
                . $this->getStatusLine("Memory", $this->testMemoryLimit())
                . $this->getStatusLine("MaxExecutionTime", $this->testMaxExecutionTime())
                . $this->getStatusLine("PING Centry", $this->testPing())
                . $this->getStatusLine("CPU", $this->testCPU())
                . $this->getStatusLine("Semaforos", $this->TestFunctionExists("sem_get"))
                . "\nDatos del módulo (reparables):\n"
                . $this->getStatusLine("Tabla Atributos", $this->testTableDefinition("attribute_centry", array("id_centry", "id_attribute")))
                . $this->getStatusLine("Tabla Grupo de Atributos", $this->testTableDefinition("attribute_group_centry", array("field_centry", "id_attribute_group")))
                . $this->getStatusLine("Tabla Categorías", $this->testTableDefinition("category_centry", array("id_centry", "id_category")))
                . $this->getStatusLine("Tabla Combinaciones", $this->testTableDefinition("combination_centry", array("id_centry", "id_combination", "created_at", "updated_at", "sku")))
                . $this->getStatusLine("Tabla Imágenes", $this->testTableDefinition("image_centry", array("id_centry", "id_image", "cover", "content_type", "file_name", "file_size", "fingerprint", "created_at", "updated_at", "url")))
                . $this->getStatusLine("Tabla Marcas", $this->testTableDefinition("manufacturer_centry", array("id_centry", "id_manufacturer")))
                . $this->getStatusLine("Tabla Órdenes", $this->testTableDefinition("order_centry", array("id_centry", "id_order")))
                . $this->getStatusLine("Tabla Productos", $this->testTableDefinition("product_centry", array("id_centry", "id_product", "centry_category")));

        $this->helper->fields_value['CENTRY_MODULE_STATUS'] = $status;
    }

    private function getStatusLine($item, $message) {
        $labelError = "[Error]   ";
        $labelOk = "[Ok]      ";
        if ($item == "Semaforos"){
          return ($message ? "[Warning] " : $labelOk) . "$item: $message\n";
        }
        return ($message ? $labelError : $labelOk) . "$item: $message\n";
    }

    private function TestFunctionExists($function){
      return function_exists($function)? false : "No existe la función";
    }

    private function testMemoryLimit() {
        ini_set('memory_limit', '1024');
        $med1 = ini_get('memory_limit');
        ini_set('memory_limit', '-1');
        $med2 = ini_get('memory_limit');
        return $med1 != $med2 ? false : "No es posible modificar el límite de memoria ini_set('memory_limit', ...);";
    }

    private function testMaxExecutionTime() {
        ini_set('max_execution_time', 300);
        $med1 = ini_get('max_execution_time');
        ini_set('max_execution_time', 400);
        $med2 = ini_get('max_execution_time');
        return $med1 != $med2 ? false : "Tiempo máximo de ejecución inmodificable ini_set('max_execution_time', ...);";
    }

    private function testPing() {
        $tB = microtime(true);
        $fP = fsockopen("www.centry.cl", 443, $errno, $errstr, 10);
        if (!$fP) {
            return "Toma más de 10 segundos establecer la conección ($errno - $errstr)";
        }
        $tA = microtime(true);
        $ping = round((($tA - $tB) * 1000), 0);
        return $ping < 200 ? null : "Advertencia: PING elevado $ping ms";
    }

    private function testCPU() {
      if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        return "Qué vergüenza. Corremos en windows.";
      } else {
        $cpuLoad = sys_getloadavg();
        $avg = round(($cpuLoad[0] + $cpuLoad[1] + $cpuLoad[2]) / 3.0, 2);
        return $avg < 0.5 ? false : "Alta carga de la CPU en los últimos 1, 5 y 15 minutos ($cpuLoad[0], $cpuLoad[1], $cpuLoad[2])";
      }
    }

    private function testTableDefinition($table, $columns) {
        $tableInfo = \Db::getInstance()->executeS("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '" . _DB_PREFIX_ . $table . "' AND TABLE_SCHEMA = '" . _DB_NAME_ . "'");
        if (!$tableInfo) {
            return "No existe la tabla";
        }
        foreach ($columns as $column) {
            $present = false;
            foreach ($tableInfo as $c) {
                if ($column == $c["COLUMN_NAME"]) {
                    $present = true;
                    break;
                }
            }
            if (!$present) {
                return "No tiene la columna $column";
            }
        }
        return false;
    }

}
