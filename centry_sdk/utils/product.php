<?php

require_once 'abstractresource.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/model/Product.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/Brands.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/Categories.php';
include_once _PS_MODULE_DIR_ . 'centry_ps/include/utils.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/classes/Log.php';

/**
 * La clase Centry_psProduct permite crear o actualizar productos que centry notifica
 *
 *
 * @author Paulo Sandoval S.
 * @see Product
 */
class Centry_psProduct extends Centry_psAbstractResource {

    /**
     *
     * @param type $params parámetros que entrega el JSON
     * @return boolean output de la función
     */
    public function save($product) {
        $params = json_decode(json_encode($product), true);
        $this->cleanUnconnectedIds();
        if (Product::findProductByIdCentry($params["_id"])) {
            $this->update($params);
        } else {
            $this->create($params);
        }
        return true;
    }

    /**
     *
     * @param type $params parámetros que entrega el JSON
     * @return boolean output de la función
     */
    protected function create($params) {
        if (Product::findProductByIdCentry($params["_id"])) {
            return false;
        }
        try {
            $product = new Product();
            $this->basicProductInfo($product, $params, true);
            $product_id = $product->id;
            if (empty($params["variants"])) {
                StockAvailable::setQuantity($product_id, null, intval($params["quantity"]));
            }
            if (!empty($params['variants'])) {
                $this->create_combinations($product_id, $params['variants'], sizeof($params['variants']) == 1);
            }
            if ($params["cover_jpg_url"]) {
                $this->create_cover($product_id, $params);
            }
            if (!empty($params['assets'])) {
                $this->create_assets($product_id, $params['assets']);
            }
            //error_log(print_r($product,true));
            //$product->save();
            return true;
        } catch (Exception $ex) {
            // Log::d(print_r($params,true));
            error_log($ex->getMessage() . "\n" . $ex->getTraceAsString());
            Log::d("Fallo al crear producto " . $params["_id"], $ex->getMessage() . "\n" . $ex->getTraceAsString());
            $product->delete();
            return false;
        }
    }

    /**
     *
     * @param type $product_id
     * @param type $params
     */
    protected function create_cover($product_id, $params) {
        ini_set('max_execution_time', 300);

        // Log::d( "En create_cover", print_r($params,true) );
        // Log::d("-------------------------Inicio de Descarga imagen cover--------------------------------",$params["cover_jpg_url"]);
        $tmpfile = $this->downloadImage($params["cover_jpg_url"]);
        // Log::d("-------------------------Término de Descarga imagen cover--------------------------------", $tmpfile);
        if (isset($tmpfile)) {
            $image = new Image();
            $image->id_centry = $params["_id"];
            $image->id_product = $product_id;
            $image->position = 1;
            $image->cover = true;
            $image->content_type = $params["cover_content_type"];
            $image->file_name = $params["cover_file_name"];
            $image->file_size = $params["cover_file_size"];
            $image->created_at = $params["created_at"];
            $image->updated_at = $params["cover_updated_at"];
            $image->fingerprint = $params["cover_fingerprint"];
            $image->url = $params["cover_jpg_url"];
            $image->legend = "";

            // Log::d("antes de guardar cover", print_r($image,true));
            Log::d($image->save() ? "Yes" : "no" ) ;
            // Log::d("despues de guardar cover", print_r($image,true));
            $this->copyImg($product_id, $image->url, $image->id, 'products', true, $tmpfile);
            // Log::d("despues de copyImg", print_r($image,true));
            // Log::d("Reabierta", print_r(new Image($image->id),true));
            if (!(($image->validateFields(false, true)) === true && ($image->validateFieldsLang(false, true)) === true)) {
                Log::d("Bai eliminado cover malo");
                $image->delete();
            }
        } else {
            Log::d("Fallo en descarga de Imagen de cover : " . $params['_id']);
        }
    }

    /**
     * @author Nicolas Orellana
     * @param string $type
     * @param type $params
     * @param bool $params // true = create , false = update
     * @return text
     */
    private function descriptionText($type, $params, $action) {
        $textresult = "";
        if (\Configuration::get($type . "_ATTRIBUTES_description_attr") == "on") {
            $textresult = $textresult . " " . $params["description"];
        }
        if (\Configuration::get($type . "_ATTRIBUTES_shortDescription_attr") == "on") {
            $textresult = $textresult . " " . $params["shortdescription"];
        }
        if (\Configuration::get($type . "_ATTRIBUTES_season_attr") == "on") {
            $textresult = $textresult . '<p> Season: ' . $params["season"] . '</p>';
        }
        if (\Configuration::get($type . "_ATTRIBUTES_year_attr") == "on") {
            $textresult = $textresult . '<p> Year: ' . $params["seasonyear"] . '</p>';
        }

        if ($action or \Configuration::get("CENTRY_SYNCHRONIZATION_FIELDS_warranty") == "on") {
            if (\Configuration::get($type . "_ATTRIBUTES_warranty_attr") == "on") {
                $textresult = $textresult . '<p> Warranty:' . $params["warranty"] . '</p>';
            }
        }
        return $textresult;
    }

