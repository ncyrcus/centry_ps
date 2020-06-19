<?php

/**
 * Extension de la órder básica, para que pueda manejar el identificador de
 * Centry®.
 *
 * @author Paulo Sandoval S.
 */
class Order extends OrderCore {

    public $id_centry = null;
    private static $TABLE = "order_centry";

    public function __construct($id = null, $id_lang = null) {
        parent::__construct($id, $id_lang);
        if (isset($id)) {
            $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
            $query = new DbQuery();
            $query->select('*');
            $query->from(static::$TABLE);
            $query->where("id_order = '" . $db->escape($this->id) . "'");
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
                $this->updateIdCentry();
    }

    public function updateIdCentry() {
        return $this->id_centry != "" ?  $this->deleteIdCentry() && $this->insertIdCentry() : true;
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
                . "` WHERE id_order = " . ((int) $this->id);
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
                . "` (`id_order`, `id_centry`)"
                . " VALUES (" . ((int) $this->id) . ", '"
                . $db->escape($this->id_centry) . "')";
        return $db->execute($sql) != false;
    }

    /**
     * Crea la tabla de equivalencias entre las órdenes de PrestaShop y las de
     * Centry®.
     * 
     * @return bool
     */
    public static function createTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . static::$TABLE . "`(
	    `id_order` INT(10) UNSIGNED NOT NULL, 
        `id_centry` VARCHAR(200) NOT NULL
        );
        ALTER TABLE  " . _DB_PREFIX_ . "order_centry"." ADD UNIQUE INDEX (`id_centry`) ;
        ALTER TABLE `" . _DB_PREFIX_ . "order_centry"."` ADD FOREIGN KEY (`id_order`) REFERENCES `" . _DB_PREFIX_ . "orders"."`(`id_order`) ON DELETE CASCADE ON UPDATE NO ACTION;
        ";
        return Db::getInstance()->execute($sql);
    }

    /**
     * Borra la tabla de equivalencias entre las órdenes de PrestaShop y las de
     * Centry®.
     * 
     * @return bool
     */
    public static function dropTable() {
        $sql = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . static::$TABLE . "`";
        return Db::getInstance()->execute($sql);
    }

    /**
     * Busca una orden según el identificador de Centry®.
     * 
     * @param int $id_centry
     * @return \Order|bool
     */
    public static function findOrderByIdCentry($id_centry) {
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $query = new DbQuery();
        $query->select('id_order');
        $query->from(static::$TABLE);
        $query->where("id_centry = '" . $db->escape($id_centry) . "'");
        return ($id = $db->getValue($query)) ? new Order($id) : false;
    }

    /**
     * Retorna todas las ordenes que se han registrado con Centry®.
     * 
     * @author Nicolás Orellana
     * @return \ListOrders
     */
    public static function getOrdersWithIdCentry() {
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $query = new DbQuery();
        $query->select('*');
        $query->from(static::$TABLE);
        $orders = $db->executeS($query);
        $ordersResult = array();
        foreach ($orders as $order) {
            $ordersResult[] = new Order($order['id_order']);
        }
        return $ordersResult;
    }

}
