<?php

include_once('../../../../config/config.inc.php');
include_once('../../../../init.php');

(new AjaxCall())->__init();

class AjaxCall {

    private $getData;

    public function __init() {
        $this->getData = filter_input_array(INPUT_GET);
        if ($this->isValidRequest()) {
            $dispatcher = $this->getDispatcher();
            $dispatcher->execute();
        }
    }

    private function isValidRequest() {
        return isset($this->getData["dispatcher"]) &&
                $this->getData["dispatcher"] != "AbstractDispatcher" &&
                file_exists(__DIR__ . "/dispatcher/" . $this->getData["dispatcher"] . ".php");
    }

    /**
     * 
     * @return \CentryAjax\AbstractDispatcher
     */
    private function getDispatcher() {
        $dispatcherClassName = "\\CentryAjax\\" . $this->getData["dispatcher"];
        include __DIR__ . "/dispatcher/{$this->getData["dispatcher"]}.php";
        return new $dispatcherClassName();
    }

}