    /**
     *
     * @param type $product
     * @param type $params
     * @param bool $action // true = create , false = update
     * @return type
     */
    protected function basicProductInfo($product, $params, $action) {
        //error_log(print_r($params,true));
        $shortdescription = $this->descriptionText("CENTRY_SHORT_DESCRIPTION", $params, $action);
        $description = $this->descriptionText("CENTRY_DESCRIPTION", $params, $action);
        $product->id_centry = $params["_id"];
        $taxRate = 1 + ((float) $product->getTaxesRate()) / 100;
        $product->centry_category = $params["category_id"];
        if ($action or \Configuration::get("CENTRY_SYNCHRONIZATION_FIELDS_name") == "on") {
            $product->name = array((int) Configuration::get('PS_LANG_DEFAULT') => $params["name"]);
            $product->link_rewrite = array((int) Configuration::get('PS_LANG_DEFAULT') => generateLinkRewrite($params["name"]));
        }
        if ($action or ! ((int) \Configuration::get("CENTRY_CATEGORYS_BEHAVIOR") == -1)) {
            //$product = $this->associate_categories($product);
        }
        if ($action or \Configuration::get("CENTRY_SYNCHRONIZATION_FIELDS_condition")) {
            $product->condition = $params["condition"];
        }


        if ($action or \Configuration::get("CENTRY_SYNCHRONIZATION_FIELDS_description") == "on") {
            $product->description = array((int) (Configuration::get('PS_LANG_DEFAULT')) => $description);
        }
        if ($action or \Configuration::get("CENTRY_SYNCHRONIZATION_FIELDS_shortDescription") == "on") {
            $product->description_short = array((int) (Configuration::get('PS_LANG_DEFAULT')) => substr( $shortdescription,0,1090) );
        }
        if ($action or \Configuration::get("CENTRY_SYNCHRONIZATION_FIELDS_price_compare") == "on") {
            $product->price = (Configuration::get('PS_TAX')) ? Tools::ps_round($params["price_compare"] * (1 / $taxRate), 2) : $params["price_compare"];
        }

        if ($action or \Configuration::get("CENTRY_SYNCHRONIZATION_FIELDS_package_dimensions") == "on") {
            $product->height = $params["packageheight"];
            $product->depth = $params["packagelength"];
            $product->width = $params["packagewidth"];
        }
        if ($action or \Configuration::get("CENTRY_SYNCHRONIZATION_FIELDS_package_weight") == "on") {
            $product->weight = $params["packageweight"];
        }

        if ($action or \Configuration::get("CENTRY_SYNCHRONIZATION_FIELDS_barcode") == "on") {
            $product->upc = \Validate::isUpc($params["barcode"]) ? $params["barcode"] : null;
        }
        if ($action or \Configuration::get("CENTRY_SYNCHRONIZATION_FIELDS_sku") == "on") {
            $product->reference = $params["sku"];
        }

        $product->meta_description = substr( $params["seo_description"],0,254);
        $product->meta_title = $params["seo_title"];
        $product->show_price = 1;
        $product->available_for_order = 1;
        $product->available_now = "Disponible";
        // $product->manufacturer_name = "Manufacturador"; // Must change to Manufacturer->name or something
        $product->active = $params["status"];

        $manufacturer = $this->do_manufacturer($params);
        $product->id_manufacturer = $manufacturer->id;
        $product->manufacturer_name = $manufacturer->name; // Must change to Manufacturer->name or something
        //error_log(print_r($product,true));
        $bool = $product->save();

        if ($action or \Configuration::get("CENTRY_PRICE_OFFER_BEHAVIOR") != 0) {
            // Log::d("A actualizar precio de oferta", \Configuration::get("CENTRY_PRICE_OFFER_BEHAVIOR") );
            // $discountedprice = Tools::ps_round($params["price"] * (1 / $taxRate), 2);
            $reducedPrice = $params["price_compare"] - $params["price"];
            $percentagePrice = ($params["price_compare"] - $params["price"]) / $params["price_compare"];
            //error_log($percentagePrice);
            if ($params["price"]) {
                $from = $params["salestartdate"];
                $timestamp = strtotime($from);
                $from = date('Y-m-d H:i:s', $timestamp);
                $to = $params["saleenddate"];
                $timestamp = strtotime($to);
                $to = date('Y-m-d H:i:s', $timestamp);
                // error_log($from ." ".$to);
                // Log::d("a Portas de cambiar precio de oferta" );
                if (\Configuration::get("CENTRY_PRICE_OFFER_BEHAVIOR") == 1) {
                    $this->discounted_price($product, $discountedprice, $from, $to);
                } elseif (\Configuration::get("CENTRY_PRICE_OFFER_BEHAVIOR") == 2) {
                    $this->percentage_price($product, $percentagePrice, $from, $to);
                } else {
                    $this->reduced_price($product, $reducedPrice, $from, $to);
                }
            } else {
                $query = SpecificPriceCore::getByProductId($product->id);
                if ($query) {
                    $discount = new SpecificPrice($query[0]["id_specific_price"]);
                    //error_log("false ". print_r($discount,true));
                    $discount->delete();
                }
            }
        }



        return $product;
    }

    /**
     * @author Nicolas Orellana
     * @param \Product $product
     * @param type $reducedPrice
     */
    protected function reduced_price($product, $reducedPrice, $from, $to) {
        // Log::d("En 1");
        // Log::d("En Reduced_price $reducedPrice   $from   $to" );
        $query = SpecificPriceCore::getByProductId($product->id);
        // Log::d(" Lista de Specific rescatado ".print_r($query,true));
        if ($query) {
            $discount = new SpecificPrice($query[0]["id_specific_price"]);
            // Log::d(" Specific rescatado ".print_r($discount,true));
            $discount->reduction = $reducedPrice;
            $discount->reduction_type = "amount";
            $discount->price = -1;
            $discount->from = $from;
            $discount->to = $to;
            $discount->save();
        } else {
            $discount = new SpecificPrice();
            $discount->price = -1;
            $discount->id_product = $product->id;
            $discount->reduction_type = "amount";
            $discount->reduction_tax = 1;
            $discount->reduction = $reducedPrice;
            $discount->from_quantity = 1;
            $discount->from = $from;
            $discount->to = $to;
            $discount->id_product_attribute = 0;
            $discount->id_customer = 0;
            $discount->id_group = 0;
            $discount->id_country = 0;
            $discount->id_currency = 0;
            $discount->id_shop_group = 0;
            $discount->id_shop = 0;
            $discount->id_cart = 0;
            $discount->id_specific_price_rule = 0;
            $discount->save();
        }
    }

