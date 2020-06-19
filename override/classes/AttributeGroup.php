<?php

/**
 * Description of AttributeGroup
 *
 * @author Elías Lama L.
 */
class AttributeGroup extends AttributeGroupCore {

    public $field_centry = null;
    private static $TABLE = "attribute_group_centry";

    public function __construct($id = null, $id_lang = null, $id_shop = null) {
        parent::__construct($id, $id_lang, $id_shop);
        if (isset($id)) {
            $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
            $query = new DbQuery();
            $query->select('*');
            $query->from(static::$TABLE);
            $query->where("id_attribute_group = '" . $db->escape($this->id) . "'");
            $row = $db->getRow($query);
            $this->field_centry = $row["field_centry"];
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
                . "` WHERE id_attribute_group = " . ((int) $this->id);
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
        if (isset($this->field_centry) && trim($this->field_centry) != "") {
            $db = Db::getInstance();
            $sql = "INSERT INTO `" . _DB_PREFIX_ . static::$TABLE
                    . "` (`id_attribute_group`, `field_centry`)"
                    . " VALUES (" . ((int) $this->id) . ", '"
                    . $db->escape($this->field_centry) . "')";
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
	    `id_attribute_group` INT(11) NOT NULL, 
        `field_centry` VARCHAR(50) NOT NULL 
        );
        ALTER TABLE  " . _DB_PREFIX_ . "attribute_group_centry"." ADD UNIQUE INDEX (`field_centry`) ;
        ALTER TABLE `" . _DB_PREFIX_ . "attribute_group_centry"."` ADD FOREIGN KEY (`id_attribute_group`) REFERENCES `" . _DB_PREFIX_ . "attribute_group"."`(`id_attribute_group`) ON DELETE CASCADE ON UPDATE NO ACTION;
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
     * @param string $field_centry
     * @return \Product|bool
     */
    public static function findByFieldCentry($field_centry) {
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $query = new DbQuery();
        $query->select('id_attribute_group');
        $query->from(static::$TABLE);
        $query->where("field_centry = '" . $db->escape($field_centry) . "'");
        return ($id = $db->getValue($query)) ? new AttributeGroup($id) : false;
    }

}
