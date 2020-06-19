<?php

class Combination extends CombinationCore {

    public $id_centry = null;
    public $created_at = null;
    public $updated_at = null;
    public $sku = null;
    private static $TABLE = "combination_centry";

    public function __construct($id = null, $id_lang = null, $id_shop = null) {
        parent::__construct($id, $id_lang, $id_shop);
        if (isset($id)) {
            $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
            $query = new DbQuery();
            $query->select('*');
            $query->from(static::$TABLE);
            $query->where("id_combination = '" . $db->escape($this->id) . "'");
            $row = $db->getRow($query);
            $this->id_centry = $row["id_centry"];
            $this->created_at = $row["created_at"];
            $this->updated_at = $row["updated_at"];
            $this->sku = $row["sku"];
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

    public function updateIdCentry() {
        return $this->deleteIdCentry() &&
                $this->insertIdCentry();
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
                . "` WHERE id_combination = " . ((int) $this->id);
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
                . "` (`id_combination`, `id_centry`, `created_at`, `updated_at`,`sku` )"
                . " VALUES (" . ((int) $this->id) . ", '"
                . $db->escape($this->id_centry) . "', '"
                . $db->escape($this->created_at) . "', '"
                . $db->escape($this->created_at) . "', '"
                . $db->escape($this->sku) . "')";
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
	    `id_combination` INT(10) UNSIGNED NOT NULL,
	    `id_centry` VARCHAR(200) NOT NULL,
            `created_at` VARCHAR(200) NOT NULL,
            `updated_at` VARCHAR(200) NOT NULL,
            `sku` VARCHAR(200) NOT NULL
        );
        ALTER TABLE  " . _DB_PREFIX_ . "combination_centry"." ADD UNIQUE INDEX (`id_centry`) ;
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
     * @return \Combination|bool
     */
    public static function findCombinationByIdCentry($id_centry) {
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $query = new DbQuery();
        $query->select('id_combination');
        $query->from(static::$TABLE);
        $query->where("id_centry = '" . $db->escape($id_centry) . "'");
        return ($id = $db->getValue($query)) ? new Combination($id) : false;
    }

    public static function findCombinationBySku($sku) {
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $query = new DbQuery();
        $query->select('id_combination');
        $query->from(static::$TABLE);
        $query->where("sku = '" . $db->escape($sku) . "'");
        return ($id = $db->getValue($query)) ? new Combination($id) : false;
    }

}
