<?php

/**
 * Description of Attribute
 *
 * @author Elías Lama L.
 */
class Attribute extends AttributeCore {

    public $id_centry = null;
    private static $TABLE = "attribute_centry";

    public function __construct($id = null, $id_lang = null, $id_shop = null) {
        parent::__construct($id, $id_lang, $id_shop);
        if (isset($id)) {
            $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
            $query = new DbQuery();
            $query->select('id_centry');
            $query->from(static::$TABLE);
            $query->where("id_attribute = '" . $db->escape($id) . "'");
            $this->id_centry = $db->getValue($query);
        }
    }

    /**
     * Saves current object to database (add or update)
     *
     * @param bool $null_values
     * @param bool $auto_date
     *
     * @return bool Insertion result
     * @throws PrestaShopException
     */
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
        return parent::delete() && $this->deleteIdCentry(); // deleteLite solo si no tiene hijos (?)
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
                . "` WHERE id_attribute = " . ((int) $this->id);
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
        if (isset($this->id_centry) && trim($this->id_centry) != "") {
            $db = Db::getInstance();
            $sql = "INSERT INTO `" . _DB_PREFIX_ . static::$TABLE
                    . "` (`id_attribute`, `id_centry`)"
                    . " VALUES (" . ((int) $this->id) . ", '"
                    . $db->escape($this->id_centry) . "')";
            return $db->execute($sql) != false;
        }
        return true;
    }

    /**
     * Crea la tabla de equivalencias entre los productos de PrestaShop y los de
     * Centry®.
     * 
     * @return bool
     */
    public static function createTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . static::$TABLE . "`(
	    `id_attribute` INT(11) NOT NULL, 
        `id_centry` VARCHAR(200) NOT NULL
        );
        ALTER TABLE `" . _DB_PREFIX_ . "attribute_centry"."` ADD FOREIGN KEY (`id_attribute`) REFERENCES `" . _DB_PREFIX_ . "attribute"."`(`id_attribute`) ON DELETE CASCADE ON UPDATE NO ACTION;
        ";
        return Db::getInstance()->execute($sql);
    }

    /**
     * Borra la tabla de equivalencias entre los productos de PrestaShop y los
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
     * @return \Product|bool
     */
    public static function findByIdCentry($id_centry) {
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $query = new DbQuery();
        $query->select('id_attribute');
        $query->from(static::$TABLE);
        $query->where("id_centry = '" . $db->escape($id_centry) . "'");
        return ($id = $db->getValue($query)) ? new Attribute($id) : false;
    }

}
