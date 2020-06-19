<?php

namespace CentrySDK;

include_once _PS_CONFIG_DIR_ . 'config.inc.php';
include_once _PS_CORE_DIR_ . '/init.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/model/AbstractModel.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/classes/Log.php';

/**
 * Description of Brand
 *
 * @author Elías Lama L.
 */
class Product extends AbstractModel {

    public $id_prestashop;
    public $_id;
    public $name;
    public $created_at;
    public $updated_at;
    public $assets;
    public $barcode;
    public $brand_id;
    public $bulk_upload;
    public $category_id;
    public $color;
    public $company_id;
    public $condition;
    public $cover_content_type;
    public $cover_file_name;
    public $cover_file_size;
    public $cover_fingerprint;
    public $cover_updated_at;
    public $cover_url;
    public $cover_jpg_url;
    public $description;
    public $gender;
    public $options;
    public $packageheight;
    public $packagelength;
    public $packageweight;
    public $packagewidth;
    public $price;
    public $price_compare;
    public $publish;
    public $quantity;
    public $saleenddate;
    public $salestartdate;
    public $season;
    public $seasonyear;
    public $seo_title;
    public $seo_description;
    public $shortdescription;
    public $sku;
    public $status;

    /** @var Variant */
    public $variants;
    public $warranty;

    public function __construct($array = null) {
        if (!$array) {
            return;
        }
        foreach ($array as $key => $value) {
            if (!property_exists("\CentrySDK\Product", $key)) {
                continue;
            }
            switch ($key) {
                case "assets":
                    $this->addAssets($value);
                    break;
                case "variants":
                    $this->addVariants($value);
                    break;
                default :
                    $this->$key = $value;
            }
        }
    }

    /**
     * En base a un producto de prestashop, crea un en el formta del SDK
     * @param \Product $productPS
     */
    public static function fromPrestashop($productPS) {
        $idLang = (int) \Configuration::get('PS_LANG_DEFAULT');
        $product = new Product();
        $product->_id = $productPS->id_centry;
        $product->name = $productPS->name[$idLang];
        $product->description = \Configuration::get('CENTRY_PRODUCT_CHARACTERISTICS') ? $productPS->description_short[$idLang] : $productPS->description[$idLang];
        $product->shortdescription = \Configuration::get('CENTRY_PRODUCT_CHARACTERISTICS') ? $productPS->description[$idLang] : static::getProductFeaturesAsUnorderedListHTML($productPS);
        $product->price_compare = $productPS->getPriceWithoutReduct();
        $sale = self::getPriceSale($productPS);
        $product->price = $sale["price"];
        $product->salestartdate = $sale["salestartdate"];
        $product->saleenddate = $sale["saleenddate"];
        $product->quantity = $productPS->quantity;
        $product->sku = $productPS->reference;
        $product->barcode = $productPS->upc ? $productPS->upc : $productPS->ean13;
        $product->brand_id = \Manufacturer::getCentryManufacturer($productPS->id_manufacturer);
        $product->category_id = self::getCategoryId($productPS);

        $cover = self::getCover($productPS);
        $product->cover_content_type = $cover["content_type"];
        $product->cover_file_name = $cover["file_name"];
        $product->cover_file_size = $cover["file_size"];
        $product->cover_fingerprint = $cover["fingerprint"];
        $product->cover_updated_at = $cover["updated_at"];
        $product->cover_url = $cover["url"];

        $product->assets = self::getAssets($productPS);
        $product->options = self::getOptions($productPS);
        $product->variants = self::getVariants($productPS);

        $product->publish = true; // Parece que no es nada
        $product->seo_description = $productPS->meta_description[$idLang];
        $product->seo_title = $productPS->meta_title[$idLang];
        $product->status = $productPS->active; // Disponible para integración
        $product->created_at = $productPS->date_add;
        $product->updated_at = $productPS->date_upd;

        $product->seasonyear = self::getFeatureValue($productPS, \Configuration::get('CENTRY_FEATURE_SEASON_YEAR'));
        $product->season = self::getFeatureValue($productPS, \Configuration::get('CENTRY_FEATURE_SEASON'));
        $product->gender = self::getFeatureValue($productPS, \Configuration::get('CENTRY_FEATURE_GENDER'));

        $product->packageheight = $productPS->height;
        $product->packagelength = $productPS->depth;
        $product->packagewidth = $productPS->width;
        $product->packageweight = $productPS->weight;

        $product->condition = $productPS->condition;
        return $product;
    }

