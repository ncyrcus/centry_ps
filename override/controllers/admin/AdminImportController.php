<?php

class AdminImportController extends AdminImportControllerCore {

    public static function copiarImagen($id_entity, $id_image = null, $url, $entity = 'products', $regenerate = true) {
        return static::copyImg($id_entity, $id_image, $url, $entity, $regenerate);
    }

}
