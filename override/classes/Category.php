<?php

/**
 * Extension de la categoría basica, para que pueda manejar el identificador de
 * Centry®.
 *
 * @author Elías Lama L.
 */
class Category extends CategoryCore {

    public $id_centry = null;
    private static $TABLE = "category_centry";

    public function __construct($id_category = null, $id_lang = null, $id_shop = null) {
        parent::__construct($id_category, $id_lang, $id_shop);
        if (isset($id_category)) {
            $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
            $query = new DbQuery();
            $query->select('*');
            $query->from(static::$TABLE);
            $query->where("id_category = '" . $db->escape($this->id) . "'");
            $row = $db->getRow($query);
            $this->id_centry = $row["id_centry"];
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
                $this->insertIdCentry() && $this->checkProducts();
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
                . "` WHERE id_category = " . ((int) $this->id);
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
                . "` (`id_category`, `id_centry`)"
                . " VALUES (" . ((int) $this->id) . ", '"
                . $db->escape($this->id_centry) . "')";
        return $db->execute($sql) != false;
    }

    private function checkProducts() {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'product_centry WHERE centry_category = "' . $this->id_centry . '"';
        if ( !empty($this->id_centry) && ($results = Db::getInstance()->ExecuteS($sql))){
            foreach ($results as $row) {
                $product = new Product($row["id_product"]);
                $product->id_category_default = $this->id;
                $product->addToCategories(array($this->id));
                $product->updateCategories(array($this->id));
                $product->save();
            }
        }
    }

    /**
     * Crea la tabla de equivalencias entre los productos de PrestaShop y los de
     * Centry®.
     * 
     * @return bool
     */
    public static function createTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . static::$TABLE . "`(
	    `id_category` INT(10) UNSIGNED NOT NULL, 
        `id_centry` VARCHAR(200) NOT NULL
        );
        ALTER TABLE `" . _DB_PREFIX_ . "category_centry"."` ADD FOREIGN KEY (`id_category`) REFERENCES `" . _DB_PREFIX_ . "category"."`(`id_category`) ON DELETE CASCADE ON UPDATE NO ACTION;
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
    public static function findCategoryByIdCentry($id_centry) {
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $query = new DbQuery();
        $query->select('id_category');
        $query->from(static::$TABLE);
        $query->where("id_centry = '" . $db->escape($id_centry) . "'");
        return ($id = $db->getValue($query)) ? new Category($id) : false;
    }

}
