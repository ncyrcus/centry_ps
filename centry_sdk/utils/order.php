<?php

require_once 'abstractresource.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/model/Product.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/model/Order.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/Brands.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/Categories.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/Orders.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/classes/Log.php';

/**
 * Define como el controlador atenderá las 4 acciones básicas del CRUD de un
 * recurso Product.
 *
 * @author Paulo Sandoval S.
 * @see Product
 */
class Centry_psOrder extends Centry_psAbstractResource {

    /**
     *
     * @param type $params parámetros que entrega el JSON
     * @return boolean output de la función
     */
    public function save($order) {
        $params = json_decode(json_encode($order), true);
        if (Order::findorderByIdCentry($order->_id)) {
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
        if (Configuration::get('CENTRY_LIMIT_ORDER_CREATION') > $params["created_at"] || Order::findOrderByIdCentry($params["_id"]) || $params["origin"] == "Prestashop") {
            return false;
        }
        try {
            $customer = $this->createGuestCustomer($params);
            $shipping = $this->createAddress(isset($params["address_shipping"]) ? $params["address_shipping"] : $this->generateFakeAddressParams(), $customer, "Dirección de Envío", $params);
            $billing = $this->createAddress(isset($params["address_billing"]) ? $params["address_billing"] : $this->generateFakeAddressParams(), $customer, "Dirección de Facturación", $params);
            $cart = $this->createCart($params, $customer, $shipping, $billing);

            $order_state_id = $this->getOrderStateId($params["_status"]);
            $order = new Order();
            $order->id_centry = $params["_id"];

            do {
                $reference = Order::generateReference();
            } while (Order::getByReference($reference)->count());
            $order->reference = $reference;

            $order->current_state = $order_state_id;
            $order->id_address_delivery = $shipping->id;
            $order->id_address_invoice = $billing->id;
            $order->id_carrier = 0;
            $order->id_cart = $cart->id;
            $order->id_customer = $customer->id;
            $order->id_lang = (int) (Configuration::get('PS_LANG_DEFAULT'));
            $shop = new Shop((int) Configuration::get('PS_SHOP_DEFAULT'));
            $order->id_shop = $shop->id;
            $order->id_shop_group = $shop->id_shop_group;
            $order->secure_key = $customer->secure_key;
            $order->delivery_number;
            $order->gift = false;
            $numero_de_orden = isset($params["number_origin"]) ? $params["number_origin"] : $params["id_origin"];
            $order->payment = $params["origin"] . " " . $numero_de_orden;
            $order->module = "PaymentModule";
            $currency = new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT'));
            $order->id_currency = $currency->id;
            $order->conversion_rate = $currency->conversion_rate;
            $order->valid = true;
            $order->total_paid_real = 0;
            $order->total_paid_tax_excl = (Configuration::get('PS_TAX')) ? Tools::ps_round($params["paid_amount"] * (1 / 1.19), 2) : $params["total_amount"];
            $order->total_paid_tax_incl = $params["paid_amount"];
            $order->total_paid = (int) $params["paid_amount"];

            $total_taxes = 0;
            $total_products = 0;
            $total_shipping = 0;
            foreach ($params["items"] as $item) {
                $total_taxes += $item["tax_amount"] * $item["quantity"];
                $total_products += $item["paid_price"] * $item["quantity"];
                $total_shipping += $item["shipping_amount"];
            }
            $order->total_products = $total_products - $total_taxes;
            $order->total_products_wt = $params['total_amount'];

            $order->total_shipping_tax_incl = $params["shipping_amount"];
            $order->total_shipping_tax_excl = Tools::ps_round($order->total_shipping_tax_incl / 1.19);
            $order->total_shipping = $params["shipping_amount"];
            $order->save();
            $this->createOrderDetail($params, $order, $cart, $order_state_id);
            return true;
        } catch (Exception $ex) {
            error_log($ex->getMessage() . "\n" . $ex->getTraceAsString());
            Log::d("Fallo al crear orden " . $params["_id"], $ex->getMessage() . "\n" . $ex->getTraceAsString());
            return false;
        }
    }

    private function getOrderStateId($status) {
        switch ($status) {
            case "pending":
                return Configuration::get('CENTRY_ORDERSTATE_PENDING');
            case "shipped":
                return Configuration::get('CENTRY_ORDERSTATE_SHIPPED');
            case "received":
                return Configuration::get('CENTRY_ORDERSTATE_RECEIVED');
            case "cancelled":
            case "cancelled_before_shipping":
            case "cancelled_after_shipping":
                return Configuration::get('CENTRY_ORDERSTATE_CANCELLED');
            default :
                return 0;
        }
    }

    private function generateFakeAddressParams() {
        return array(
            "first_name" => "Sin nombre",
            "last_name" => "Sin apellido",
            "phone1" => "Sin número 1",
            "phone2" => "Sin número 2",
            "email" => "No se encontró mail",
            "line1" => "Sin dirección",
            "line2" => "",
            "zip_code" => "8240000",
            "city" => "Santiago",
            "state" => "Santiago",
            "country" => "Chile"
        );
    }

    private function createAddress($params, $customer, $alias, $original_params) {
        $address = new Address();
        $address->address1 = $params["line1"] ? htmlentities($params["line1"], ENT_DISALLOWED, "UTF-8") : "Sin información";
        $address->address2 = htmlentities($params["line2"] . " " . $params["state"], ENT_DISALLOWED, "UTF-8");
        $address->postcode = $params["zip_code"];
        $address->city = $params["city"] ? $params["city"] : "Sin información";
        $address->id_customer = $customer->id;
        $address->firstname = ( \Validate::isName($params["first_name"]) and $params["first_name"]) ? $params["first_name"] : "Sin información";
        $address->lastname = (\Validate::isName($params["last_name"])and $params["last_name"]) ? $params["last_name"] : "Sin información";
        $address->phone = \Validate::isPhoneNumber($params["phone1"], ENT_DISALLOWED, "UTF-8") ? $params["phone1"] : null;
        $address->phone_mobile = \Validate::isPhoneNumber($params["phone2"], ENT_DISALLOWED, "UTF-8") ? $params["phone2"] : null;
        $address->alias = $alias;
        $address->other = "Email: " . $params["email"];
        $address->other .= " Orden número : " . htmlentities($original_params["id_origin"] . " de " . htmlentities($original_params["origin"], ENT_DISALLOWED, "UTF-8"));
        //error_log($address->other);
        if (($id_country = Country::getIdByName(null, $params["country"]))) {
            $address->id_country = $id_country;
        } else {
            $address->id_country = (int) Configuration::get('PS_COUNTRY_DEFAULT');
            $address->address2 .= ", " . $params["country"];
        }
        if (($id_state = State::getIdByName(null, $params["state"]))) {
            $address->id_state = $id_state;
        } else {
            $address->id_state = State::getIdByName(null, "Santiago");
            $address->address2 .= ", " . $params["state"];
        }
        $address->save();
        return $address;
    }

    private function createGuestCustomer($params) {
      $customer = new Customer();
      $customer->id_lang = (int) (Configuration::get('PS_LANG_DEFAULT'));
      $customer->id_currency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
      $customer->is_guest = true;
      $customer->email = $params["buyer_email"] ? $params["buyer_email"] : "no-email@centry.cl";
      $customer->firstname = (\Validate::isName($params["buyer_first_name"]) and $params["buyer_first_name"]) ? $params["buyer_first_name"] : "Sin informacion";
      $customer->lastname = (\Validate::isName($params["buyer_last_name"]) and $params["buyer_last_name"]) ? $params["buyer_last_name"] : "Sin informacion";
      $customer->passwd = "7e8fa98a4cc193d585682a81f9f8b0c9";
      $customer->rut = $params["buyer_dni"] == "" ? "11.111.111-9" :  $params["buyer_dni"] ;
      $customer->save();
      $guest = new Guest();
      $guest->id_customer = $customer->id;
      $guest->save();

      $customer->id_guest = $guest->id;
      $customer->save();
      return $customer;
    }

    private function createCart($params, $customer, $shipping, $billing) {
        $shop = new Shop((int) Configuration::get('PS_SHOP_DEFAULT'));
        $cart = new Cart();
        $cart->id_customer = $customer->id;
        $cart->id_shop_group = $shop->id_shop_group;
        $cart->id_carrier = 2;
        $cart->id_address_delivery = $shipping->id;
        $cart->id_address_invoice = $billing->id;
        $cart->id_guest = $customer->id_guest;
        $cart->id_lang = (int) (Configuration::get('PS_LANG_DEFAULT'));
        $cart->id_currency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        $cart->save();
        foreach ($params["items"] as $value) {
            $product = isset($value["product_id"]) ? Product::findProductByIdCentry($value["product_id"]) : null;
            $quantity = $value["quantity"];
            $variant = $value["variant_id"];
            $combination = isset($value["variant_id"]) ? Combination::findCombinationByIdCentry($value["variant_id"]) : null;
            if ($combination) {
                $id_product = $combination->id_product;
                $cart->updateQty($quantity, $id_product, $combination->id, false, 'up', $shipping->id);
            } elseif ($product) {
                $id_product = $product->id;
                $cart->updateQty($quantity, $id_product, null, false, 'up', $shipping->id);
            }
        }
        $cart->save();

        return $cart;
    }

    public function getIpaByIdVariant($id_variant){

        $sql = 'SELECT id_combination, sku FROM `'._DB_PREFIX_.'combination_centry` where id_centry ="'.$id_variant.'";';
        if ($results = Db::getInstance()->getRow($sql)){
            if (!empty($results)) {
                return $results;
            }else{
                return false;
            }
        }
    }


    public function getProductbySKU($sku){
      Log::d("Buscando SKU: ", print_r($sku,true));
        $db = Db::getInstance();
        $sql = 'SELECT id_product FROM `'._DB_PREFIX_.'product_attribute` where reference ="'.$sku.'";';
        if ($results = $db->getRow($sql)){
                return $results;
        }else{
          $sql2 = 'SELECT id_product FROM `'._DB_PREFIX_.'product` where reference ="'.$sku.'";';
          if ($results2 = $db->getRow($sql2)){
                return $results2;

              }else{
                return false;

              }
          }
        }



    private function createOrderDetail($params, $order, $cart, $state_id) {
        //error_log(print_r($params, true));
        $detail = new OrderDetail();
        $shop = new Shop((int) Configuration::get('PS_SHOP_DEFAULT'));
        $detail->id_order = $order->id;
        $detail->id_shop = $shop->id;
        $product_list = array();
        foreach ($params["items"] as $value) {
            $prueba = Product::findProductByIdCentry("aaa");
            error_log(gettype($prueba));
            error_log(!$prueba);
            $product = isset($value["product_id"]) ? Product::findProductByIdCentry($value["product_id"]) : null;
            if(is_null($product) or !$product){
              $id = $this->getProductbySKU($value["sku"])["id_product"];
              $product = new Product($id);
            }
            $unit_price = $value["unit_price"];
            $tax_amount = $value["tax_amount"];
            $quantity = $value["quantity"];
            $combination = isset($value["variant_id"]) ? Combination::findCombinationByIdCentry($value["variant_id"]) : null;
            $IPA = null;
            $productAttributeInfo = $this->getIpaByIdVariant($value["variant_id"]);
            $IPA = $productAttributeInfo["id_combination"];
            if ($combination) {
                $name = isset($value["name"]) ? $value["name"] : $product->name[(int)\Configuration::get('PS_LANG_DEFAULT')];
                $reference = $productAttributeInfo["sku"];
                $product_list[] = array(
                    'id_shop' => $shop->id,
                    'id_product' => $product ? $product->id : null,
                    'reference' => $reference, # DIGITAG
                    'id_product_attribute' => $combination->id,
                    'name' => isset($value["variant"]["description"])? $name . " ". $value["variant"]["description"] ." ". $reference : $name ." ". $reference  ,
                    'cart_quantity' => $quantity,
                    'price' => $unit_price - $tax_amount,
                    'price_wt' => $unit_price,
                    'total' => ($unit_price - $tax_amount) * $quantity,
                    'total_wt' => $unit_price * $quantity,
                    'wholesale_price' => $unit_price,
                    'id_supplier' => 0,
                    'additional_shipping_cost' => 0,
                    'ecotax' => 0,
                    'stock_quantity' => 0,
                    'weight_attribute' => 0,
                );
            } elseif ($product) {#agregar condicion ver producto con  variante y verificar existencia de id product atribute
                $product_list[] = array(
                    'id_shop' => $shop->id,
                    'id_product' => $product ? $product->id : null,
                    'id_product_attribute' => $IPA, // must be null
                    'name' => $product ? $product->name[(int)\Configuration::get('PS_LANG_DEFAULT')] : "Producto no homologado con Centry",
                    'cart_quantity' => $quantity,
                    'price' => $unit_price - $tax_amount,
                    'price_wt' => $unit_price,
                    'total' => ($unit_price - $tax_amount) * $quantity,
                    'total_wt' => $unit_price * $quantity,
                    'wholesale_price' => $unit_price,
                    'id_supplier' => 0,
                    'additional_shipping_cost' => 0,
                    'ecotax' => 0,
                    'stock_quantity' => 0,
                    'weight_attribute' => 0,
                ); //se deberia ver el caso de que sea simple en PS?
            }
        }
        $detail->createList($order, $cart, $state_id, $product_list);
    }


    protected function read($id_centry) {
//        error_log("Order read: $id_centry");
        $order = Order::findOrderByIdCentry($id_centry);
        return $order == false ? false : \CentrySDK\Order::fromPrestashop($order)->toParametersArray();
    }

    /**
     *
     * @param type $params
     * @return boolean
     */
    protected function update($params) {
//        error_log("Order update: " . print_r($params, true));
        $order = Order::findOrderByIdCentry($params["_id"]);
        if ($params['origin'] == "Prestashop") {
            return true;
        }
        if (!$order) {
            return false;
        }
        try {
            $flag = ($order->current_state == \Configuration::get('PS_OS_CANCELED')) || $order->current_state == Configuration::get('CENTRY_ORDERSTATE_RECEIVED');
            $order->current_state = $flag ? $order->current_state : $this->getOrderStateId($params["_status"]);
            $order->total_paid = (int) $params["paid_amount"];
            $order->total_paid_tax_incl = $params["paid_amount"];
            $order->total_paid_tax_excl = (Configuration::get('PS_TAX')) ? Tools::ps_round($params["paid_amount"] * (1 / 1.19), 2) : $params["total_amount"];
            $order->save();
            if (!$order->getOrderDetailList()) {
                $customer = $this->createGuestCustomer($params);
                $shipping = $this->createAddress(isset($params["address_shipping"]) ? $params["address_shipping"] : $this->generateFakeAddressParams(), $customer, "Dirección de Envío", $params);
                $billing = $this->createAddress(isset($params["address_billing"]) ? $params["address_billing"] : $this->generateFakeAddressParams(), $customer, "Dirección de Facturación", $params);
                $cart = $this->createCart($params, $customer, $shipping, $billing);
                $order_state_id = $this->getOrderStateId($params["_status"]);
                $this->createOrderDetail($params, $order, $cart, $order_state_id);
            }
            return true;
        } catch (Exception $ex) {
            error_log($ex->getMessage() . "\n" . $ex->getTraceAsString());
            Log::d("Fallo al actualizar categoría ", $ex->getMessage() . "\n" . $ex->getTraceAsString());
            return false;
        }
    }

    protected function delete($id_centry) {
        //    error_log("Order delete: $id_centry");
        $order = Order::findOrderByIdCentry($id_centry);
        if (!$order) {
            return false;
        }
        try {
            if ($order->delete()) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $ex) {
            error_log($ex->getMessage() . "\n" . $ex->getTraceAsString());
            Log::d("Fallo al eliminar categoría ", $ex->getMessage() . "\n" . $ex->getTraceAsString());
            return false;
        }
    }

}
