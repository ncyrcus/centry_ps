<?php

/**
 * Extension del producto basico, para que pueda manejar el identificador de
 * Centry®.
 *
 * @author Elías Lama L.
 */
class Product extends ProductCore {

    public $id_centry = null;
    private static $TABLE = "product_centry";
    public $id_centry_unique_variant = null;
    public $centry_category = null;

    public function __construct($id_product = null, $full = false, $id_lang = null, $id_shop = null, Context $context = null) {
        parent::__construct($id_product, $full, $id_lang, $id_shop, $context);
        if (isset($id_product)) {
            $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
            $query = new DbQuery();
            $query->select('*');
            $query->from(static::$TABLE);
            $query->where("id_product = '" . $db->escape($this->id) . "'");
            $row = $db->getRow($query);
            $this->id_centry = $row["id_centry"];
            $this->id_centry_unique_variant = $row["id_centry_unique_variant"];
            $this->centry_category = $row["centry_category"];
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
                $this->updateIdCentry();
    }

    public function updateIdCentry() {
          return $this->id_centry!= "" ? $this->deleteIdCentry() && $this->insertIdCentry() : true;
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
                . "` WHERE id_product = " . ((int) $this->id);
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
                . "` (`id_product`, `id_centry`, `id_centry_unique_variant`, `centry_category`)"
                . " VALUES (" . ((int) $this->id) . ", '"
                . $db->escape($this->id_centry) . "', '"
                . $db->escape($this->id_centry_unique_variant) . "', '"
                . $db->escape($this->centry_category) . "')";
        return $db->execute($sql) != false;
    }

    /**
     * Crea la tabla de equivalencias entre los productos de PrestaShop y los de
     * Centry®.
     *
     * @return bool
     */
    public static function createTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . static::$TABLE . "`(
	    `id_product` INT(10) UNSIGNED NOT NULL,
        `id_centry_unique_variant` VARCHAR(200),
        `id_centry` VARCHAR(200) NOT NULL ,
        `centry_category` VARCHAR(200) NOT NULL
        );
        ALTER TABLE  " . _DB_PREFIX_ . "product_centry"." ADD UNIQUE INDEX (`id_centry`) ;
        ALTER TABLE `" . _DB_PREFIX_ . "product_centry"."` ADD FOREIGN KEY (`id_product`) REFERENCES `" . _DB_PREFIX_ . "product"."`(`id_product`) ON DELETE CASCADE ON UPDATE NO ACTION;
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
    public static function findProductByIdCentry($id_centry) {
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $query = new DbQuery();
        $query->select('id_product');
        $query->from(static::$TABLE);
        $query->where("id_centry = '" . $db->escape($id_centry) . "'");
        return ($id = $db->getValue($query)) ? new Product($id) : false;
    }

}