    private static function getCategoryId($productPS) {
        switch (\Configuration::get('CENTRY_CATEGORY_SOURCE')) {
            case \Centry_ps::CATEGORY_SOURCE_PRIMARY:
                return $productPS->getDefaultCategory() ? (new \Category($productPS->getDefaultCategory()))->id_centry : null;
            case \Centry_ps::CATEGORY_SOURCE_LOWER_SECONDARY:
                $level_depth = 0;
                $id_centry = null;
                foreach ($productPS->getCategories() as $category_id) {
                    $category = new \Category($category_id);
                    if ($category->id_centry && $category->level_depth > $level_depth) {
                        $id_centry = $category->id_centry;
                        $level_depth = $category->level_depth;
                    }
                }
                return $id_centry;
        }
    }

    /**
     *
     * @param \Product $product
     * @return string <code>ul HTML</code> features list.
     */
    private static function getProductFeaturesAsUnorderedListHTML($product) {
        $ul = "<ul>";
        foreach ($product->getFeatures() as $feature) {
            $name = array_values((new \Feature($feature["id_feature"]))->name)[0];
            $value = array_values((new \FeatureValue($feature["id_feature_value"]))->value)[0];
            $ul .= "<li>$name: $value</li>";
        }
        return "$ul</ul>";
    }

    /**
     *
     * @param \Product $product
     * @return array
     */
    private static function getVariants($product) {
        $array_variants = array();

        foreach (self::getCombinationsAndExtras($product) as $id_product_attribute => $values) {
            $combi_actual = new \Combination($id_product_attribute);
            $variant = array(
                "_id" => $combi_actual->id_centry,
                "product_id" => $product->id_centry,
                "quantity" => $values["quantity"],
                "barcode" => $combi_actual->upc ? $combi_actual->upc : $combi_actual->ean13,
                "sku" => $combi_actual->reference,
                "description" => $values["description"],
                "original_data" => $combi_actual->id,
                "size" => array_key_exists('size', $values) ? $values["size"] : NULL,
                "color" => array_key_exists('color', $values) ? $values["color"] : NULL
            );
            $array_variants[] = $variant;
        }

        if (count($array_variants) == 0) {
            $array_variants[] = array(
                "_id" => $product->id_centry_unique_variant,
                "product_id" => $product->id_centry,
                "quantity" => $product->getQuantity($product->id),
                "barcode" => $product->upc ? $product->upc : $product->ean13,
                "sku" => $product->reference
            );
        }

        return $array_variants;
    }

    /**
     * Toma los "AttributeCombinations" del producto en Prestashop
     * @param \Product $product
     * @return array
     */
    public static function getCombinationsAndExtras($product) {
        $array_combinations = $product->getAttributeCombinations((int) \Configuration::get('PS_LANG_DEFAULT'));
        $array_product_attributes = array();

        foreach ($array_combinations as $combination) {
            $id_pa = $combination["id_product_attribute"];
            $attribute_group = new \AttributeGroup($combination["id_attribute_group"]);
            $attribute = new \Attribute($combination["id_attribute"]);

            if (!array_key_exists($id_pa, $array_product_attributes)) {
                $array_product_attributes[$id_pa] = array(
                    "description" => $combination["attribute_name"],
                    "quantity" => $combination["quantity"]
                        //                   "desc_variant" => array()
                );
            } else {
                $array_product_attributes[$id_pa]["description"] .= ", {$combination["attribute_name"]}";
            }
            if ($attribute_group->field_centry && $attribute->id_centry) {
                $array_product_attributes[$id_pa][$attribute_group->field_centry] = $attribute->id_centry;
            }
        }

        return $array_product_attributes;
    }

    /**
     *
     * @param \Product $product
     * @return array
     */
    private static function getCover($product) {
        try {
            $cover = \Product::getCover($product->id);
            $image = new \Image($cover["id_image"]);
            $array = array(
                "content_type" => $image->content_type,
                "file_name" => $image->file_name,
                "file_size" => $image->file_size,
                "fingerprint" => $image->fingerprint,
                "updated_at" => $image->updated_at,
                "created_at" => $image->created_at,
                "url" => $image->getExistingImgPath() ? (_PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . "." . $image->image_format) : null
            );
            return $array;
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
            \Log::d("Fallo en imagen principal", $ex->getTraceAsString());
            return null;
        }
    }

