<?php

class Manufacturer extends ManufacturerCore {

    public $id_centry = null;
    private static $TABLE = "manufacturer_centry";

    public function __construct($id = null, $id_lang = null) {
        parent::__construct($id, $id_lang);
        if (isset($id)) {
            $this->id_centry = static::getCentryManufacturer($id);
        }
    }

    public function save($null_values = false, $auto_date = true) {
        return parent::save($null_values, $auto_date) &&
                $this->deleteIdCentry() &&
                $this->insertIdCentry();
    }

    /**
     * Deletes current object from database
     * 
     * @return bool True if delete was successful
     * @throws PrestaShopException
     */
    public function delete() {
        return parent::delete() && $this->deleteIdCentry();
    }

    /**
     * Elimina de la tabla de equivalencias el par de enteros id de PrestaShop
     * e id de Centry®.
     * 
     * @return bool
     * @throws PrestaShopDatabaseException
     */
    private function deleteIdCentry() {
        $sql = "DELETE FROM `" . _DB_PREFIX_ . static::$TABLE
                . "` WHERE id_manufacturer = " . ((int) $this->id);
        return Db::getInstance()->execute($sql) != false;
    }

    /**
     * Inserta en la tabla de equivalencias el par de enteros id de PrestaShop
     * e id de Centry®.
     * 
     * @return bool
     * @throws PrestaShopDatabaseException
     */
    private function insertIdCentry() {
        $db = Db::getInstance();
        $sql = "INSERT INTO `" . _DB_PREFIX_ . static::$TABLE
                . "` (`id_manufacturer`, `id_centry`)"
                . " VALUES (" . ((int) $this->id) . ", '"
                . $db->escape($this->id_centry) . "')";
        return $db->execute($sql) != false;
    }

    /**
     * Crea la tabla de equivalencias entre las combinaciones de PrestaShop y los de
     * Centry®.
     * 
     * @return bool
     */
    public static function createTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . static::$TABLE . "`(
	    `id_manufacturer` INT(10) UNSIGNED NOT NULL, 
        `id_centry` VARCHAR(200)  NOT NULL
        );
        ALTER TABLE `" . _DB_PREFIX_ . "manufacturer_centry"."` ADD FOREIGN KEY (`id_manufacturer`) REFERENCES `" . _DB_PREFIX_ . "manufacturer"."`(`id_manufacturer`) ON DELETE CASCADE ON UPDATE NO ACTION;
        ";
        return Db::getInstance()->execute($sql);
    }

    /**
     * Borra la tabla de equivalencias entre las Combinaciones de PrestaShop y los
     * de Centry®.
     * 
     * @return bool
     */
    public static function dropTable() {
        $sql = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . static::$TABLE . "`";
        return Db::getInstance()->execute($sql);
    }

    /**
     * Busca un producto segun el identificador de Centry®.
     * 
     * @param int $id_centry
     * @return \Manufacturer|bool
     */
    public static function findManufacturerByIdCentry($id_centry) {
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $query = new DbQuery();
        $query->select('id_manufacturer');
        $query->from(static::$TABLE);
        $query->where("id_centry = '" . $db->escape($id_centry) . "'");
        return ($id = $db->getValue($query)) ? new Manufacturer($id) : false;
    }

    /**
     * Busca una combinación segun el identificador de PrestaShop y retorna su id de Centry
     * 
     * @param int $id_manufacturer
     * @return (int) id_centry | boot
     */
    public static function getCentryManufacturer($id_manufacturer) {
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $query = new DbQuery();
        $query->select('id_centry');
        $query->from(static::$TABLE);
        $query->where("id_manufacturer = '" . $db->escape($id_manufacturer) . "'");
        return ($id = $db->getValue($query)) ? $id : false;
    }

}
