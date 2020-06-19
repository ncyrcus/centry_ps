<?php

namespace CentryModulePS;

/**
 * Description of ConfigFormFieldsCreator
 *
 * @author Elías Lama L.
 */
class ConfigFormFieldsCreator {

    private $module;
    private $context;
    private $_path;

    function __construct($module, $context, $_path) {
        $this->module = $module;
        $this->context = $context;
        $this->_path = $_path;
    }

    public function gteFieldsForm() {
        $fields_form = [];
        $fields_form[]['form'] = $this->generateGeneralForm();
        if (\Configuration::get('CENTRY_MAESTRO')) {
            $fields_form[]['form'] = $this->generateGeneralMasterForm();
            $fields_form[]['form'] = $this->generateCategoryTranslationForm();
            $fields_form[]['form'] = $this->generateManufacturerTranslationForm();
            $fields_form[]['form'] = $this->generateFeaturesForm();
            $fields_form[]['form'] = $this->generateColorsForm();
            $fields_form[]['form'] = $this->generateSizesForm();
            $fields_form[]['form'] = $this->generateImportCentryColorsForm();
            $fields_form[]['form'] = $this->generateImportCentrySizesForm();
            $fields_form[]['form'] = $this->generateOrderStateForm();
            $fields_form[]['form'] = $this->generateSyncForm();
            $fields_form[]['form'] = $this->limitOrdersCreation();
            $this->context->controller->addJS(($this->_path) . 'js/search.js');
        } else {
            $fields_form[]['form'] = $this->generateGeneralSlaveForm();
            $fields_form[]['form'] = $this->generateSynchronizeFieldForm();
        }
        $fields_form[]['form'] = $this->generateCheckStatusForm();
        return $fields_form;
    }

    private function limitOrdersCreation() {
        return array(
            'legend' => array(
                'title' => $this->module->l('Order limit creation date')
            ),
            'input' => array(
                array(
                    'type' => 'date',
                    'label' => $this->module->l('This time prevent the creation of orders before it:'),
                    'name' => 'CENTRY_LIMIT_ORDER_CREATION',
                    'size' => 10,
                    'required' => true,
                    'desc' => $this->module->l('All orders with date of creation before this date will not create.'),
                ),
            ),
            'submit' => array('title' => $this->module->l('Save'), 'class' => 'button')
        );
    }