    /**
     *
     * @param \Product $product
     * @return array
     */
    private static function getAssets($product) {
        $array_assets = array();
        try {
            $images = $product->getImages((int) \Configuration::get('PS_LANG_DEFAULT'));
            foreach ($images as $value) {
                if ($value["cover"] == 1) {
                    continue;
                } else {
                    $image = new \Image($value["id_image"]);
                    $array_image = array(
                        "alt" => null,
                        "created_at" => $image->created_at,
                        "_id" => $image->id_centry,
                        "image_content_type" => $image->content_type,
                        "image_file_name" => $image->file_name,
                        "image_file_size" => $image->file_size,
                        "image_fingerprint" => $image->fingerprint,
                        "image_updated_at" => $image->updated_at,
                        "updated_at" => $image->updated_at,
                        "url" => _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . "." . $image->image_format,
                    );
                    array_push($array_assets, $array_image);
                }
            }
        } catch (Exception $exc) {
            error_log($exc->getMessage() . "\n" . $exc->getTraceAsString());
            \Log::d("Fallo en imagen ", $ex->getTraceAsString());
        }
        return $array_assets;
    }

    /**
     *
     * @param \Product $product
     * @return string
     */
    private static function getOptions($product) {
        $array_combinations = $product->getAttributeCombinations((int) \Configuration::get('PS_LANG_DEFAULT'));
        $array_groups = array();
        foreach ($array_combinations as $value) {
            $nombre_group = $value["group_name"];
            if (!in_array($nombre_group, $array_groups)) {
                $array_groups[] = $nombre_group;
            }
        }

        return implode(", ", $array_groups);
    }

    /**
     *
     * @param \Product $product
     * @param type $id_feature
     */
    private static function getFeatureValue($product, $id_feature) {
        foreach ($product->getFeatures() as $feature) {
            if ($feature["id_feature"] == $id_feature) {
                return array_values((new \FeatureValue($feature["id_feature_value"]))->value)[0];
            }
        }
        return "";
    }

    /**
     *
     * @param \Product $productPS
     * @return type
     */
    private static function getPriceSale($productPS) {
        $sale = array(
            "price" => null,
            "salestartdate" => null,
            "saleenddate" => null
        );
        $specificPrices = \SpecificPrice::getByProductId($productPS->id);
        foreach ($specificPrices as $sp) {
            if ($sp["from_quantity"] == 1) {
                $sale["price"] = $productPS->getPrice();
                $sale["salestartdate"] = $sp["from"] == "0000-00-00 00:00:00" ? "2016-01-01" : substr($sp["from"], 0, 10);
                $sale["saleenddate"] = $sp["to"] == "0000-00-00 00:00:00" ? "2100-12-31" : substr($sp["to"], 0, 10);
            }
            return $sale;
        }
        return $sale;
    }

    private function addAssets($array) {
        $this->assets = array();
        foreach ($array as $asset) {
            $this->assets[] = new Asset($asset);
        }
    }

    private function addVariants($array) {
        if (!$this->variants) {
            $this->variants = array();
        }
        foreach ($array as $variant) {
            $this->variants[] = new Variant($variant);
        }
    }

}

class Asset extends AbstractModel {

    public $_id;
    public $name;
    public $created_at;
    public $updated_at;
    public $alt;
    public $image_content_type;
    public $image_file_name;
    public $image_file_size;
    public $image_fingerprint;
    public $image_updated_at;
    public $url;
    public $url_jpg;

    public function __construct($array = null) {
        if ($array) {
            $this->_id = $array["_id"];
            $this->name = $array["name"];
            $this->created_at = $array["created_at"];
            $this->updated_at = $array["updated_at"];
            $this->alt = $array["alt"];
            $this->image_content_type = $array["image_content_type"];
            $this->image_file_name = $array["image_file_name"];
            $this->image_file_size = $array["image_file_size"];
            $this->image_fingerprint = $array["image_fingerprint"];
            $this->image_updated_at = $array["image_updated_at"];
            $this->url = $array["url"];
            $this->url_jpg = $array["url_jpg"];
        }
    }

}

class Variant extends AbstractModel {

    public $_id;
    public $created_at;
    public $updated_at;
    public $barcode;
    public $bulk_upload;
    public $company_id;
    public $description;
    public $product_id;
    public $quantity;
    public $sku;
    public $color;
    public $size;
    public $original_data;

    public function __construct($array = null) {
        if ($array) {
            $this->_id = $array["_id"];
            $this->created_at = $array["created_at"];
            $this->updated_at = $array["updated_at"];
            $this->barcode = $array["barcode"];
            $this->bulk_upload = $array["bulk_upload"];
            $this->company_id = $array["company_id"];
            $this->description = $array["description"];
            $this->product_id = $array["product_id"];
            $this->quantity = $array["quantity"];
            $this->sku = $array["sku"];
            $this->color = $array["color_id"];
            $this->size = $array["size_id"];
            $this->original_data = $array["original_data"];
        }
    }

}
