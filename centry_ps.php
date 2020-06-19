<?php
require_once _PS_MODULE_DIR_ . 'centry_ps/classes/Log.php';
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Clase principal del módulo de Centry®.
 *
 * @author Paulo Sandoval S.
 */
class Centry_ps extends Module {

    const CATEGORY_SOURCE_PRIMARY = "CATEGORY_SOURCE_PRIMARY";
    const CATEGORY_SOURCE_LOWER_SECONDARY = "CATEGORY_SOURCE_LOWER_SECONDARY";

    public function __construct() {
        $this->name = 'centry_ps';
        $this->tab = 'market_place';
        $this->controllers = array("centry", "abstractresource", "product");
        $this->version = '2.0.5';
        $this->author = 'Centry';
        $this->need_instance = 0; // if needs warning message -> set to 1
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Centry® for PrestaShop');
        $this->description = $this->l('Synchronizes your inventory with the world\'s best ecommerce connector.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        if (!Configuration::get('CENTRY_API_APPID')) {
            $this->warning = $this->l('No app id provided');
        }
        if (!Configuration::get('CENTRY_API_SECRETKEY')) {
            $this->warning = $this->l('No secret key provided');
        }
        if ((!Configuration::get('PS_GUEST_CHECKOUT_ENABLED') == 1)) {
            $this->warning = $this->l('Checkout guest must be enable');
        }

        $this->addRequiredClasses();
    }

    private function addRequiredClasses() {
        require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/Webhooks.php';
        require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/Orders.php';
        require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/Categories.php';
        require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/Brands.php';
        require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/Products.php';
        require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/Colors.php';
        require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/Sizes.php';
        require_once _PS_MODULE_DIR_ . 'centry_ps/classes/PendingNotification.php';
        require_once _PS_MODULE_DIR_ . 'centry_ps/classes/Processing.php';

        require_once _PS_MODULE_DIR_ . 'centry_ps/helpers/ConfigFormPersistence.php';
        require_once _PS_MODULE_DIR_ . 'centry_ps/helpers/ConfigFormFieldsCreator.php';
        require_once _PS_MODULE_DIR_ . 'centry_ps/helpers/ConfigFormPoblateData.php';
        require_once _PS_MODULE_DIR_ . 'centry_ps/helpers/ProductQueue.php';
        require_once _PS_MODULE_DIR_ . 'centry_ps/helpers/OrderQueue.php';
    }

    public function install() {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if (!parent::install() ||
                !$this->whenInstall("\\Product", "createTable") ||
                !$this->whenInstall("\\Category", "createTable") ||
                !$this->whenInstall("\\Manufacturer", "createTable") ||
                !$this->whenInstall("\\Combination", "createTable") ||
                !$this->whenInstall("\\Attribute", "createTable") ||
                !$this->whenInstall("\\AttributeGroup", "createTable") ||
                !$this->whenInstall("\\Order", "createTable") ||
                !$this->whenInstall("\\Image", "createTable") ||
                !$this->whenInstall("\\PendingNotification", "createTable") ||
                !$this->whenInstall("\\Processing", "createTable") ||
                !$this->registerHook('actionValidateOrder') ||
                !$this->registerHook('displayHeader') ||
                !$this->registerHook('actionProductSave') ||
                !$this->registerHook('actionProductDelete') ||
                !$this->registerHook('actionOrderHistoryAddAfter')
        ) {
            return false;
        }
        Processing::add_first_row();
        $defaultRowsConfiguration = array(
            "CENTRY_SYNCHRONIZATION_FIELDS_name",
            "CENTRY_SYNCHRONIZATION_FIELDS_description",
            "CENTRY_SYNCHRONIZATION_FIELDS_shortDescription",
            // "CENTRY_SYNCHRONIZATION_FIELDS_price_compare",
            "CENTRY_SYNCHRONIZATION_FIELDS_price",
            "CENTRY_SYNCHRONIZATION_FIELDS_condition",
            "CENTRY_SYNCHRONIZATION_FIELDS_package_dimensions",
            "CENTRY_SYNCHRONIZATION_FIELDS_package_weight",
            "CENTRY_SYNCHRONIZATION_FIELDS_warranty",
            "CENTRY_SYNCHRONIZATION_FIELDS_barcode",
            "CENTRY_SYNCHRONIZATION_FIELDS_sku",
            "CENTRY_SYNCHRONIZATION_FIELDS_images",
            "CENTRY_DESCRIPTION_ATTRIBUTES_description_attr",
            // "CENTRY_DESCRIPTION_ATTRIBUTES_shortDescription_attr",
            // "CENTRY_DESCRIPTION_ATTRIBUTES_warranty_attr",
            // "CENTRY_DESCRIPTION_ATTRIBUTES_season_attr",
            // "CENTRY_DESCRIPTION_ATTRIBUTES_year_attr",
            // "CENTRY_SHORT_DESCRIPTION_ATTRIBUTES_description_attr",
            "CENTRY_SHORT_DESCRIPTION_ATTRIBUTES_shortDescription_attr",
            // "CENTRY_SHORT_DESCRIPTION_ATTRIBUTES_warranty_attr",
            // "CENTRY_SHORT_DESCRIPTION_ATTRIBUTES_season_attr",
            // "CENTRY_SHORT_DESCRIPTION_ATTRIBUTES_year_attr"
        );
        /////////////////////  Valores por defecto en la configuración del módulo///////////////
        foreach ($defaultRowsConfiguration as $initialConfig) {
            if (!\Configuration::getIdByName($initialConfig)) {
                \Configuration::updateValue($initialConfig, "on");
            }
        }
        if (!\Configuration::getIdByName("CENTRY_CATEGORYS_BEHAVIOR")) {
            \Configuration::updateValue("CENTRY_CATEGORYS_BEHAVIOR", -1);
        }
        if (!\Configuration::getIdByName("CENTRY_PRICE_OFFER_BEHAVIOR")) {
            \Configuration::updateValue("CENTRY_PRICE_OFFER_BEHAVIOR", -1);
        }

        return true;
    }

