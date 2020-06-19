<?php

require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/Brands.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/Categories.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/Products.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/Sizes.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/Colors.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/utils/abstractresource.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/model/Order.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/classes/Log.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/utils/product.php';

class Centry_psTestModuleFrontController extends FrontController {



    public function initContent() {
        //parent::initContent();
        // header('Content-type: application/json; charset=utf-8');
        header("HTTP/1.0 200 OK");

        //print_r(new Order(1563));
        $parameters = Tools::getAllValues();
        //echo print_r($parameters);


        if(Tools::getIsset('debug') && $parameters['debug']=='test'){
          header('Content-Type:text/plain');
          echo "test successful";
          Log::d("Test write " , "asdasdasd ");

        }
        //print_r($parameters );
        if(Tools::getIsset('debug') && $parameters['debug']=='show'){
          header('Content-Type:text/plain');
          echo "show debug file \n \n \n ";
          readfile(_PS_MODULE_DIR_ . "centry_ps/debug");
        }
        if(Tools::getIsset('debug') && $parameters['debug']=='clean'){
          header('Content-Type:text/plain');
          echo "clean debug file \n \n \n ";
          Log::dclean('debug');
          readfile(_PS_MODULE_DIR_ . "centry_ps/debug");
        }

        if(Tools::getIsset('debug') && $parameters['debug']=='cleanQueue'){
          $results="";
          $TABLE = "processing_notification";
          $db = Db::getInstance();
          $sql = "UPDATE `" . _DB_PREFIX_ .$TABLE
                  . "` SET `processing` = 0";
          if($db->execute($sql) != false){
            $results = "La limpieza fue exitosa <br>";
          }
          else{
            $results = "La limpieza no pudo realizarse <br>";
          }
          $results = $results . "Threads: ". \Processing::get_threads()[0]["processing"];
          echo $results;
        }

        if(Tools::getIsset('debug') && $parameters['debug']=='ResetNotifications'){
          $results="";
          $TABLE = "pending_notification_centry";
          $db = Db::getInstance();
          $sql = "UPDATE `" . _DB_PREFIX_ .$TABLE
                  . "` SET `processing` = 0";
          if($db->execute($sql) != false){
            $results = "Reinicio exitoso <br>";
          }
          else{
            $results = "Reinicio fallido <br>";
          }
          echo $results;
        }







        die();
    }
}
