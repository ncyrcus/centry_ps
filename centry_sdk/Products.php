<?php

namespace CentrySDK;

require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/AbstractResource.php';

/**
 * Description of Products
 *
 * @author Elías Lama L.
 */
class Products extends AbstractResource {

    protected function __init() {
        $this->resource = "products";
        $this->modelClass = "Product";
    }

    public function save(\Product $product) {
        (isset($product->id_centry) && trim($product->id_centry) != "" ) ? $this->update($product) : $this->create($product);
    }

    public function create(\Product $product) {
        $parameters = Product::fromPrestashop($product)->toParametersArray();
        $response = $this->doRequest($this->getUrlResource() . ".json", $parameters, \OAuth2\Client::HTTP_METHOD_POST);
        $result = $response['code'] != 200 ? null : $response['result'];
        if (isset($result)) {
            $resp = new Product($result);
            $product->id_centry = $resp->_id;
            $product->updateIdCentry();
            $this->registerIdCentryInCombinations($product, $resp);
            $this->registerIdCentryInImages($product, $resp);
        }
    }

    private function registerIdCentryInCombinations(\Product $productPS, Product $productCentry) {
        if (count($productCentry->variants) == 1 && !$productCentry->variants[0]->original_data) {
            $productPS->id_centry_unique_variant = $productCentry->variants[0]->_id;
            $productPS->updateIdCentry();
        } else {
            foreach ($productCentry->variants as $variant) {
                $combination = new \Combination($variant->original_data);
                $combination->id_centry = $variant->_id;
                $combination->updateIdCentry();
            }
        }
    }

    private function registerIdCentryInImages(\Product $productPS, Product $productCentry) {
        $images = $productPS->getImages((int) \Configuration::get('PS_LANG_DEFAULT'));

        foreach ($images as $image) {
            $imagePS = new \Image($image['id_image']);
            $imageFile = $imagePS->getImgPath();
            $fingerprintPS = md5_file(_PS_PROD_IMG_DIR_ . $imageFile . "." . $imagePS->image_format);
            if ($image['cover']) {
                $imagePS->id_centry = $productCentry->_id;
                $imagePS->cover = true;
                $imagePS->content_type = $productCentry->cover_content_type;
                $imagePS->file_name = $productCentry->cover_file_name;
                $imagePS->file_size = $productCentry->cover_file_size;
                $imagePS->created_at = $productCentry->created_at;
                $imagePS->updated_at = $productCentry->cover_updated_at;
                $imagePS->fingerprint = $productCentry->cover_fingerprint;
                $imagePS->url = $productCentry->cover_url;
                $imagePS->save();
            } else {
                foreach ($productCentry->assets as $asset) {
                    if ($asset->image_fingerprint == $fingerprintPS) {
                        $imagePS->id_centry = $asset->_id;
                        $imagePS->cover = false;
                        $imagePS->content_type = $asset->image_content_type;
                        $imagePS->file_name = $asset->image_file_name;
                        $imagePS->file_size = $asset->image_file_size;
                        $imagePS->created_at = $asset->created_at;
                        $imagePS->updated_at = $asset->updated_at;
                        $imagePS->fingerprint = $asset->image_fingerprint;
                        $imagePS->url = $asset->url;
                        $imagePS->save();
                    }
                }
            }
        }
    }

    /**
     *
     * @param \CentrySDK\Product $productCentry
     * @param array $descVariant
     * @return \CentrySDK\Variant
     */
    private function findVariant(Product $productCentry, $descVariant) {
        foreach ($productCentry->variants as $variant) {
            $coincide = true;
            foreach ($descVariant as $desc) {
                if (!property_exists($variant, $desc["option"]) || $variant->$desc["option"] != $desc["value"]) {
                    $coincide = false;
                    break;
                }
            }
            if ($coincide) {
                return $variant;
            }
        }
        return null;
    }

    public function update(\Product $product) {
        $parameters = Product::fromPrestashop($product)->toParametersArray();
        $response = $this->doRequest($this->getUrlResource() . "/{$product->id_centry}.json", $parameters, \OAuth2\Client::HTTP_METHOD_PATCH);
        $result = $response['code'] != 200 ? null : $response['result'];
        if (isset($result)) {
            $resp = new Product($result);
            $this->registerIdCentryInCombinations($product, $resp);
            $this->registerIdCentryInImages($product, $resp);
        } elseif ($response['code'] == 404) {
            $this->create($product);
        } else {
            \Log::d("No se pudo actualizar el producto", print_r($parameters, true));
        }
    }

    public function delete($id_centry) {
        $this->doRequest($this->getUrlResource() . "/$id_centry.json", array(), \OAuth2\Client::HTTP_METHOD_DELETE);
    }

}
