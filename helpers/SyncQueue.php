<?php

namespace CentryModulePS;

/**
 * Description of SyncQueue
 *
 * @author Elías Lama L.
 */
abstract class SyncQueue {

    private $key = null;
    private $sem_id;

    public function __construct() {
        $this->key = ftok(".", ".");
    }

    public function sync($id) {
        if ($this->push($id)) {
            $this->process($id);
            $this->pop($id);
        }
    }

    protected abstract function process($id);

    private function push($id) {
        $this->sem_id = sem_get($this->key, 1);
        sem_acquire($this->sem_id) or die('Error esperando al semaforo.');
        $queue = null;
        $wasAdded = false;
        if (\Configuration::get($this->getConfigurationKey())) {
            $queue = json_decode(\Configuration::get($this->getConfigurationKey()), true);
        }
        if (empty($queue)) {
            $queue = array();
        }
        // Si no estaba registrado o se registro hace más de 5 minutos.
        if (!isset($queue[$id]) || $queue[$id] < (time() - 300)) {
            $queue[$id] = time();
            \Configuration::updateValue($this->getConfigurationKey(), json_encode($queue));
            $wasAdded = true;
        }
        sem_release($this->sem_id) or die('Error liberando el semaforo');
        return $wasAdded;
    }

    private function pop($id) {
        $this->sem_id = sem_get($this->key, 1);
        sem_acquire($this->sem_id) or die('Error esperando al semaforo.');
        if (\Configuration::get($this->getConfigurationKey())) {
            $queue = json_decode(\Configuration::get($this->getConfigurationKey()), true);
            unset($queue[$id]);
            \Configuration::updateValue($this->getConfigurationKey(), json_encode($queue));
        }
        sem_release($this->sem_id) or die('Error liberando el semaforo');
    }

    private function getConfigurationKey() {
        return str_replace("\\", "_", get_class($this));
    }

    public static function asyncRequest( $id , $resource) {
        $url = _PS_BASE_URL_ . __PS_BASE_URI__ . "index.php?fc=module&module=centry_ps&controller=synchronizer&resource=$resource&id=$id";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1); // 1 segundo (no nos interesa la respueta).
        curl_exec($ch);
    }

}
