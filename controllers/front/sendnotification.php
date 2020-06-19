<?php

require_once _PS_MODULE_DIR_ . 'centry_ps/include/utils.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/model/Product.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/Brands.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/Categories.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/classes/Log.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/classes/PendingNotification.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/classes/Processing.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/model/Order.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/Orders.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/utils/product.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/utils/order.php';

/**
 * Procesamiento de notificaciones
 *
 * @author Vanessa Guzman c.
 */

class Centry_psSendNotificationModuleFrontController extends FrontController {
  public function initContent(){

  if (!function_exists('sem_get')) {
      function sem_get($key) {
          return fopen(__FILE__ . '.sem.' . $key, 'w+');
      }
      function sem_acquire($sem_id) {
          return flock($sem_id, LOCK_EX);
      }
      function sem_release($sem_id) {
          return flock($sem_id, LOCK_UN);
      }
  }
  $sem_id = sem_get( ftok(".", "."), 1);
  sem_acquire($sem_id) or die('Error esperando al semaforo.');
  \Processing::add_thread();
  sem_release($sem_id) or die('Error liberando el semaforo');
  $max_thr = \Configuration::get('CENTRY_MAX_THREADS');
  $thr = \Processing::get_threads()[0]["processing"];
  if ((int)$thr <= (int)$max_thr){
    while ($row = \PendingNotification::get_row_not_processed()){
      try {
        switch (preg_replace("/.*(product|order).*/i", "$1", $row["topic"])) {
          case "product":
            Log::pro($row["topic"],$row['id_topic']);
            if (!(Configuration::get('CENTRY_MAESTRO'))) {
              error_log($row['id_topic']);
              $product_from_centry = (new CentrySDK\Products())->findById($row['id_topic']);
              if ($row["topic"] == "on_product_save"){
                  if ($product_from_centry) {
                	 $result = (new Centry_psProduct())->save($product_from_centry);
                  }
              }
              if ($row["topic"] == "on_product_delete"){
                $result = (new Centry_psProduct())->delete($row['id_topic']);
              }
            }
            break;
            case "order":
              Log::ord($row["topic"],$row['id_topic']);
              if (Configuration::get('CENTRY_MAESTRO')) {
                $order_from_centry = (new CentrySDK\Orders())->findById($row['id_topic']);
                if ($row["topic"] == "on_order_save"){
                    if ($order_from_centry) {
                     $result = (new Centry_psOrder())->save($order_from_centry);
                    }
                  }
                if ($row["topic"] == "on_order_delete"){
                  $result = (new Centry_psOrder())->delete($order_from_centry->_id);
                }
              }

            break;
            }
          PendingNotification::delete_notification($row);
        } catch (Exception $ex) {
            Log::d("Fallo al actualizar el producto" . $row["id_topic"], $ex->getMessage() . "\n" . $ex->getTraceAsString());
            PendingNotification::delete_notification($row);
            continue;
          }
      }
    }
    echo("todo ok");
    \Processing::minus_thread();
    die("ok");
  }
}
