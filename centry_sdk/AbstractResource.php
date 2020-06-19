<?php

namespace CentrySDK;

require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/OAuth2/Client.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/OAuth2/GrantType/IGrantType.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/OAuth2/GrantType/AuthorizationCode.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/centry_sdk/OAuth2/GrantType/RefreshToken.php';
include_once _PS_MODULE_DIR_ . 'centry_ps/include/utils.php';
require_once _PS_MODULE_DIR_ . 'centry_ps/classes/Log.php';

/**
 * Description of AbstractResource
 *
 * @author Elías Lama L.
 */
abstract class AbstractResource {

    const CENTRY_DEVELOPMENT = "http://localhost:3000";
    const CENTRY_TEST = "https://test.centry.cl";
    const CENTRY_PRODUCTION = "https://www.centry.cl";
//    // Desarrollo
//    const CENTRY_ENVIRONMENT = AbstractResource::CENTRY_DEVELOPMENT;
//    const PRE_URL_RESOURCE = "http://localhost:3000/conexion/v1/";
//    const AUTHORIZATION_ENDPOINT = "http://localhost:3000/oauth/authorize";
//    const TOKEN_ENDPOINT = "http://localhost:3000/oauth/token";
//    // Test
//    const CENTRY_ENVIRONMENT = AbstractResource::CENTRY_TEST;
//    const PRE_URL_RESOURCE = "https://test.centry.cl/conexion/v1/";
//    const AUTHORIZATION_ENDPOINT = "https://test.centry.cl/oauth/authorize";
//    const TOKEN_ENDPOINT = "https://test.centry.cl/oauth/token";
    // Producción
    const CENTRY_ENVIRONMENT = AbstractResource::CENTRY_PRODUCTION;
    const PRE_URL_RESOURCE = "https://www.centry.cl/conexion/v1/";
    const AUTHORIZATION_ENDPOINT = "https://www.centry.cl/oauth/authorize";
    const TOKEN_ENDPOINT = "https://www.centry.cl/oauth/token";

    private $client;
    protected $resource;
    protected $modelClass;

    public function __construct() {
        $this->__init();
        $modelFile = _PS_MODULE_DIR_ . "centry_ps/centry_sdk/model/$this->modelClass.php";
        if (file_exists($modelFile)) {
            require_once $modelFile;
        }
        $this->client = new \OAuth2\Client(\Configuration::get('CENTRY_API_APPID'), \Configuration::get('CENTRY_API_SECRETKEY'));
    }

    protected abstract function __init();

    /**
     * Recupera todos los elementos del recurso que tenga registrado Centry.
     * @return \CentrySDK\class
     */
    public function all() {
      $arr = array();
      $response = $this->doRequest($this->getUrlResource() . ".json");
      if ($response['code'] == 200) {
          foreach ($response['result'] as $item) {
              $class = "\\CentrySDK\\$this->modelClass";
              $arr[] = new $class($item);
          }
      }
      return $arr;
    }

    /**
     * Busca en Centry un recurso con el identificador que se pasa commo
     * parámetro.
     * @param type $id
     * @return \CentrySDK\class | false
     */
    public function findById($id) {
        if (trim($id) != "") {
            $response = $this->doRequest($this->getUrlResource() . "/$id.json");
            if ($response['code'] == 200) {
                $class = "\\CentrySDK\\$this->modelClass";
                return new $class($response['result']);
            }
        }
        return false;
    }

