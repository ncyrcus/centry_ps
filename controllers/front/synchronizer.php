<?php

require_once _PS_MODULE_DIR_ . 'centry_ps/helpers/ProductQueue.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/helpers/OrderQueue.php';

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of synchronizer
 *
 * @author Elías Lama L.
 */
class Centry_psSynchronizerModuleFrontController extends FrontController {

    public function initContent() {
        //parent::initContent();
        $resource = filter_input(INPUT_GET, "resource");
        $id = filter_input(INPUT_GET, "id");

        ignore_user_abort(true);
        ob_start();
        //echo json_encode(array("resource" => $resource, "id" => $id));
        header("Status: 200 Accepted");
        header('Content-type: application/json; charset=utf-8');
        header('Content-Length: ' . ob_get_length());
        ob_end_flush();
        ob_flush();
        flush();

        switch ($resource) {
            case "product":
                (new \CentryModulePS\ProductQueue())->sync($id);
                break;
            case "order":
                (new \CentryModulePS\OrderQueue())->sync($id);
                break;
        }
        die();
    }

}
