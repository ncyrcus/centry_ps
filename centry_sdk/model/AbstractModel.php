<?php

namespace CentrySDK;

/**
 * Description of AbstractModel
 *
 * @author Elías Lama L.
 */
abstract class AbstractModel {

    public abstract function __construct($array = null);

    /**
     * Entrega una representación del producto en forma de arreglo de manera
     * que lo pueda manejar la API de Centry.
     * @return array
     */
    public function toParametersArray() {
        $modelName = $this->fromCamelCase(str_replace("CentrySDK\\", "", get_class($this)));
        $params = array($modelName => json_decode(json_encode($this), true));
        if (key_exists("id_prestashop", $params[$modelName])) {
            unset($params[$modelName]["id_prestashop"]);
        }
        if (key_exists("_id", $params[$modelName]) && !$params[$modelName]["_id"]) {
            unset($params[$modelName]["_id"]);
        }
        return $params;
    }

    private function fromCamelCase($input) {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }

}