    /**
     * Ejecuta un request a la API de Centry cumpliendo con la verificación de usuario según el protocolo OAuth.
     * @param string $url URL donde se hará el request
     * @param array $parameters datos que se enviarán para ser procesados por Centry.
     * @param string $http_method GET | POST | PUT | DELETE | HEAD | PATCH por defecto es GET
     * @param array $http_headers por defecto un arreglo vacío
     * @param int $form_content_type done:
     * <ul>
     * <li>0: HTTP_FORM_CONTENT_TYPE_APPLICATION</li>
     * <li>1: HTTP_FORM_CONTENT_TYPE_MULTIPART</li>
     * </ul>
     * @return type
     */
    protected function doRequest($url, $parameters = array(), $http_method = \OAuth2\Client::HTTP_METHOD_GET, array $http_headers = array(), $form_content_type = \OAuth2\Client::HTTP_FORM_CONTENT_TYPE_APPLICATION) {
        $NUM_OF_ATTEMPTS = 5;
        $attempts = 0;
        do {
            try {
                ini_set("max_execution_time", 300);
                $this->client->setAccessToken(\Configuration::get('CENTRY_API_ACCESS_TOKEN'));
                $response = $this->client->fetch($url, $parameters, $http_method, $http_headers, $form_content_type);
                \Log::d(print_r($parameters,true) , "response : \n" . print_r($response,true));
                if ($response['code'] == 401) {
                    $this->refreshAccessToken();
                    $this->client->setAccessToken(\Configuration::get('CENTRY_API_ACCESS_TOKEN'));
                    $response = $this->client->fetch($url, $parameters, $http_method, $http_headers, $form_content_type);
                }
                return $response;
            } catch (\OAuth2\Exception $ex) {
                error_log("No se pudo establecer comunicación con Centry.\n" . $ex->getMessage() . "\n" . $ex->getTraceAsString());
                \Log::d("No se pudo establecer comunicación con Centry", $ex->getMessage() . "\n" . $ex->getTraceAsString());
                $attempts++;
                sleep(1);
                continue;
            }
        } while ($attempts < $NUM_OF_ATTEMPTS);
    }

    public function requestCode() {
        $auth_url = $this->client->getAuthenticationUrl(static::AUTHORIZATION_ENDPOINT, $this->getRedirectURI());
        header('Location: ' . $auth_url);
        die('Redirect');
    }

    /**
     * Solicita a Centry que valide el código de autorización. Si es válido,
     * Centry respondera con un código 200 e informando los "access_token" y
     * "refresh_token" los cuales son almacenados en variables de confirugación.
     * @param string $code Código de autorización que tiene que ser validado en
     * Centry.
     * @return bool <code>true</code> si todo salió bien o <cocde>false</code>
     * en caso contrario.
     */
    public function requestAccessToken($code) {
        $params = array('code' => $code, 'redirect_uri' => $this->getRedirectURI());
        $response = $this->client->getAccessToken(static::TOKEN_ENDPOINT, \OAuth2\Client::GRANT_TYPE_AUTH_CODE, $params);
        if ($response["code"] == "200") {
            \Configuration::updateValue('CENTRY_API_ACCESS_TOKEN', $response['result']['access_token']);
            \Configuration::updateValue('CENTRY_API_REFRESH_TOKEN', $response['result']['refresh_token']);
        }
        return $response["code"] == "200";
    }

    /**
     * Solicita refrescar el "acces_token" usando el "refresh_token". Si Centry
     * lo autoriza, responde con un código 200 y con los dos tokens que son
     * almacenados en variables de configuración.
     * @return bool <code>true</code> si todo salió bien o <cocde>false</code>
     * en caso contrario.
     */
    public function refreshAccessToken() {
        $params = array('refresh_token' => \Configuration::get('CENTRY_API_REFRESH_TOKEN'), 'redirect_uri' => $this->getRedirectURI());
        $response = $this->client->getAccessToken(static::TOKEN_ENDPOINT, \OAuth2\Client::GRANT_TYPE_REFRESH_TOKEN, $params);
        if ($response["code"] == "200") {
            \Configuration::updateValue('CENTRY_API_ACCESS_TOKEN', $response['result']['access_token']);
            \Configuration::updateValue('CENTRY_API_REFRESH_TOKEN', $response['result']['refresh_token']);
        }
        return $response["code"] == "200";
    }

    /**
     * Genera la URI que se le tiene que informar a Centry para hacer el
     * callback.
     * @return string URI para callback de OAuth.
     */
    public function getRedirectURI() {
        return \Tools::getShopDomainSsl(true) == "http://localhost" && static::CENTRY_ENVIRONMENT != static::CENTRY_DEVELOPMENT ?
                "urn:ietf:wg:oauth:2.0:oob" :
                \Tools::getShopDomainSsl(true) . __PS_BASE_URI__ . ((bool) \Configuration::get('PS_REWRITING_SETTINGS') ? "module/centry_ps/authorize" : "index.php?fc=module&module=centry_ps&controller=authorize");
    }

    /**
     * Genera la URL base a la cual se debe acceder para obtener o envíar datos
     * del recurso.
     * @return string
     */
    protected function getUrlResource() {
        return static::PRE_URL_RESOURCE . $this->resource;
    }

}