    /**
     * @param \Product $product
     * @param type $discountedprice
     */
    protected function discounted_price($product, $discountedprice, $from, $to) {
        // Log::d("En 2");
        // Log::d("En discounted_price" );
        $query = SpecificPriceCore::getByProductId($product->id);

        if ($query) {
            $discount = new SpecificPrice($query[0]["id_specific_price"]);
            $discount->price = $discountedprice;
            $discount->reduction = 0;
            $discount->reduction_type = "amount";
            $discount->from = $from;
            $discount->to = $to;
            $discount->save();
        } else {
            $discount = new SpecificPrice();
            $discount->price = $discountedprice;
            $discount->id_product = $product->id;
            $discount->reduction_type = "amount";
            $discount->reduction_tax = 1;
            $discount->reduction = 0;
            $discount->from_quantity = 1;
            $discount->from = $from;
            $discount->to = $to;
            $discount->id_product_attribute = 0;
            $discount->id_customer = 0;
            $discount->id_group = 0;
            $discount->id_country = 0;
            $discount->id_currency = 0;
            $discount->id_shop_group = 0;
            $discount->id_shop = 0;
            $discount->id_cart = 0;
            $discount->id_specific_price_rule = 0;
            $discount->save();
        }
    }

    /**
     * @author Nicolas Orellana
     * @param \Product $product
     * @param type $percentagePrice
     */
    protected function percentage_price($product, $percentagePrice, $from, $to) {
        // Log::d("En 3");
        // Log::d("En percentage_price" );
        $query = SpecificPriceCore::getByProductId($product->id);
        if ($query) {
            $discount = new SpecificPrice($query[0]["id_specific_price"]);
            $discount->price = -1;
            $discount->reduction = round($percentagePrice,5);
            $discount->reduction_type = "percentage";
            $discount->from = $from;
            $discount->to = $to;
            $discount->save();
        } else {
            $discount = new SpecificPrice();
            $discount->price = -1;
            $discount->id_product = $product->id;
            $discount->reduction_type = "percentage";
            $discount->reduction_tax = 1;
            $discount->reduction = round($percentagePrice,5);
            $discount->from_quantity = 1;
            $discount->from = $from;
            $discount->to = $to;
            $discount->id_product_attribute = 0;
            $discount->id_customer = 0;
            $discount->id_group = 0;
            $discount->id_country = 0;
            $discount->id_currency = 0;
            $discount->id_shop_group = 0;
            $discount->id_shop = 0;
            $discount->id_cart = 0;
            $discount->id_specific_price_rule = 0;
            $discount->save();
        }
    }

    /**
     *
     * @param type $params
     * @return \Manufacturer
     */
    protected function do_manufacturer($params) {
        if (!$params["brand_id"]) {
            return null;
        }
        $manufacturer = Manufacturer::findManufacturerByIdCentry($params["brand_id"]);
        if (!$manufacturer) {
            $manufacturer = new Manufacturer();
            $manufacturer->active = true;
            $manufacturer->id_centry = $params["brand_id"];
            $brandSDK = new CentrySDK\Brands();
            $brand = $brandSDK->findById($params["brand_id"]);
            $manufacturer->name = $brand->name;
            $manufacturer->save();
        }
        return $manufacturer;
    }

    /**
     *
     * @param \Product $product
     * @return \Product
     * @param bool $action // true = create , false = update
     */
    protected function associate_categories($product) {
        $category_id = (int) \Configuration::get("CENTRY_CATEGORYS_BEHAVIOR");
        if ($category_id == -1) {
            $category_id = \Configuration::get("PS_HOME_CATEGORY");
        } elseif ($category_id == -2) {
            if (($cat = Category::findCategoryByIdCentry($product->centry_category))) {
                $category_id = $cat->id;
            } else {
                $categorySDK = (new CentrySDK\Categories())->findById($product->centry_category);
                if (!(array) $categorySDK) {
                    $category_id = 2;
                } else {
                    $cat = $categorySDK->saveInPrestashop();
                    $category_id = $cat->id;
                }
            }
        } else {
            $category_id = (int) \Configuration::get("CENTRY_CATEGORYS_BEHAVIOR");
        }
        $product->id_category_default = $category_id;
        $cats = array_merge($product->getCategories(), array($category_id));
        $product->updateCategories($cats);
        return $product;
    }

    /**
     *
     * @param type $product_id
     * @param type $assets_array
     */
    protected function create_assets($product_id, $assets_array) {
        foreach ($assets_array as $value) {
            $this->create_asset($product_id, $value);
        }
    }

