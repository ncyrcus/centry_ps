<?php

namespace CentryAjax;

/**
 * Description of AbstractDispatcher
 *
 * @author Elías Lama L.
 */
abstract class AbstractDispatcher {

    protected $getData;
    protected $postData;

    public function __construct() {
        $this->getData = filter_input_array(INPUT_GET);
        $this->postData = filter_input_array(INPUT_POST);
    }

    public abstract function execute();

    protected function isEmployeeLegedIn() {
        $cookie = new \Cookie('psAdmin');
        return $cookie->id_employee > 0;
    }

}

class Response {

    const CODE_200 = 200;
    const CODE_401 = 401;
    const CODE_500 = 500;
    const CONTENT_TYPE_JSON = "application/json";
    const CONTENT_TYPE_HTML = "text/html";

    public $code;
    public $contentType;
    public $body;

    function __construct($body, $code = Response::CODE_200, $contentType = Response::CONTENT_TYPE_JSON) {
        $this->code = $code;
        $this->contentType = $contentType;
        $this->body = $body;
    }

}
