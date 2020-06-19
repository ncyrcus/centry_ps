<?php

/**
 *
 *
 * @author Vanessa Guzman
 */
class Processing extends ObjectModel {
  private static $TABLE = "processing_notification";

  public function __construct() {
        parent::__construct();

    }

    public static function createTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . static::$TABLE . "`(
	    `processing` INT(2) NOT NULL

            )";
        return Db::getInstance()->execute($sql);
    }

    public static function dropTable() {
        $sql = "DROP TABLE `" . _DB_PREFIX_ . static::$definition['table'] . "`";
        return Db::getInstance()->Execute($sql);
    }

    public static function add_first_row(){
      $db = Db::getInstance();
      $query = new DbQuery();
      $query->select('*');
      $query->from(static::$TABLE);
      $query->limit("1");
      $cosa = empty($db->executeS($query));
      error_log($cosa);
      if ($cosa){
        $process = 0;
        $db = Db::getInstance();
        $sql = "INSERT INTO `" . _DB_PREFIX_ . static::$TABLE
                . "` (`processing`)"
                . " VALUES ('" . ((int) $process) . "')";
        return $db->execute($sql) != false;
      }
    }

    public static function add_thread(){
      $db = Db::getInstance();
      $sql = "UPDATE `" . _DB_PREFIX_ . static::$TABLE
              . "` SET `processing` = `processing` + 1";
      return $db->execute($sql) != false;
    }

    public static function minus_thread(){
      $db = Db::getInstance();
      $sql = "UPDATE `" . _DB_PREFIX_ . static::$TABLE
              . "` SET `processing` = `processing` - 1";
      return $db->execute($sql) != false;
    }

    public static function get_threads(){
      $db = Db::getInstance();
      $query = new DbQuery();
      $query->select('*');
      $query->from(static::$TABLE);
      $query->limit("1");
      return $db->executeS($query);
    }


}
