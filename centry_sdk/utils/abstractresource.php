<?php

/**
 * Define de manera genérica como un controlador atenderá las 4 acciones básicas
 * del CRUD de un recurso.
 *
 * @author Elías Lama L.
 */
abstract class Centry_psAbstractResource {

    //protected static $RESPONSE_OK = "ok";
    //protected static $RESPONSE_ERROR = "error";

    //public function initContent() {
    //    parent::initContent();
    //    $response = $this->getResponse();
    //    $this->prepararHeader($response);
    //    die(gzencode($this->cifrar($response)));
    //}

    /**
     * Evalúa el método HTTP que se utilizó para hacer el request y determina
     * que acción del CRUD se va a ejecutar. Finalmente retorna la respuesta
     * estperada como texto.
     *
     * @return string respuesta esperada al request.
     */
    //private function getResponse() {
    //    $method = $_SERVER['REQUEST_METHOD'];
//        $method = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
    //    $params = $this->getRequest();
        //error_log($method);
    //    switch ($method) {
    //        case "POST":
    //            $response = $this->create($params) ? static::$RESPONSE_OK : static::$RESPONSE_ERROR;
    //            break;
    //        case "GET":
    //            $response = ($array = $this->read($params["id_centry"])) ? json_encode($array) : static::$RESPONSE_ERROR;
    //            //error_log(print_r($response,true). " ". $method);
    //            break;
    //        case "PATCH":
    //            $response = $this->update($params) ? static::$RESPONSE_OK : static::$RESPONSE_ERROR;
    //            break;
    //        case "DELETE":
    //            $response = $this->delete($params["id_centry"]) ? static::$RESPONSE_OK : static::$RESPONSE_ERROR;
    //            break;
    //        default :
    //            $response = static::$RESPONSE_ERROR;
    //    }
    //    return $response;
    //}

    /**
     * Entrega los párametros y sus valores del request y los entrega como un
     * arreglo.
     *
     * @return array párametros que vienen en el request.
     */
    //private function getRequest() {
    //    $id_centry = filter_input(INPUT_GET, "id_centry");
    //    if (trim($id_centry) != "") {
    //        return array(
    //            "id_centry" => $this->descrifrar($id_centry)
    //        );
    //    }
    //    $cifrado = file_get_contents('php://input');
    //    return json_decode($this->descrifrar($cifrado), true);
    //}

    /**
     * Prepara el headder HTTP con el que se enviará la respuesta. Los posibles
     * casos son:
     * <ul>
     * <li>
     * <b>500 Internal Server Error</b><br/>
     * Si hubo un error.
     * </li>
     * <li>
     * <b>200 OK</b><br/>
     * En cualquier otro caso
     * </li>
     * </ul>
     *
     * @param string $response texto que irá en el contenido de la respuesta.
     */
    //private function prepararHeader($response) {
    //    header('Content-Encoding: gzip');
    //    header('Content-type: application/json; charset=utf-8');
    //    if ($response == static::$RESPONSE_ERROR) {
    //        header('HTTP/1.0 400 Bad Request');
    //    } else {
    //        header("HTTP/1.0 200 OK");
    //    }
    //}

    /**
     * Toma un texto encriptado y lo descrifra.
     *
     * @param string $txt texto a descifrar.
     * @return string texto descifrado.
     * @todo Este método no está haciendo el descifrado, sólo retorna el mismo
     * texto.
     */
    private function descrifrar($txt) {
        // TODO: Hacer la desencriptación.
        return $txt;
    }

    /**
     * Toma un texto y lo encripta.
     *
     * @param string $txt texto a cifrar.
     * @return string texto cifrado.
     * @todo Este método no está haciendo el cifrado, sólo retorna el mismo
     * texto.
     */
    private function cifrar($txt) {
        // TODO: Hacer la encriptación.
        return $txt;
    }

    /**
     * Determina si se debe crear o ctualizar un recurso.
     *
     * @param \Centry::Product $params
     * @return bool <code>true</code> si el recurso fue creado,
     * <code>false</code> en caso contrario.
     */
    public abstract function save($params);
    /**
     * Crea un recurso con los campos pasados como parámetro.
     *
     * @param array $params
     * @return bool <code>true</code> si el recurso fue creado,
     * <code>false</code> en caso contrario.
     */
    protected abstract function create($params);

    /**
     * Recupera toda la información del recurso que tenga por id de Centry® el
     * pasado como parámetro.
     *
     * @param int $id_centry
     * @return array|bool arreglo con todos los párametros de definien al
     * recurso o <code>false</code> en caso de ocurrir un problema.
     */
    protected abstract function read($id_centry);

    /**
     * Actualiza un recurso con los campos pasados como parámetro.
     *
     * @param array $params
     * @return bool <code>true</code> si el recurso fue actualizado,
     * <code>false</code> en caso contrario.
     */
    protected abstract function update($params);

    /**
     * Elimina el recurso que tenga por id de Centry® el pasado como parámetro.
     *
     * @param int $id_centry
     * @return boolean <code>true</code> si el recurso fue eliminado,
     * <code>false</code> en caso contrario.
     */
    protected abstract function delete($id_centry);

   

}
