<?php

/**
 *
 *
 * @author Elías Lama L. <elama@dcc.uchile.cl>
 * @copyright  2007-2016 Elías Lama L.
 */
class Log extends ObjectModel {

    const LEVEL_DEBUG = 0;
    const LEVEL_INFO = 1;
    const LEVEL_WARNING = 2;
    const LEVEL_ERROR = 3;

    /** @var int Log's message */
    public $level = null;

    /** @var string quote's state */
    public $title = null;

    /** @var string quote's state */
    public $content = null;

    /** @var string Object creation date */
    public $date_add;

    /** @var string Object last modification date */
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'centry_log',
        'primary' => 'id_log',
        'fields' => array(
            'level' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'copy_post' => false),
            'title' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 100),
            'content' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false),
        ),
    );

    public function __construct() {
        parent::__construct();

    }
    /**
     * Returns fields required for a quote in an array hash
     * @return array hash values
     */
    public static function getFieldsValidate() {
        $tmp_log = new Log();
        $out = $tmp_log->fieldsValidate;

        unset($tmp_log);
        return $out;
    }

    public function getFieldsRequiredDB() {
        $this->cacheFieldsRequiredDatabase(false);
        if (isset(self::$fieldsRequiredDatabase['Quote'])) {
            return self::$fieldsRequiredDatabase['Quote'];
        }
        return array();
    }

    public static function createTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . static::$definition['table'] . "`(
	    `id_log` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	    `level` INT(11),
	    `title` VARCHAR(100) NOT NULL,
	    `content` MEDIUMTEXT NOT NULL,
	    `date_add` datetime NOT NULL,
	    `date_upd` datetime NOT NULL
            )";
        return Db::getInstance()->Execute($sql);
    }

    public static function dropTable() {
        $sql = "DROP TABLE `" . _DB_PREFIX_ . static::$definition['table'] . "`";
        return Db::getInstance()->Execute($sql);
    }

    public static function d($title, $content = "") {
        self::log(LOG::LEVEL_DEBUG, $title, $content, "debug");
    }
    public static function dclean($registro) {
        $file =  _PS_MODULE_DIR_ . "centry_ps/$registro";
        file_put_contents($file, "");
    }

    public static function pro($title, $content = "") {
        self::log(LOG::LEVEL_INFO, $title, $content, "notifications_products");
    }
    public static function ord($title, $content = "") {
        self::log(LOG::LEVEL_INFO, $title, $content, "notifications_orders");
    }

    public static function w($title, $content = "") {
        self::log(LOG::LEVEL_WARNING, $title, $content);
    }

    public static function e($title, $content = "") {
        self::log(LOG::LEVEL_ERROR, $title, $content);
    }

    private static function log($level, $title, $content, $registro) {
        // (new Log($level, $title, $content))->save();
        // error_log("log test ");
        $file =  _PS_MODULE_DIR_ . "centry_ps/$registro";
        //Use the function is_file to check if the file already exists or not.
        $t=time();
        $contents = date("Y-m-d h:i:sa")." ".$title." :\n\t\t".$content."\n";
        if(!is_file($file)){
            //Some simple example content.
            //Save our content to the file.
            file_put_contents($file, $contents, FILE_APPEND | LOCK_EX);
        }else {
          file_put_contents($file, $contents, FILE_APPEND | LOCK_EX);
        }

  }

}