    private function generateGeneralForm() {
        $masterLittleSection = array();
        if (\Configuration::get('CENTRY_MAESTRO')) {
            $masterLittleSection = array(
                'type' => 'radio',
                'label' => $this->module->l('Where to store/read product\'s characteristics'),
                'name' => 'CENTRY_PRODUCT_CHARACTERISTICS',
                'required' => true,
                'class' => 't',
                'is_bool' => true,
                'values' => array(
                    array(
                        'id' => 'active_on',
                        'value' => 1,
                        'label' => $this->module->l('Long description')
                    ),
                    array(
                        'id' => 'active_off',
                        'value' => 0,
                        'label' => $this->module->l('Features')
                    )
                ),
            );
        }
        return array(
            'legend' => array(
                'title' => $this->module->l('Settings')
            ),
            'input' => array(
                array(
                    'type' => 'radio',
                    'label' => $this->module->l('Mode'),
                    'name' => 'CENTRY_MAESTRO',
                    'required' => true,
                    'class' => 't',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->module->l('Master')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->module->l('Slave')
                        )
                    ),
                ),
                $masterLittleSection,
                array(
                    'type' => 'text',
                    'label' => $this->module->l('App id'),
                    'name' => 'CENTRY_API_APPID',
                    'size' => 100,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->module->l('Secret key'),
                    'name' => 'CENTRY_API_SECRETKEY',
                    'size' => 100,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->module->l('Redirect URI'),
                    'desc' => $this->module->l('Non editable. Just for reference.'),
                    'name' => 'CENTRY_REDIRECT_URI',
                    'size' => 100,
                    'required' => false
                ),
                array(
                    'type' => 'text',
                    'label' => $this->module->l('Notification threads'),
                    'desc' => $this->module->l('Number of threads for process notifications from Centry.'),
                    'name' => 'CENTRY_MAX_THREADS',
                    'size' => 2,
                    'required' => true
                )
            ),
            'submit' => array('title' => $this->module->l('Save'), 'class' => 'button')
        );
    }

    private function generateGeneralMasterForm() {
        return array(
            'legend' => array(
                'title' => $this->module->l('General Master Settings')
            ),
            'input' => array(
                array(
                    'type' => 'radio',
                    'label' => $this->module->l('Category source'),
                    'name' => 'CENTRY_CATEGORY_SOURCE',
                    'required' => true,
                    'class' => 't',
                    'is_bool' => false,
                    'values' => array(
                        array(
                            'id' => \Centry_ps::CATEGORY_SOURCE_PRIMARY,
                            'value' => \Centry_ps::CATEGORY_SOURCE_PRIMARY,
                            'label' => $this->module->l('Primary category')
                        ),
                        array(
                            'id' => \Centry_ps::CATEGORY_SOURCE_LOWER_SECONDARY,
                            'value' => \Centry_ps::CATEGORY_SOURCE_LOWER_SECONDARY,
                            'label' => $this->module->l('Lower level secondary category')
                        )
                    ),
                ),
            ),
            'submit' => array('title' => $this->module->l('Save'), 'class' => 'button')
        );
    }

    private function generateGeneralSlaveForm() {
        $option_values = array(
            'query' => array(
                array(
                    'id_option' => "description_attr",
                    'name' => $this->module->l('Description')
                ),
                array(
                    'id_option' => "shortDescription_attr",
                    'name' => $this->module->l('Main characteristics')
                ),
                array(
                    'id_option' => "warranty_attr",
                    'name' => $this->module->l('Warranty')
                ),
                array(
                    'id_option' => "season_attr",
                    'name' => $this->module->l('Season')
                ),
                array(
                    'id_option' => "year_attr",
                    'name' => $this->module->l('Year')
                )
            ),
            'id' => 'id_option',
            'name' => 'name'
        );

        return array(
            'legend' => array(
                'title' => $this->module->l('General Slave Settings')
            ),
            'input' => array(
                array(
                    'type' => 'checkbox',
                    'label' => $this->module->l('Select description\'s attributes.'),
                    'name' => 'CENTRY_DESCRIPTION_ATTRIBUTES',
                    'desc' => $this->module->l('Select the attributes that will be appear on the product\'s description.'),
                    'values' => $option_values,
                ),
                array(
                    'type' => 'checkbox',
                    'label' => $this->module->l('Select short description\'s attributes.'),
                    'name' => 'CENTRY_SHORT_DESCRIPTION_ATTRIBUTES',
                    'desc' => $this->module->l('Select the attributes that will be appear on the product\'s short description.'),
                    'values' => $option_values,
                )
            ),
            'submit' => array('title' => $this->module->l('Save'), 'class' => 'button')
        );
    }

    private function generateSynchronizeFieldForm() {
        $options = array();
        $options[] = array(
            'category_id_prestashop' => -1, // The value of the 'value' attribute of the <option> tag.
            'category_name' => $this->module->l("Nothing to do")
        );
        $options[] = array(
            'category_id_prestashop' => -2, // The value of the 'value' attribute of the <option> tag.
            'category_name' => $this->module->l("Persist Centry Relationship")
        );


        foreach (\CategoryCore::getAllCategoriesName() as $category) {
            if (!($category['id_category'] == (int) \Configuration::get("PS_ROOT_CATEGORY"))) {
                $options[] = array(
                    'category_id_prestashop' => $category['id_category'], // The value of the 'value' attribute of the <option> tag.
                    'category_name' => $category['name']      // The value of the text content of the  <option> tag.
                );
            }
        }
        $priceOfferOptions=array(
          array(
              'price_offer_option' => 0, // The value of the 'value' attribute of the <option> tag.
              'price_offer_name' => $this->module->l("Don't synchronizate Price offer")
          ),
          array(
              'price_offer_option' => 1, // The value of the 'value' attribute of the <option> tag.
              'price_offer_name' => $this->module->l("Overwrite Price")
          ),
          array(
              'price_offer_option' => -1, // The value of the 'value' attribute of the <option> tag.
              'price_offer_name' => $this->module->l("Calculate difference ")
          ),
          array(
              'price_offer_option' => 2, // The value of the 'value' attribute of the <option> tag.
              'price_offer_name' => $this->module->l("Calculate percentage ")
          ),
        );
        return array(
            'legend' => array(
                'title' => $this->module->l('Fields to synchronizate')
            ),
            'input' => array(
                array(
                    'type' => 'checkbox',
                    'label' => $this->module->l('Fields to synchronizate selection.'),
                    'name' => 'CENTRY_SYNCHRONIZATION_FIELDS',
                    'desc' => $this->module->l('Select the fields that you want to synchronizate with Centry.'),
                    'values' => array(
                        'query' => array(
                            array(
                                'id_option' => "name",
                                'name' => $this->module->l('Products\'s name')
                            ),
                            array(
                                'id_option' => "description",
                                'name' => $this->module->l('Products\'s description')
                            ),
                            array(
                                'id_option' => "shortDescription",
                                'name' => $this->module->l('Products\'s main characteristics')
                            ),
                            array(
                                'id_option' => "price_compare",
                                'name' => $this->module->l('Prices')
                            ),
                            // array(
                            //     'id_option' => "price",
                            //     'name' => $this->module->l('Offer\'s price')
                            // ),
                            array(
                                'id_option' => "condition",
                                'name' => $this->module->l('Condition')
                            ),
                            array(
                                'id_option' => "package_dimensions",
                                'name' => $this->module->l('Package\'s dimensions')
                            ),
                            array(
                                'id_option' => "package_weight",
                                'name' => $this->module->l('Package\'s weight')
                            ),
                            array(
                                'id_option' => "warranty",
                                'name' => $this->module->l('Warranty')
                            ),
                            array(
                                'id_option' => "barcode",
                                'name' => $this->module->l('Barcode')
                            ),
                            array(
                                'id_option' => "sku",
                                'name' => $this->module->l('SKU')
                            ),
                            array(
                                'id_option' => "images",
                                'name' => $this->module->l('Images')
                            )
                        ),
                        'id' => 'id_option',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'select',
                    'multiple' => false,
                    'label' => $this->module->l('Category\'s behavior.'),
                    'name' => 'CENTRY_CATEGORYS_BEHAVIOR',
                    'desc' => $this->module->l('Select what to do with categories.'),
                    'options' => array(
                        'query' => $options,
                        'id' => 'category_id_prestashop',
                        'name' => 'category_name'
                    )
                ),
                array(
                    'type' => 'select',
                    'multiple' => false,
                    'label' => $this->module->l('Price Offer\'s behavior.'),
                    'name' => 'CENTRY_PRICE_OFFER_BEHAVIOR',
                    'desc' => $this->module->l('Select what to do with Price offer.'),
                    'options' => array(
                        'query' => $priceOfferOptions,
                        'id' => 'price_offer_option',
                        'name' => 'price_offer_name'

                    )
                )
            ),
            'submit' => array('title' => $this->module->l('Save'), 'class' => 'button')
        );
    }

    private function generateCategoryTranslationForm() {
        $form = array(
            'legend' => array(
                'title' => $this->module->l('Category Translation')
            ),
            'input' => array(),
            'submit' => array('title' => $this->module->l('Save'), 'class' => 'button')
        );

        $options = array(
            array('id_centry' => null, 'name' => $this->module->l('--- Select a category ---'))
        );

        foreach (\Category::getNestedCategories(null, $this->context->language->id,false) as $category) {
            $this->addInputCategoryToForm($form, $category, $options);
        }

        return $form;
    }

    private function generateManufacturerTranslationForm() {
        $form = array(
            'legend' => array(
                'title' => $this->module->l('Manufacturer Translation')
            ),
            'input' => array(),
            'submit' => array('title' => $this->module->l('Save'), 'class' => 'button')
        );

        $options = array(
            array('manufacturer_id_centry' => null, 'manufacturer_name' => $this->module->l('--- Select a manufacturer ---'))
        );

        foreach (\Manufacturer::getManufacturers() as $manufacturer) {
            $form['input'][] = array(
                'type' => 'select', // This is a <select> tag.
                'label' => $manufacturer['name'], // The <label> for this <select> tag.
                'name' => "manufacturers[{$manufacturer['id_manufacturer']}]", // The content of the 'id' attribute of the <select> tag.
                'class' => 'fixed-width-xxl manufacturer_select2',
                'required' => false, // If set to true, this option must be set.
                'options' => array(
                    'query' => $options, // $options contains the data itself.
                    'id' => 'manufacturer_id_centry', // The value of the 'id' key must be the same as the key for 'value' attribute of the <option> tag in each $options sub-array.
                    'name' => 'manufacturer_name'     // The value of the 'name' key must be the same as the key for the text content of the <option> tag in each $options sub-array.
                )
            );
        }

        return $form;
    }

    private function generateFeaturesForm() {
        $form = array(
            'legend' => array(
                'title' => $this->module->l('Key Features')
            ),
            'input' => array(),
            'submit' => array('title' => $this->module->l('Save'), 'class' => 'button')
        );

        $options = array(
            array('feature_id' => null, 'feature_name' => $this->module->l('--- Select a feature ---'))
        );
        foreach (\Feature::getFeatures($this->context->language->id) as $feature) {
            $options[] = array(
                'feature_id' => $feature["id_feature"], // The value of the 'value' attribute of the <option> tag.
                'feature_name' => $feature["name"] // The value of the text content of the  <option> tag.
            );
        }
        $key_features = array(
            "CENTRY_FEATURE_SEASON_YEAR" => $this->module->l('Season Year'),
            "CENTRY_FEATURE_SEASON" => $this->module->l('Season'),
            "CENTRY_FEATURE_GENDER" => $this->module->l('Gender'),
        );

        foreach ($key_features as $key => $name) {
            $form['input'][] = array(
                'type' => 'select', // This is a <select> tag.
                'label' => $name, // The <label> for this <select> tag.
                'name' => "$key", // The content of the 'id' attribute of the <select> tag.
                'class' => 'fixed-width-xxl select2',
                'required' => false, // If set to true, this option must be set.
                'options' => array(
                    'query' => $options, // $options contains the data itself.
                    'id' => 'feature_id', // The value of the 'id' key must be the same as the key for 'value' attribute of the <option> tag in each $options sub-array.
                    'name' => 'feature_name'     // The value of the 'name' key must be the same as the key for the text content of the <option> tag in each $options sub-array.
                )
            );
        }

        return $form;
    }

    private function generateColorsForm() {
        $form = array(
            'legend' => array(
                'title' => $this->module->l('Colors Translation')
            ),
            'input' => array(),
            'submit' => array('title' => $this->module->l('Save'), 'class' => 'button')
        );

        $options = array(
            array('color_group_id' => null, 'color_group_name' => $this->module->l('--- Select a color attribute group ---'))
        );
        foreach (\AttributeGroup::getAttributesGroups((int) (\Configuration::get('PS_LANG_DEFAULT'))) as $color_group) {
            $options[] = array(
                'color_group_id' => $color_group["id_attribute_group"],
                'color_group_name' => $color_group["name"]
            );
        }

        $form['input'][] = array(
            'type' => 'select', // This is a <select> tag.
            'label' => $this->module->l('Colors Attribute Group'), // The <label> for this <select> tag.
            'name' => "color_group", // The content of the 'id' attribute of the <select> tag.
            'class' => 'fixed-width-xxl',
            'required' => false, // If set to true, this option must be set.
            'options' => array(
                'query' => $options, // $options contains the data itself.
                'id' => 'color_group_id', // The value of the 'id' key must be the same as the key for 'value' attribute of the <option> tag in each $options sub-array.
                'name' => 'color_group_name'     // The value of the 'name' key must be the same as the key for the text content of the <option> tag in each $options sub-array.
            )
        );

        if (($group = \AttributeGroup::findByFieldCentry("color"))) {
            $options = array(
                array('color_id_centry' => null, 'color_name' => $this->module->l('--- Select a color ---'))
            );
            foreach ((new \CentrySDK\Colors())->all() as $color) {
                $options[] = array(
                    'color_id_centry' => $color->_id, // The value of the 'value' attribute of the <option> tag.
                    'color_name' => $color->name      // The value of the text content of the  <option> tag.
                );
            }
            uasort($options, array($this, 'sortCentryColors'));

            foreach (\AttributeGroup::getAttributes((int) (\Configuration::get('PS_LANG_DEFAULT')), $group->id) as $color) {
                $form['input'][] = array(
                    'type' => 'select', // This is a <select> tag.
                    'label' => $color['name'], // The <label> for this <select> tag.
                    'name' => "color[{$color['id_attribute']}]", // The content of the 'id' attribute of the <select> tag.
                    'class' => 'fixed-width-xxl',
                    'required' => false, // If set to true, this option must be set.
                    'options' => array(
                        'query' => $options, // $options contains the data itself.
                        'id' => 'color_id_centry', // The value of the 'id' key must be the same as the key for 'value' attribute of the <option> tag in each $options sub-array.
                        'name' => 'color_name'     // The value of the 'name' key must be the same as the key for the text content of the <option> tag in each $options sub-array.
                    )
                );
            }
        } else {

        }

        return $form;
    }

    private function sortCentryColors($a, $b) {
        if ($a['color_name'] == $b['color_name']) {
            return 0;
        }
        return ($a['color_name'] < $b['color_name']) ? -1 : 1;
    }

    private function generateImportCentryColorsForm() {
        $form = array(
            'legend' => array(
                'title' => $this->module->l('Colors import from Centry')
            ),
            'input' => array(),
            'submit' => array('title' => $this->module->l('Save'), 'class' => 'button')
        );

        $options = array();
        foreach ((new \CentrySDK\Colors())->all() as $color) {
            $options[] = array(
                'color_id_centry' => $color->_id, // The value of the 'value' attribute of the <option> tag.
                'color_name' => $color->name      // The value of the text content of the  <option> tag.
            );
        }

        $form['input'][] = array(
            'type' => 'select',
            'multiple' => true,
            'label' => $this->module->l('Colors'), // The <label> for this <input> tag.
            'desc' => $this->module->l('Choose colors to import.'), // A help text, displayed right next to the <input> tag.
            'name' => 'colors' . (_PS_VERSION_ < '1.6.1.2' ? '[]' : ''), // The content of the 'id' attribute of the <input> tag.
            'options' => array(
                'query' => $options, // $options contains the data itself.
                'id' => 'color_id_centry', // The value of the 'id' key must be the same as the key
                // for the 'value' attribute of the <option> tag in each $options sub-array.
                'name' => 'color_name', // The value of the 'name' key must be the same as the key
            )
        );

        return $form;
    }

    private function generateSizesForm() {
        $form = array(
            'legend' => array(
                'title' => $this->module->l('Sizes Translation')
            ),
            'input' => array(),
            'submit' => array('title' => $this->module->l('Save'), 'class' => 'button')
        );

        $options = array(
            array('size_group_id' => null, 'size_group_name' => $this->module->l('--- Select a size attribute group ---'))
        );
        foreach (\AttributeGroup::getAttributesGroups((int) (\Configuration::get('PS_LANG_DEFAULT'))) as $color_group) {
            $options[] = array(
                'size_group_id' => $color_group["id_attribute_group"],
                'size_group_name' => $color_group["name"]
            );
        }

        $form['input'][] = array(
            'type' => 'select', // This is a <select> tag.
            'label' => $this->module->l('Sizes Attribute Group'), // The <label> for this <select> tag.
            'name' => "size_group", // The content of the 'id' attribute of the <select> tag.
            'class' => 'fixed-width-xxl',
            'required' => false, // If set to true, this option must be set.
            'options' => array(
                'query' => $options, // $options contains the data itself.
                'id' => 'size_group_id', // The value of the 'id' key must be the same as the key for 'value' attribute of the <option> tag in each $options sub-array.
                'name' => 'size_group_name'     // The value of the 'name' key must be the same as the key for the text content of the <option> tag in each $options sub-array.
            )
        );

        if (($group = \AttributeGroup::findByFieldCentry("size"))) {
            $options = array(
                array('size_id_centry' => null, 'size_name' => $this->module->l('--- Select a size ---'))
            );
            foreach ((new \CentrySDK\Sizes())->all() as $size) {
                $options[] = array(
                    'size_id_centry' => $size->_id, // The value of the 'value' attribute of the <option> tag.
                    'size_name' => $size->name      // The value of the text content of the  <option> tag.
                );
            }

            foreach (\AttributeGroup::getAttributes((int) (\Configuration::get('PS_LANG_DEFAULT')), $group->id) as $size) {
                $form['input'][] = array(
                    'type' => 'select', // This is a <select> tag.
                    'label' => $size['name'], // The <label> for this <select> tag.
                    'name' => "size[{$size['id_attribute']}]", // The content of the 'id' attribute of the <select> tag.
                    'class' => 'fixed-width-xxl',
                    'required' => false, // If set to true, this option must be set.
                    'options' => array(
                        'query' => $options, // $options contains the data itself.
                        'id' => 'size_id_centry', // The value of the 'id' key must be the same as the key for 'value' attribute of the <option> tag in each $options sub-array.
                        'name' => 'size_name'     // The value of the 'name' key must be the same as the key for the text content of the <option> tag in each $options sub-array.
                    )
                );
            }
        } else {

        }

        return $form;
    }

    private function generateImportCentrySizesForm() {
        $form = array(
            'legend' => array(
                'title' => $this->module->l('Sizes import from Centry')
            ),
            'input' => array(),
            'submit' => array('title' => $this->module->l('Save'), 'class' => 'button')
        );

        $options = array();
        foreach ((new \CentrySDK\Sizes())->all() as $size) {
            $options[] = array(
                'size_id_centry' => $size->_id, // The value of the 'value' attribute of the <option> tag.
                'size_name' => $size->name      // The value of the text content of the  <option> tag.
            );
        }

        $form['input'][] = array(
            'type' => 'select',
            'multiple' => true,
            'label' => $this->module->l('Sizes'), // The <label> for this <input> tag.
            'desc' => $this->module->l('Choose sizes to import.'), // A help text, displayed right next to the <input> tag.
            'name' => 'sizes' . (_PS_VERSION_ < '1.6.1.2' ? '[]' : ''), // The content of the 'id' attribute of the <input> tag.
            'options' => array(
                'query' => $options, // $options contains the data itself.
                'id' => 'size_id_centry', // The value of the 'id' key must be the same as the key
                // for the 'value' attribute of the <option> tag in each $options sub-array.
                'name' => 'size_name', // The value of the 'name' key must be the same as the key
            )
        );

        return $form;
    }

    private function generateOrderStateForm() {
        $form = array(
            'legend' => array(
                'title' => $this->module->l('Order states')
            ),
            'input' => array(),
            'submit' => array('title' => $this->module->l('Save'), 'class' => 'button')
        );

        $options = array(
            array('order_state_id' => null, 'order_state_name' => $this->module->l('--- Select an Order State ---'))
        );
        foreach (\OrderState::getOrderStates($this->context->language->id) as $order_state) {
            $options[] = array(
                'order_state_id' => $order_state["id_order_state"], // The value of the 'value' attribute of the <option> tag.
                'order_state_name' => "{$order_state["name"]} ({$order_state["template"]})" // The value of the text content of the  <option> tag.
            );
        }
        $order_states = array(
            "CENTRY_ORDERSTATE_PENDING" => $this->module->l('Pending'),
            "CENTRY_ORDERSTATE_SHIPPED" => $this->module->l('Shipped'),
            "CENTRY_ORDERSTATE_RECEIVED" => $this->module->l('Received'),
                //"CENTRY_ORDERSTATE_CANCELLED" => $this->module->l('Cancelled'),
        );

        foreach ($order_states as $key => $name) {
            $form['input'][] = array(
                'type' => 'select', // This is a <select> tag.
                'label' => $name, // The <label> for this <select> tag.
                'name' => "$key", // The content of the 'id' attribute of the <select> tag.
                'class' => 'fixed-width-xxl select2',
                'required' => false, // If set to true, this option must be set.
                'options' => array(
                    'query' => $options, // $options contains the data itself.
                    'id' => 'order_state_id', // The value of the 'id' key must be the same as the key for 'value' attribute of the <option> tag in each $options sub-array.
                    'name' => 'order_state_name'     // The value of the 'name' key must be the same as the key for the text content of the <option> tag in each $options sub-array.
                )
            );
        }

        return $form;
    }

    private function generateSyncForm() {
        $this->context->controller->addCSS(($this->_path) . 'views/css/select2.min.css');
        $this->context->controller->addCSS(($this->_path) . 'views/css/centry_ps.css');
        $this->context->controller->addJS(($this->_path) . 'views/js/jquery.ui.progressbar.min.js');
        $this->context->controller->addJS(($this->_path) . 'views/js/select2.min.js');
        $this->context->controller->addJS(($this->_path) . 'views/js/centry_ps.js');

        $form = array(
            'legend' => array(
                'title' => $this->module->l('Massive synchronization')
            ),
            'input' => array(
                array('type' => 'hidden', 'name' => 'CENTRY_SYNC_PRODUCTS_IDS'),
                array('type' => 'hidden', 'name' => 'CENTRY_AJAX_URL'),
                array('type' => 'hidden', 'name' => 'CENTRY_CATEGORIES'),
                array('type' => 'hidden', 'name' => 'CENTRY_MANUFACTURER'),
                array('type' => 'hidden', 'name' => 'CENTRY_CATEGORIES_SELECTED'),
                array('type' => 'hidden', 'name' => 'CENTRY_MANUFACTURER_SELECTED')

            ),
            'submit' => array('title' => $this->module->l('Sync'), 'class' => 'button centry_sync_massive')
        );

        return $form;
    }

    private function generateOrdersSyncForm() {
        $this->context->controller->addCSS(($this->_path) . 'views/css/select2.min.css');
        $this->context->controller->addCSS(($this->_path) . 'views/css/centry_ps.css');
        $this->context->controller->addJS(($this->_path) . 'views/js/jquery.ui.progressbar.min.js');
        $this->context->controller->addJS(($this->_path) . 'views/js/select2.min.js');
        $this->context->controller->addJS(($this->_path) . 'views/js/centry_ps.js');

        $form = array(
            'legend' => array(
                'title' => $this->module->l('Orders synchronization')
            ),
            'input' => array(
                array('type' => 'hidden', 'name' => 'CENTRY_SYNC_ORDERS_IDS'),
                array('type' => 'hidden', 'name' => 'CENTRY_AJAX_URL'),

            ),
            'submit' => array('title' => $this->module->l('Sync'), 'class' => 'button centry_orders_sync_massive')
        );

        return $form;
    }

    private function generateCheckStatusForm() {
        return array(
            'legend' => array(
                'title' => $this->module->l('Check module status')
            ),
            'input' => array(
                array(
                    'type' => 'checkbox',
                    'label' => $this->module->l('Fields to try to repair.'),
                    'desc' => $this->module->l('ADVERTENCIA. Es posible intentar reparar campos que están bien, pero no se recomienda hacerlo porque se eliminarán todos los datos que tiene esa tabla.'),
                    //'desc' => $this->module->l('Las opciones preseleccionadas son las que presentan problemas. Es posible intentar reparar campos que están bien, pero no se recomienda hacerlo.'),
                    'name' => 'CENTRY_FIELDS_TO_REPAIR',
                    'values' => array(
                        'query' => array(
                            array(
                                'id_option' => 'attribute_centry',
                                'name' => $this->module->l('Attributes Table')
                            ),
                            array(
                                'id_option' => 'attribute_group_centry',
                                'name' => $this->module->l('Group of attributes Table')
                            ),
                            array(
                                'id_option' => 'category_centry',
                                'name' => $this->module->l('Categories Table')
                            ),
                            array(
                                'id_option' => 'combination_centry',
                                'name' => $this->module->l('Combinations Table')
                            ),
                            array(
                                'id_option' => 'image_centry',
                                'name' => $this->module->l('Images Table')
                            ),
                            array(
                                'id_option' => 'manufacturer_centry',
                                'name' => $this->module->l('Brands Table')
                            ),
                            array(
                                'id_option' => 'order_centry',
                                'name' => $this->module->l('Orders Table')
                            ),
                            array(
                                'id_option' => 'product_centry',
                                'name' => $this->module->l('Products Table')
                            ),
                        ),
                        'id' => 'id_option',
                        'name' => 'name'
                    ),
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->module->l('Detailed module status'),
                    'desc' => $this->module->l('Non editable. Just for reference.'),
                    'name' => 'CENTRY_MODULE_STATUS',
                    'required' => false
                )
            ),
            'submit' => array('title' => $this->module->l('Repair'), 'class' => 'button')
        );
    }

    private function addInputCategoryToForm(&$form, $category, $options, $ruta = "") {
        $form['input'][] = array(
            'type' => 'select', // This is a <select> tag.
            'label' => $ruta . $category['name'], // The <label> for this <select> tag.
            'name' => "categories[{$category['id_category']}]", // The content of the 'id' attribute of the <select> tag.
            'class' => 'fixed-width-xxl category_select2',
            'required' => false, // If set to true, this option must be set.
            'options' => array(
                'query' => $options, // $options contains the data itself.
                'id' => 'id_centry', // The value of the 'id' key must be the same as the key for 'value' attribute of the <option> tag in each $options sub-array.
                'name' => 'name'     // The value of the 'name' key must be the same as the key for the text content of the <option> tag in each $options sub-array.
            )
        );

        if (isset($category['children'])) {
            foreach ($category['children'] as $child) {
                $this->addInputCategoryToForm($form, $child, $options, $ruta . $category['name'] . " / ");
            }
        }
    }

}
