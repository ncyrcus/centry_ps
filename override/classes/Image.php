<?php

/**
 * Description of Image
 *
 * @author tortita
 */
class Image extends ImageCore {

    public $id_centry = null;
    private static $TABLE = "image_centry";
    public $cover = null;
    public $content_type = null;
    public $file_name = null;
    public $file_size = null;
    public $created_at = null;
    public $updated_at = null;
    public $fingerprint = null;
    public $url = null;

    public function __construct($id = null, $id_lang = null) {
        parent::__construct($id, $id_lang);
        if (isset($id)) {
            $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
            $query = new DbQuery();
            $query->select('*');
            $query->from(static::$TABLE);
            $query->where("id_image = '" . $db->escape($this->id) . "'");
            $row = $db->getRow($query);
            $this->id_centry = $row["id_centry"];
            $this->cover = $row["cover"];
            $this->content_type = $row["content_type"];
            $this->file_name = $row["file_name"];
            $this->file_size = $row["file_size"];
            $this->fingerprint = $row["fingerprint"];
            $this->created_at = $row["created_at"];
            $this->updated_at = $row["updated_at"];
            $this->url = $row["url"];
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
                . "` WHERE id_image = " . ((int) $this->id);
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
                . "` (`id_image`, `id_centry`, `cover`, `content_type`,"
                . " `file_name`, `file_size`, `fingerprint`, `created_at`,"
                . " `updated_at`, `url`)"
                . " VALUES (" . ((int) $this->id) . ", '"
                . $db->escape($this->id_centry) . "', '"
                . $db->escape($this->cover) . "', '"
                . $db->escape($this->content_type) . "', '"
                . $db->escape($this->file_name) . "', '"
                . $db->escape($this->file_size) . "', '"
                . $db->escape($this->fingerprint) . "', '"
                . $db->escape($this->created_at) . "', '"
                . $db->escape($this->updated_at) . "', '"
                . $db->escape($this->url). "')";
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
	    `id_image` INT(10) UNSIGNED NOT NULL, 
	    `id_centry` VARCHAR(200) NOT NULL ,
            `cover` BOOLEAN NOT NULL,
            `content_type` VARCHAR(200) NOT NULL,
            `file_name` VARCHAR(200) NOT NULL,
            `file_size` VARCHAR(200) NOT NULL,
            `fingerprint` VARCHAR(200) NOT NULL,
            `created_at` VARCHAR(200) NOT NULL,
            `updated_at` VARCHAR(200) NOT NULL,
            `url` VARCHAR(200) NOT NULL
        );
        ALTER TABLE  " . _DB_PREFIX_ . "image_centry"." ADD UNIQUE INDEX (`id_centry`) ;
        ALTER TABLE `" . _DB_PREFIX_ . "image_centry"."` ADD FOREIGN KEY (`id_image`) REFERENCES `" . _DB_PREFIX_ . "image"."`(`id_image`) ON DELETE CASCADE ON UPDATE NO ACTION;
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
    public static function findImageByIdCentry($id_centry) {
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $query = new DbQuery();
        $query->select('id_image');
        $query->from(static::$TABLE);
        $query->where("id_centry = '" . $db->escape($id_centry) . "'");
        return ($id = $db->getValue($query)) ? new Image($id) : false;
    }

}
