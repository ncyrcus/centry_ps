<?php

namespace CentrySDK;

include_once _PS_CONFIG_DIR_ . 'config.inc.php';
include_once _PS_CORE_DIR_ . '/init.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/model/AbstractModel.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/classes/Log.php';

/**
 * Description of Order
 *
 * @author Paulo Sandoval S.
 */
class Order extends AbstractModel
{

    const STATUS_PENDING = "pending";
    const STATUS_SHIPPED = "shipped";
    const STATUS_RECEIVED = "received";
    const STATUS_CANCELLED = "cancelled";

    public $id_prestashop;
    public $id_origin;
    public $origin;
    public $_id;
    public $created_at;
    public $updated_at;
    public $status;
    public $items;
    public $buyer_first_name;
    public $buyer_last_name;
    public $buyer_email;
    public $total_amount;
    public $original_data;
    public $shipped_date;
    public $received_date;
    public $cancelled_date;
    public $address_shipping;
    public $address_billing;
    public $paid_amount;
    public $shipping_amount;
    public $status_origin;
    public $buyer_dni;
    public $_status;
    public $buyer_birthdate;
    public $number_origin;
    public $buyer_mobilephone;

    public function __construct($array = null)
    {
        if (!$array) {
            return;
        }
        foreach ($array as $key => $value) {
            if (!property_exists("\CentrySDK\Order", $key)) {
                continue;
            }
            $this->$key = $value;
        }
    }

    public static function processCart($orderPS, $orderCentry)
    {
        // To Do re-encoding of Shopping Cart involved with the order
        $cart = $orderPS->getCartProducts();
        //error_log(print_r($orderCentry,true));
        $centry_cart = $orderCentry ? $orderCentry->items : false;
        $cart_processed = array();
        $id_order_item = false;
        foreach ($cart as $value) {
            if ($centry_cart) {
                foreach ($centry_cart as $centry_value) {

                    if ($value['id_order_detail'] == $centry_value['id_origin']) {
                        $id_order_item = $centry_value['_id'];
                    }
                }
            }
            $product_object = new \Product($value['product_id']);
            $combination = new \Combination($value['product_attribute_id']);
            //error_log("cart"  . print_r($cart,true));
            $product = array(
                "variant" => ($combination->id > 0) ? $combination->id_centry : $product_object->id_centry_unique_variant,
                "order" => (isset($orderPS->id_centry)) ? $orderPS->id_centry : null,
                "name" => $product_object->name[(int) \Configuration::get('PS_LANG_DEFAULT')],
                "ps_product_id" => $value['product_id'],
                "ps_product_attribute_id" => $value['product_attribute_id'],
                "unit_price" => $value['unit_price_tax_incl'],
                "quantity" => $value['cart_quantity'],
                "created_at" => $value['date_add'],
                "updated_at" => $value['date_upd'],
                "id_origin" => $value['id_order_detail'],
            );
            $product['_id'] = $id_order_item ? $id_order_item : null;
            if (self::processStatus(new \OrderState($orderPS->current_state)) == self::STATUS_CANCELLED) {
                $product["quantity_restocked"] = $product["quantity"];
            }
            array_push($cart_processed, $product);
        }
        return $cart_processed;
    }

    private static function processStatus(\OrderState $order_state)
    {
        if ($order_state->id == \Configuration::get('CENTRY_ORDERSTATE_SHIPPED')) {
            return self::STATUS_SHIPPED;
        } elseif ($order_state->id == \Configuration::get('CENTRY_ORDERSTATE_RECEIVED')) {
            return self::STATUS_RECEIVED;
        } elseif ($order_state->id == \Configuration::get('CENTRY_ORDERSTATE_CANCELLED') || $order_state->id == 8) {
            return self::STATUS_CANCELLED;
        } elseif ($order_state->id == \Configuration::get('PS_OS_CANCELED') || $order_state->id == \Configuration::get('PS_OS_ERROR')) {
            return self::STATUS_CANCELLED;
        } elseif ($order_state->id == \Configuration::get('PS_OS_SHIPPING')) {
            return self::STATUS_SHIPPED;
        } elseif ($order_state->id == \Configuration::get('PS_OS_DELIVERED')) {
            return self::STATUS_RECEIVED;
        } elseif ($order_state == null || $order_state->id == \Configuration::get('CENTRY_ORDERSTATE_PENDING')) {
            return self::STATUS_PENDING;
        } else {
            return self::STATUS_PENDING;
        }
    }

    public static function addressForCentry($adressId)
    {
        $address = new \Address($adressId);
        $state = new \State($address->id_state);
        $country = new \Country($address->id_country);
        $array = array(
            "first_name" => $address->firstname,
            "last_name" => $address->lastname,
            "phone1" => $address->phone,
            "phone2" => $address->phone_mobile,
            "line1" => $address->address1,
            "line2" => $address->address2,
            "zip_code" => $address->postcode,
            "city" => $address->city,
            "state" => $state->name,
            "country" => $country->name[(int) \Configuration::get('PS_LANG_DEFAULT')]
        );
        return $array;
    }

    /**
     * En base a una orden de prestashop, crea un en el formato del SDK
     * @param \Order $orderPS
     */
    public static function fromPrestashop($orderPS)
    {
        $orderCentry = isset($orderPS->id_centry) ? (new  \CentrySDK\Orders())->findById($orderPS->id_centry) : null;
        $order_object = new \Order($orderPS->id);
        $customer_object = new \Customer($orderPS->id_customer);
        $order = new Order();
        $order->id_prestashop       = $orderPS->id;
        $order->id_origin           = ($orderCentry and $orderCentry->origin != "Prestashop") ? null : $orderPS->id;
        $order->origin              = ($orderCentry and $orderCentry->origin != "Prestashop") ? null : "Prestashop";
        $order->_id                 = ($orderCentry) ? $orderPS->id_centry : null; // not sure
        $order->total_amount        = $orderPS->total_products_wt;
        $order->paid_amount         = $orderPS->total_paid;
        $order->shipping_amount     = $orderPS->total_shipping_tax_incl;
        $order->items               = self::processCart($orderPS, $orderCentry);
        $order->buyer_email         = $customer_object->email;
        $order->buyer_first_name    = $customer_object->firstname;
        $order->buyer_last_name     = $customer_object->lastname;
        $order->address_shipping    = self::addressForCentry($orderPS->id_address_delivery);
        $order->address_billing     = self::addressForCentry($orderPS->id_address_invoice);
        $order->status              = self::processStatus(new \OrderState($orderPS->current_state));
        //$order->status_origin       = (new \Order($orderPS->getCurrentStateFull((int) \Configuration::get('PS_LANG_DEFAULT'))->name[1]));
        $order->status_origin       = (new \OrderState($orderPS->current_state))->name[(int) \Configuration::get('PS_LANG_DEFAULT')];
        $order->shipped_date        = null;
        $order->received_date       = null;
        $order->cancelled_date      = null;
        $order->original_data       = json_encode($order);
        return $order;
    }

    public function toParametersArray()
    {
        $params = parent::toParametersArray();
        foreach ($params['order'] as $key => $value) {
            if ($value == null) {
                unset($params['order'][$key]);
            }
        }
        return $params;
    }
}