    /**
     * @author Nicolas Orellana
     * @param type $product_id
     * @param type $asset
     */
    protected function create_asset($product_id, $asset) {
        ini_set('max_execution_time', 300);
         Log::d(print_r($asset,true));
         Log::d("-------------------------Inicio de Descarga imagen--------------------------------",$asset["url_jpg"]);
        $tmpfile = $this->downloadImage($asset["url_jpg"]);
         Log::d("-------------------------Término de Descarga imagen--------------------------------", $tmpfile);
        if (isset($tmpfile)) {
            $image = new Image();
            $image->id_centry = $asset["_id"];
            $image->cover = false;
            $image->content_type = $asset["image_content_type"];
            $image->file_name = $asset["image_file_name"];
            $image->file_size = $asset["image_file_size"];
            $image->created_at = $asset["created_at"];
            $image->updated_at = $asset["updated_at"];
            $image->fingerprint = $asset["image_fingerprint"];
            $image->url = $asset["url_jpg"];
            $image->id_product = $product_id;
            $image->legend = "";
            $image->position = Image::getHighestPosition($product_id) + 1;
            Log::d("Imagen asset guardandose",print_r($image,true));
            $image->save();
            $this->copyImg($product_id, $image->url, $image->id, 'products', true, $tmpfile);
            if (!($image->validateFields(false, true) === true && $image->validateFieldsLang(false, true) === true )) {
                Log::d("Bai eliminado");
                $image->delete();
            }
        } else {
            Log::d("Fallo en descarga de Imagen secundaria : " . $asset['_id']);
        }
    }

    protected function add_image($product_id, $url, $cover) {
        $image = new Image();
        $image->id_product = $product_id;
        $image->position = Image::getHighestPosition($product_id) + 1;
        $image->cover = $cover;
        $image->url = $url;
        if (($image->validateFields(false, true)) === true && ($image->validateFieldsLang(false, true)) === true && $image->save()) {
            if (!$this->copyImg($product_id, $url, $image->id, 'products', true)) {
                $image->delete();
            }
        }
        return $image;
    }

    /**
     *
     * @param type $product_id
     * @return array
     */
    protected function getVariants($product_id) {
        $product = new Product($product_id);
        $array_combinations = $product->getAttributeCombinations((int) Configuration::get('PS_LANG_DEFAULT'));
        $array_id_pa = array();
        $array_variants = array();

        foreach ($array_combinations as $key => $value) {
            $id_pa = $value["id_product_attribute"];
            $index = array_search($id_pa, $array_id_pa);
            if ($index === false) {
                array_push($array_id_pa, $id_pa);
            }
        }

        foreach ($array_id_pa as $key => $value) {
            $combi_actual = new Combination($value);
            $combination = array();
            $array_combo = array();
            $product_name = "";
            foreach ($array_combinations as $v) {
                if ($v["id_product_attribute"] == $value) {
                    array_push($array_combo, $v["attribute_name"]);
                    $combination["_id"] = $combi_actual;
                    $product = new Product(intval($v["id_product"]));
                    $combination["product_id"] = $product->id_centry;
                    $product_name = $product->reference;
                    $combination["quantity"] = $v["quantity"];
                    $combination["barcode"] = $v["upc"];
                    $combination["created_at"] = $combi_actual->created_at;
                    $combination["updated_at"] = $combi_actual->updated_at;
                    $combination["sku"] = $combi_actual->sku;
                }
            }
            $combination["description"] = implode(", ", $array_combo);
            array_push($array_variants, $combination);
        }
        return $array_variants;
    }

    /**
     *
     * @param type $product_id
     * @return type
     */
    protected function getOptions($product_id) {
        $product = new Product($product_id);
        $array_combinations = $product->getAttributeCombinations((int) Configuration::get('PS_LANG_DEFAULT'));
        $array_groups = array();
        foreach ($array_combinations as $value) {
            $nombre_group = $value["group_name"];
            $index = array_search($nombre_group, $array_groups);
            if ($index === false) {
                array_push($array_groups, $nombre_group);
            }
        }
        return implode(", ", $array_groups);
    }

    /**
     *
     * @param type $id_centry
     * @return type
     */
    protected function read($id_centry) {
        // error_log("Product read: $id_centry");
        $product = Product::findProductByIdCentry($id_centry);
        return $product == false ? false : \CentrySDK\Product::fromPrestashop($product)->toParametersArray();
    }

    /**
     *
     * @param type $product_id
     * @return type
     */
    protected function getPriceCompare($product_id) {
        $query = SpecificPriceCore::getByProductId($product_id);
        if ($query) {
            return $query[0]["price"];
        }
        return $query;
    }

    /**
     *
     * @param type $product
     */
    protected function deleteImages($product) {
        $images = $product->getImages((int) Configuration::get('PS_LANG_DEFAULT'));
        foreach ($images as $value) {
            $image = new Image($value["id_image"]);
            $image->delete();
        }
    }