    private function whenInstall($class, $method) {
        if (!method_exists($class, $method)) {
            $this->_errors[] = Tools::displayError(sprintf(
                                    $this->l('There is no method %1$s in class %2$s. This is happening because the module has no write permission to override the default prestashop classes. Contact your webmaster to fix this problem.')
                                    , $method
                                    , $class), false);
            return false;
        } elseif (!$class::$method()) {
            $this->_errors[] = Tools::displayError(sprintf($this->l('There was an error calling the %1$s\'s %2$s method.'), $class, $method), false);
            return false;
        }
        return true;
    }

    public function uninstall() {
        if (
        // !$this->whenUninstall("\\Product", "dropTable") ||
        // !$this->whenUninstall("\\Category", "dropTable") ||
        // !$this->whenUninstall("\\Manufacturer", "dropTable") ||
        // !$this->whenUninstall("\\Combination", "dropTable") ||
        // !$this->whenUninstall("\\Attribute", "dropTable") ||
        // !$this->whenUninstall("\\AttributeGroup", "dropTable") ||
        // !$this->whenUninstall("\\Order", "dropTable") ||
        // !$this->whenUninstall("\\Image", "dropTable") ||
//                !Configuration::deleteByName('CENTRY_API_ACCESS_TOKEN') ||
//                !Configuration::deleteByName('CENTRY_API_ACCESS_TOKEN') ||
//                !Configuration::deleteByName('CENTRY_API_APPID') ||
//                !Configuration::deleteByName('CENTRY_API_SECRETKEY')
                !parent::uninstall()
        ) {
            return false;
        }
        return true;
    }

    private function whenUninstall($class, $method) {
        return method_exists($class, $method) ? $class::$method() : true;
    }

    public function getContent() {
        $output = null;
        if (Tools::isSubmit('submit' . $this->name)) {
            (new CentryModulePS\ConfigFormPersistence())->saveForm();
            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }
        return $output . $this->displayForm();
    }

    public function poblateHelper() {
        $helper = new HelperForm();
        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit' . $this->name;
        $helper->toolbar_btn = array(
            'save' =>
            array(
                'desc' => $this->l('Save'), 'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'), 'desc' => $this->l('Back to list')
            )
        );
        return (new CentryModulePS\ConfigFormPoblateData($helper, $this->context))->poblate();
    }

    private function displayForm() {
        $fields_form = (new CentryModulePS\ConfigFormFieldsCreator($this, $this->context, $this->_path))->gteFieldsForm();
        $helper = $this->poblateHelper();
        return $helper->generateForm($fields_form);
    }

    public function hookactionValidateOrder($params) {
        (new CentrySDK\Orders())->save($params["order"]);
    }

    public function hookactionOrderHistoryAddAfter($params) {
        $order = new Order($params["order_history"]->id_order);
        CentryModulePS\OrderQueue::asyncRequest($params["order_history"]->id_order);
        if (Configuration::get('CENTRY_MAESTRO')) {
            $cart = $order->getCartProducts();
            foreach ($cart as $value) {
                CentryModulePS\ProductQueue::asyncRequest($value['product_id']);
            }
        }
    }

    public function hookactionProductSave($params) {
        if (Configuration::get('CENTRY_MAESTRO')) {
            $product = new Product($params["id_product"]);
            (new \CentrySDK\Products())->save($product);
            // CentryModulePS\ProductQueue::asyncRequest($params["id_product"]);
        }
    }

    public function hookactionProductDelete($params) {
        if (Configuration::get('CENTRY_MAESTRO')) {
            $productsSDK = new CentrySDK\Products();
            $productsSDK->delete($params["product"]->id_centry);
        }
    }

    /**
     * Solicita evaluar el código pasado en el campo "code" para confirmar si la
     * autorización de acceso OAuth es correcta o no.
     */
    public function hookdisplayHeader() {
        if (($code = \Tools::getValue('code'))) {
            $appSDK = new CentrySDK\Webhooks();
            $js = $appSDK->requestAccessToken($code) ? "success" : "fail";
            $this->context->controller->addJS($this->_path . "views/js/oauth_token_$js.js", 'all');
            if ($js == "success") {
                $appSDK->registerWH();
            }
        }
    }

}