    /**
     *
     * @param type $product
     * @param type $params
     * @return boolean
     */
    protected function updateImages($product, $params) {
        // Log::d(print_r($params,true));

        // Log::d("Entrando a updateImages  Fin resumen");
        $images = $product->getImages((int) Configuration::get('PS_LANG_DEFAULT'));
        $asstes_to_skip_creation = array();
        $crearCover = true;
        $countCovers =0;
        foreach ($images as $img) {
            $image = new Image($img["id_image"]);
            $procesada = false;
            // Log::d(print_r($img["id_image"],true) ."\t". print_r($params["_id"],true));
            // Log::d( _THEME_PROD_DIR_ . $image->getExistingImgPath() .".".$image->image_format , file_exists( _THEME_PROD_DIR_ . $image->getExistingImgPath() .".".$image->image_format) );
            // Log::d(print_r($image->fingerprint,true) ."\t". print_r($params["_id"],true));
            if ($image->cover && file_exists( \Tools::getShopDomainSsl(true) . __PS_BASE_URI__. _THEME_PROD_DIR_ . $image->getExistingImgPath() .".".$image->image_format)) {
                // Log::d("si cover");
                $procesada = true;
                if ($params["cover_fingerprint"] == $image->fingerprint) {
                    $crearCover = false;
                    // Log::d("creando Cover ?", print_r($image,true));

                    // $contador++;
                } else {
                    Log::d("Eliminé cover");
                    $image->delete();
                }
            } elseif (isset($params["assets"]) && is_array($params["assets"])) {
                $mantener = false;
                $procesada = true;
                // Log::d("Mantener? ". $image->id , $mantener  ? "Si" : "No");
                foreach ($params["assets"] as $asset) {
                    if ($asset["image_fingerprint"] == $image->fingerprint) {
                        $asstes_to_skip_creation[] = $image->fingerprint;
                        $mantener = true;
                        break;
                    }
                }
                // Log::d("Mantener? despues de foreach ". $image->id , $mantener  ? "Si" : "No");
                if (!$mantener) {
                    Log::d("Eliminé foto : " . $image->id_image);
                    $image->delete();
                }
            }
            if (!$procesada) {
                Log::d("Eliminé foto : " . $image->id_image);
                $image->delete();
            }
        }
        // Log::d(print_r($params["assets"],true));
        if ($crearCover) {
            // Log::d("creando cover -----------------------------------------------");
            $this->create_cover($product->id, $params);
            // Log::d("cover creado -------------------------------------------------");
        }
        foreach ($params["assets"] as $asset) {
            // Log::d("en For");
            if (!in_array($asset["image_fingerprint"], $asstes_to_skip_creation)) {
                // Log::d("creando asset");
                $this->create_asset($product->id, $asset);
                // Log::d("assets creado ");
            }
        }

        // Reordenar las imágenes.
        try {
            $images = $product->getImages((int) Configuration::get('PS_LANG_DEFAULT'));
            foreach ($images as $img) {
                $image = new Image($img["id_image"]);
                if ($params["_id"] == $image->id_centry || count($images) == 1) {
                    $image->position = 1;
                    $image->cover = 1;
                    Log::d("set cover");

                } else {
                    foreach ($params["assets"] as $index => $asset) {
                        if ($asset["image_fingerprint"] == $image->fingerprint) {
                            $image->position = 2 + $index;
                        }
                    }
                }
                $image->save();
            }
            $isCoverSet = 0 ;
            foreach ($images as $img) {
                $image = new Image($img["id_image"]);
                if($image->cover== 1 ){
                    $isCoverSet=1;
                }
            }
            if ($isCoverSet == 0){
                foreach ($images as $img) {
                    $image = new Image($img["id_image"]);
                    $image->cover = 1;
                    $image->save();
                    break;
                }
            }


        } catch (Exception $ex) {
            error_log($ex->getMessage() . "\n" . $ex->getTraceAsString());
            Log::d("Fallo al reordenar las imágenes " . $params["_id"], $ex->getMessage() . "\n" . $ex->getTraceAsString());
        }
    }

    /**
     *
     * @param type $product_id
     * @param type $options
     * @return boolean
     */
    protected function checkOptions($product_id, $options) {
        $stringOptions = $this->getOptions($product_id);

        $array_options = explode(",", $options);
        $trimmed_array = array_map('trim', $array_options);

        $array_options_get = explode(",", $stringOptions);
        $trimmed_array_get = array_map('trim', $array_options_get);

        $diff = array_diff($trimmed_array, $trimmed_array_get);

        if (!$diff) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * @param type $params
     * @return boolean
     */
    protected function update($params) {
        $product = Product::findProductByIdCentry($params["_id"]);
        if (!$product) {
            return false;
        }
        try {
            $this->basicProductInfo($product, $params, false);

            $product->save();
            if (!$params['variants']) {
                StockAvailable::setQuantity($product->id, null, intval($params["quantity"]));
            } else {
                $this->create_needed_attributes($params["variants"]);
                $variants = $this->separate_variants_to_update_delete_create($product, $params["variants"]);
                // Actualizar las viejas
                $this->update_combinations($product->id, $variants["to_update"]);
                // Eliminar las que ya no existen.
                $this->delete_combinations($variants["to_delete"]);
                // Crear las nuevas
                $this->create_combinations($product->id, array_values($variants["to_create"]), sizeof($params['variants']) == 1);
                StockAvailable::synchronize($product->id);
                $product->save();
            }
            if (\Configuration::get("CENTRY_SYNCHRONIZATION_FIELDS_images") == "on") {
                Log::d($product->id);
                // $this->deleteImages($product);
                // $this->create_cover($product->id, $params);
                // $this->create_assets($product->id, $params['assets']);
                $this->updateImages($product, $params);
            }
            $product->save();
            return true;
        } catch (Exception $ex) {
            error_log($ex->getMessage() . "\n" . $ex->getTraceAsString());
            Log::d("Fallo al actualizar Producto " . $params["_id"], $ex->getMessage() . "\n" . $ex->getTraceAsString());
            return false;
        }
    }

    /**
     * Del listado de variantes provenientes de Centry, este método las separa
     * por las combinaciones de Prestashop que tiene que actualizar, eliminar y
     * crear.
     * @param \Product $product
     * @param array $variants_array
     * @return array Arreglo con 3 sublistados:
     * <ul>
     * <li>
     * <b>to_update:</b> <code>array</code> de pares:
     * <ul>
     * <li><b>combination:</b> <code>\Combination</code> con la combinación a actualizar.</li>
     * <li><b>variant:</b> <code>array</code> con los datos de la variante de Centry.</li>
     * </ul>
     * </li>
     * <li><b>to_delete:</b> <code>array</code> de <code>\Combination</code>s a eliminar.</li>
     * <li><b>to_create:</b> <code>array</code> de <code>array</code>s con los datos de la variante de Centry por crear</li>
     * </ul>
     */
    private function separate_variants_to_update_delete_create($product, $variants_array) {
        $result = array("to_update" => array(), "to_delete" => array(), "to_create" => $variants_array);
        $array_combinations = $product->getAttributeCombinations((int) Configuration::get('PS_LANG_DEFAULT'));
        foreach ($array_combinations as $value) {
            $id_pa = $value["id_product_attribute"];
            $combination = new Combination($id_pa);
            foreach ($variants_array as $variant) {
                if ($combination->id_centry == $variant["_id"] && !isset($result["to_update"][$combination->id])) {
                    $result["to_update"][$combination->id] = array("combination" => $combination, "variant" => $variant);
                    unset($result["to_create"][array_search($variant, $result["to_create"])]);
                    break;
                }
            }
            if (!isset($result["to_update"][$combination->id]) && !isset($result["to_delete"][$combination->id])) {
                $result["to_delete"][$combination->id] = $combination;
            }
        }
        return $result;
    }

    /**
     * En base a las variantes de Centry, crea combinaciones para un procuto de
     * Prestashop.
     * @param int $product_id identificador del producto al cual crear combinaciones.
     * @param array $array_variants listado de variantes de Centry
     * @param bool $is_unique_variant indica si se tiene que considerar como producto con variante única.
     */
    private function create_combinations($product_id, $array_variants, $is_unique_variant) {
        ini_set('max_execution_time', 300);
        $producto = new Product($product_id);
        // Recolectar todos los atributos (y crear los atributos)
        if ($is_unique_variant) {
            $producto->id_centry_unique_variant = $array_variants[0]["_id"];
            $producto->save();
        }
        $this->create_needed_attributes($array_variants);
        // Crear las combinaciones para el producto (Luego asociarlos a los productos)
        foreach ($array_variants as $value) {
            $price = 0; // Hay un precio de Subida, y un precio del producto! (ojo ahí)
            $wholesale_price = 0;
            StockAvailableCore::setProductOutOfStock($producto->id);
            $combination_id = $producto->addCombinationEntity($wholesale_price, $price, 0, 0, 0, 0, null, null, null, null, null, null, null, 0, array(), null);
            $combination = new Combination($combination_id);
            $combination->id_centry = $value['_id'];
            $combination->upc = \Validate::isUpc($value["barcode"]) ? $value["barcode"] : null;
            $combination->created_at = $value["created_at"];
            $combination->updated_at = $value["updated_at"];
            $combination->sku = $value["sku"];
            $combination->reference = $value["sku"];
            $combination->setAttributes($this->find_attributes_by_centry_variant($value));
            $combination->save();
            StockAvailable::setQuantity($product_id, $combination_id, $value['quantity'], null);
            if ($value['quantity'] > 0 || $is_unique_variant) {
                $producto->deleteDefaultAttributes();
                $producto->setDefaultAttribute($combination->id); // Set default combination!?!?! Cualquiera que tenga stock mayor a 0
            }
        }
        StockAvailable::synchronize($producto->id);
        $producto->save();
    }

    /**
     * Actualiza las combinaciones de un producto.
     * @param int $product_id identificador del producto de Prestashop.
     * @param array $combinations_variants arreglo de pares:
     * <ul>
     * <li><b>combination:</b> <code>\Combination</code> con la combinación a actualizar.</li>
     * <li><b>variant:</b> <code>array</code> con los datos de la variante de Centry.</li>
     * </ul>
     */
    private function update_combinations($product_id, $combinations_variants) {
        $producto = new Product($product_id);
        foreach ($combinations_variants as $combination_variant) {
            $combination = $combination_variant["combination"];
            $variant = $combination_variant["variant"];
            $combination->upc = \Validate::isUpc($variant["barcode"]) ? $variant["barcode"] : null;
            $combination->created_at = $variant["created_at"];
            $combination->updated_at = $variant["updated_at"];
            $combination->sku = $variant["sku"];
            $combination->reference = $variant["sku"];
            $combination->setAttributes($this->find_attributes_by_centry_variant($variant));
            $combination->save();
            StockAvailable::setQuantity($product_id, $combination->id, $variant['quantity'], null);
            if ($combination_variant['quantity'] > 0 || count($combinations_variants) == 1) {
                $producto->deleteDefaultAttributes();
                $producto->setDefaultAttribute($combination->id); // Set default combination!?!?! Cualquiera que tenga stock mayor a 0
            }
        }
    }

    /**
     * Elimina todas las combinaciones del listado pasado como parámetro.
     * @param array $combinations
     */
    private function delete_combinations($combinations) {
        foreach ($combinations as $combination) {
            $combination->delete();
        }
    }

    /**
     * En base a un listado de variantes de Centry, evalua si tiene que crear en
     * prestahop los atributos y grupos para sus tallas y colores.
     * @param array $array_variants arreglo con variantes de Centry.
     */
    private function create_needed_attributes($array_variants) {
        foreach ($array_variants as $variant) {
            if ($variant['size'] && \Attribute::findByIdCentry($variant['size']) == false) {
                $this->create_size_attribute($variant['size']);
            }
            if ($variant['color'] && \Attribute::findByIdCentry($variant['color']) == false) {
                $this->create_color_attribute($variant['color']);
            }
        }
    }

    /**
     * Crea una talla y su grupo (si no existiera) en base a los datos de Centry
     * los cuales se buscan vía API en base a su identificador,.
     * @param string $centry_size_id identificador de la talla en Centry.
     */
    private function create_size_attribute($centry_size_id) {
        $size_centry = (new CentrySDK\Sizes())->findById($centry_size_id);
        $attribute = new Attribute();
        $attribute->name = array((int) Configuration::get('PS_LANG_DEFAULT') => $size_centry->name);
        $attribute->id_centry = $size_centry->_id;
        $group = \AttributeGroup::findByFieldCentry("size");
        if (!isset($group->field_centry)) {
            $group = new AttributeGroup();
            $group->name = array((int) Configuration::get('PS_LANG_DEFAULT') => "Talla");
            $group->field_centry = "size";
            $group->group_type = "select"; // Muy importante
            $group->public_name = array((int) Configuration::get('PS_LANG_DEFAULT') => "Talla");
            $group->save();
        }
        $attribute->id_attribute_group = $group->id;
        $attribute->save();
    }

    /**
     * Crea un color y su grupo (si no existiera) en base a los datos de Centry
     * los cuales se buscan vía API en base a su identificador,.
     * @param string $centry_color_id identificador de la talla en Centry.
     */
    private function create_color_attribute($centry_color_id) {
        $color_centry = (new CentrySDK\Colors())->findById($centry_color_id);
        $attribute = new Attribute();
        $attribute->color = $color_centry->hexadecimal;
        $attribute->name = array((int) Configuration::get('PS_LANG_DEFAULT') => $color_centry->name);
        $attribute->id_centry = $color_centry->_id;
        $group = \AttributeGroup::findByFieldCentry("color");
        if (!isset($group->field_centry)) {
            $group = new AttributeGroup();
            $group->name = array((int) Configuration::get('PS_LANG_DEFAULT') => "Color");
            $group->field_centry = "color";
            $group->is_color_group = 1;
            $group->group_type = "color"; // Muy importante
            $group->public_name = array((int) Configuration::get('PS_LANG_DEFAULT') => "Color");
            $group->save();
        }
        $attribute->id_attribute_group = $group->id;
        $attribute->save();
    }

    /**
     * En base a los identificadores de talla y color de una variante en Centry,
     * se buscan los respectivos atributos en Prestashop.
     * @param array $variant datos de una variante de Centry.
     * @return array de <code>\Attribute</code>
     * @see Centry_psProduct::create_combinations()
     * @see Centry_psProduct::update_combinations()
     */
    private function find_attributes_by_centry_variant($variant) {
        $array_attributes = array();
        if ($variant['color']) {
            $attr = \Attribute::findByIdCentry($variant['color']);
            $array_attributes["Color"] = $attr->id;
        }
        if ($variant['size']) {
            $attr = \Attribute::findByIdCentry($variant['size']);
            $array_attributes["Talla"] = $attr->id;
        }
        return $array_attributes;
    }

    public function delete($id_centry) {
        //error_log("Product delete: $id_centry");
        $product = Product::findProductByIdCentry($id_centry);
        if (!$product) {
            return false;
        }
        try {
            return $product->delete();
        } catch (Exception $ex) {
            error_log($ex->getMessage() . "\n" . $ex->getTraceAsString());
            Log::d("Fallo al eliminar producto " . $id_centry, $ex->getMessage() . "\n" . $ex->getTraceAsString());
            return false;
        }
    }

    protected function get_best_path($tgt_width, $tgt_height, $path_infos) {
        $path_infos = array_reverse($path_infos);
        $path = '';
        foreach ($path_infos as $path_info) {
            list($width, $height, $path) = $path_info;
            if ($width >= $tgt_width && $height >= $tgt_height) {
                return $path;
            }
        }
        return $path;
    }

    protected function downloadImage($url) {
        try {
            ini_set('max_execution_time', 1000);

            // Log::d($url, "descarganding" ) ;
            $url = urldecode(trim($url));
            $parced_url = parse_url($url);
            //  Log::d("1");

            if (isset($parced_url['path'])) {
                $uri = ltrim($parced_url['path'], '/');
                $parts = explode('/', $uri);
                foreach ($parts as &$part) {
                    $part = rawurlencode($part);
                }
                unset($part);
                $parced_url['path'] = '/' . implode('/', $parts);
            }
            // Log::d("2");

            if (isset($parced_url['query'])) {
                $query_parts = array();
                parse_str($parced_url['query'], $query_parts);
                $parced_url['query'] = http_build_query($query_parts);
            }
            // Log::d("3");

            if (!function_exists('http_build_url')) {
                require_once(_PS_TOOL_DIR_ . 'http_build_url/http_build_url.php');
            }
            // Log::d("4");

            $url = http_build_url('', $parced_url);
            $try = 0;
            $success = false;
            $counting_fingerprint = array();
            // Log::d(print_r(ini_get_all(),true));
            while ((!$success) && $try < 100) {
                ini_set('max_execution_time', 1000);
                //   Log::d("5");

                $tmpfile = tempnam(_PS_TMP_IMG_DIR_, "ps_import$try");
                $try++;
                //   Log::d("6" , $try);

                $download = Tools::copy($url, $tmpfile);
                //   Log::d("7");
                //   Log::d("Descargas? ", print_r($download),true);
                if ($download) {
                    //   $stats = stat($tmpfile);
                    $fingerprint = md5_file($tmpfile);
                    //   Log::d("8");
                    //   Log::d("Fingerpront $url : \n  $tmpfile \n " , $fingerprint );
                    if (!array_key_exists($fingerprint, $counting_fingerprint)) {
                        $finger_data = array("counted" => 1, "route" => $tmpfile);
                        $counting_fingerprint[$fingerprint] = $finger_data;
                        // Log::d("9");
                    } else {
                        $counting_fingerprint[$fingerprint]["counted"] ++;
                        // Log::d("10");

                        if ($counting_fingerprint[$fingerprint]["counted"] >= 3) {
                            $success = true;
                            // Log::d("Imagen Descargada en : ",$counting_fingerprint[$fingerprint]["route"]);

                            foreach ($counting_fingerprint as $data) {
                                if (!($data["route"] == $counting_fingerprint[$fingerprint]["route"] )) {
                                    @unlink($data["route"]);
                                }
                            }

                            return $counting_fingerprint[$fingerprint]["route"];
                        }
                        @unlink($tmpfile);
                    }
                    //   Log::d(print_r($counting_fingerprint,true));
                    //    Log::d("Datos justo despues de descarga ", print_r($stats,true)."\n".md5_file($tmpfile));
                } else {
                    Log::d("Falló descarga");
                    @unlink($tmpfile);
                }
            }
            return null;
        } catch (Exception $ex) {
            Log::d("Me caí", $ex->getMessage() . "\n" . $ex->getTraceAsString());
        }
    }

    protected function copyImg($id_entity, $url, $id_image = null, $entity = 'products', $regenerate = true, $tmpfile) {

        // Log::d("Enlazando $tmpfile " ,md5_file($tmpfile) ."\n origen: ".$url);
        // $tmpfile = tempnam(_PS_TMP_IMG_DIR_, 'ps_import');
        $watermark_types = explode(',', Configuration::get('WATERMARK_TYPES'));
        $image_obj = false;
        $orig_tmpfile = $tmpfile;
        // $counting_fingerprint = array();
        switch ($entity) {
            default:
            case 'products':
                $image_obj = new Image($id_image);
                $path = $image_obj->getPathForCreation();
                break;
            case 'categories':
                $path = _PS_CAT_IMG_DIR_ . (int) $id_entity;
                break;
            case 'manufacturers':
                $path = _PS_MANU_IMG_DIR_ . (int) $id_entity;
                break;
            case 'suppliers':
                $path = _PS_SUPP_IMG_DIR_ . (int) $id_entity;
                break;
        }

        if (isset($tmpfile)) {
            // Evaluate the memory required to resize the image: if it's too much, you can't resize it.
            if (!ImageManager::checkImageMemoryLimit($tmpfile)) {
                // Log::d("memoryLimit?");
                 @unlink($tmpfile);
                return false;
            }

            $tgt_width = $tgt_height = 0;
            $src_width = $src_height = 0;
            $error = 0;
            ImageManager::resize($tmpfile, $path . '.jpg', null, null, 'jpg', false, $error, $tgt_width, $tgt_height, 5, $src_width, $src_height);
            $images_types = ImageType::getImagesTypes($entity, true);

            if ($regenerate) {
                $previous_path = null;
                $path_infos = array();
                $path_infos[] = array($tgt_width, $tgt_height, $path . '.jpg');
                foreach ($images_types as $image_type) {
                    $tmpfile = $this->get_best_path($image_type['width'], $image_type['height'], $path_infos);
                    if (ImageManager::resize($tmpfile, $path . '-' . stripslashes($image_type['name']) . '.jpg', $image_type['width'], $image_type['height'], 'jpg', false, $error, $tgt_width, $tgt_height, 5, $src_width, $src_height)) {
                        // the last image should not be added in the candidate list if it's bigger than the original image
                        if ($tgt_width <= $src_width && $tgt_height <= $src_height) {
                            $path_infos[] = array($tgt_width, $tgt_height, $path . '-' . stripslashes($image_type['name']) . '.jpg');
                        }
                        if ($entity == 'products') {
                            if (is_file(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int) $id_entity . '.jpg')) {
                                @unlink(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int) $id_entity . '.jpg');
                            }
                            if (is_file(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int) $id_entity . '_' . (int) Context::getContext()->shop->id . '.jpg')) {
                                @unlink(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int) $id_entity . '_' . (int) Context::getContext()->shop->id . '.jpg');
                            }
                        }
                    }
                    if (in_array($image_type['id_image_type'], $watermark_types)) {
                        Hook::exec('actionWatermark', array('id_image' => $id_image, 'id_product' => $id_entity));
                    }
                }
            }
        } else {
            @unlink($orig_tmpfile);
            return false;
        }
         @unlink($orig_tmpfile);
        return true;
    }

    private function cleanUnconnectedIds(){
        $TABLE = "product_centry";
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $query = new DbQuery();
        $query->select('id_product');
        $query->from($TABLE);
        foreach($db->executeS($query) as $result){
            $productTEST= new Product($result['id_product']);

            if ($productTEST->id_centry == ""){
               $this->deleteIdCentry($result['id_product']);


            }
        }
    }
    private function deleteIdCentry($id) {
        $TABLE = "product_centry";
        $sql = "DELETE FROM `" . _DB_PREFIX_ . $TABLE
                . "` WHERE id_product = " . ((int) $id);
        return Db::getInstance()->execute($sql) != false;
    }

}
