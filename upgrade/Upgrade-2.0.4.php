<?php
require_once _PS_MODULE_DIR_ . 'centry_ps/classes/Log.php';
/*
* File: /upgrade/Upgrade-2_0_4.php
*/
  
function upgrade_module_2_0_4($module) {


  Log::d("modificando tablas");
  $success = Db::getInstance()->execute(
    'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'product_centry_tmp'.' SELECT * FROM  ' . _DB_PREFIX_ . 'product_centry'.';
    TRUNCATE TABLE  ' . _DB_PREFIX_ . 'product_centry'.';
    ALTER TABLE  ' . _DB_PREFIX_ . 'product_centry'.' ADD UNIQUE INDEX (`id_centry`) ;
    ALTER TABLE `' . _DB_PREFIX_ . 'product_centry'.'` CHANGE `id_product` `id_product` INT(10) UNSIGNED NOT NULL;
    ALTER TABLE `' . _DB_PREFIX_ . 'product_centry'.'` ADD FOREIGN KEY (`id_product`) REFERENCES `' . _DB_PREFIX_ . 'product'.'`(`id_product`) ON DELETE CASCADE ON UPDATE NO ACTION;
    INSERT IGNORE INTO  ' . _DB_PREFIX_ . 'product_centry'.' SELECT * from  ' . _DB_PREFIX_ . 'product_centry_tmp'.';'
  );
  if($success){
    Log::d("Tabla product modificada correctamente");
  }
  else{
    Log::d("Tabla product no se modificó");
  }

  $success2 = Db::getInstance()->execute(
    'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'order_centry_tmp'.' SELECT * FROM  ' . _DB_PREFIX_ . 'order_centry'.';
    TRUNCATE TABLE  ' . _DB_PREFIX_ . 'order_centry'.';
    ALTER TABLE  ' . _DB_PREFIX_ . 'order_centry'.' ADD UNIQUE INDEX (`id_centry`) ;
    ALTER TABLE `' . _DB_PREFIX_ . 'order_centry'.'` CHANGE `id_order` `id_order` INT(10) UNSIGNED NOT NULL;
    ALTER TABLE `' . _DB_PREFIX_ . 'order_centry'.'` ADD FOREIGN KEY (`id_order`) REFERENCES `' . _DB_PREFIX_ . 'orders'.'`(`id_order`) ON DELETE CASCADE ON UPDATE NO ACTION;
    INSERT IGNORE INTO  ' . _DB_PREFIX_ . 'order_centry'.' SELECT * from  ' . _DB_PREFIX_ . 'order_centry_tmp'.';'
  );
  if($success2){
    Log::d("Tabla order modificada correctamente");
  }
  else{
    Log::d("Tabla order no se modificó");
  }

  $success3 = Db::getInstance()->execute(
    'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'manufacturer_centry_tmp'.' SELECT * FROM  ' . _DB_PREFIX_ . 'manufacturer_centry'.';
    TRUNCATE TABLE  ' . _DB_PREFIX_ . 'manufacturer_centry'.';
    ALTER TABLE `' . _DB_PREFIX_ . 'manufacturer_centry'.'` CHANGE `id_manufacturer` `id_manufacturer` INT(10) UNSIGNED NOT NULL;
    ALTER TABLE `' . _DB_PREFIX_ . 'manufacturer_centry'.'` ADD FOREIGN KEY (`id_manufacturer`) REFERENCES `' . _DB_PREFIX_ . 'manufacturer'.'`(`id_manufacturer`) ON DELETE CASCADE ON UPDATE NO ACTION;
    INSERT IGNORE INTO  ' . _DB_PREFIX_ . 'manufacturer_centry'.' SELECT * from  ' . _DB_PREFIX_ . 'manufacturer_centry_tmp'.';'
  );
  if($success3){
    Log::d("Tabla manufacturer modificada correctamente");
  }
  else{
    Log::d("Tabla manufacturer no se modificó");
  }

  $success4 = Db::getInstance()->execute(
    'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'category_centry_tmp'.' SELECT * FROM  ' . _DB_PREFIX_ . 'category_centry'.';
    TRUNCATE TABLE  ' . _DB_PREFIX_ . 'category_centry'.';
    ALTER TABLE `' . _DB_PREFIX_ . 'category_centry'.'` CHANGE `id_category` `id_category` INT(10) UNSIGNED NOT NULL;
    ALTER TABLE `' . _DB_PREFIX_ . 'category_centry'.'` ADD FOREIGN KEY (`id_category`) REFERENCES `' . _DB_PREFIX_ . 'category'.'`(`id_category`) ON DELETE CASCADE ON UPDATE NO ACTION;
    INSERT IGNORE INTO  ' . _DB_PREFIX_ . 'category_centry'.' SELECT * from  ' . _DB_PREFIX_ . 'category_centry_tmp'.';'
  );
  if($success4){
    Log::d("Tabla category modificada correctamente");
  }
  else{
    Log::d("Tabla category no se modificó");
  }

  $success5 = Db::getInstance()->execute(
    'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'attribute_centry_tmp'.' SELECT * FROM  ' . _DB_PREFIX_ . 'attribute_centry'.';
    TRUNCATE TABLE  ' . _DB_PREFIX_ . 'attribute_centry'.';
    ALTER TABLE `' . _DB_PREFIX_ . 'attribute_centry'.'` CHANGE `id_attribute` `id_attribute` INT(11) NOT NULL;
    ALTER TABLE `' . _DB_PREFIX_ . 'attribute_centry'.'` ADD FOREIGN KEY (`id_attribute`) REFERENCES `' . _DB_PREFIX_ . 'attribute'.'`(`id_attribute`) ON DELETE CASCADE ON UPDATE NO ACTION;
    INSERT IGNORE INTO  ' . _DB_PREFIX_ . 'attribute_centry'.' SELECT * from  ' . _DB_PREFIX_ . 'attribute_centry_tmp'.';'
  );
  if($success5){
    Log::d("Tabla attribute modificada correctamente");
  }
  else{
    Log::d("Tabla attribute no se modificó");
  }

  $success6 = Db::getInstance()->execute(
    'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'attribute_group_centry_tmp'.' SELECT * FROM  ' . _DB_PREFIX_ . 'attribute_group_centry'.';
    TRUNCATE TABLE  ' . _DB_PREFIX_ . 'attribute_group_centry'.';
    ALTER TABLE  ' . _DB_PREFIX_ . 'attribute_group_centry'.' ADD UNIQUE INDEX (`field_centry`) ;
    ALTER TABLE `' . _DB_PREFIX_ . 'attribute_group_centry'.'` CHANGE `id_attribute_group` `id_attribute_group` INT(11) NOT NULL;
    ALTER TABLE `' . _DB_PREFIX_ . 'attribute_group_centry'.'` ADD FOREIGN KEY (`id_attribute_group`) REFERENCES `' . _DB_PREFIX_ . 'attribute_group'.'`(`id_attribute_group`) ON DELETE CASCADE ON UPDATE NO ACTION;
    INSERT IGNORE INTO  ' . _DB_PREFIX_ . 'attribute_group_centry'.' SELECT * from  ' . _DB_PREFIX_ . 'attribute_group_centry_tmp'.';'
  );
  if($success6){
    Log::d("Tabla attribute_group modificada correctamente");
  }
  else{
    Log::d("Tabla attribute_group no se modificó");
  }

  $success7 = Db::getInstance()->execute(
    'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'combination_centry_tmp'.' SELECT * FROM  ' . _DB_PREFIX_ . 'combination_centry'.';
    TRUNCATE TABLE  ' . _DB_PREFIX_ . 'combination_centry'.';
    ALTER TABLE  ' . _DB_PREFIX_ . 'combination_centry'.' ADD UNIQUE INDEX (`id_centry`) ;
    ALTER TABLE `' . _DB_PREFIX_ . 'combination_centry'.'` CHANGE `id_combination` `id_combination` INT(10) UNSIGNED NOT NULL;
    INSERT IGNORE INTO  ' . _DB_PREFIX_ . 'combination_centry'.' SELECT * from  ' . _DB_PREFIX_ . 'combination_centry_tmp'.';'
  );
  if($success7){
    Log::d("Tabla combination modificada correctamente");
  }
  else{
    Log::d("Tabla combination no se modificó");
  }

  $success8 = Db::getInstance()->execute(
    'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'image_centry_tmp'.' SELECT * FROM  ' . _DB_PREFIX_ . 'image_centry'.';
    TRUNCATE TABLE  ' . _DB_PREFIX_ . 'image_centry'.';
    ALTER TABLE  ' . _DB_PREFIX_ . 'image_centry'.' ADD UNIQUE INDEX (`id_centry`) ;
    ALTER TABLE `' . _DB_PREFIX_ . 'image_centry'.'` CHANGE `id_image` `id_image` INT(10) UNSIGNED NOT NULL;
    ALTER TABLE `' . _DB_PREFIX_ . 'image_centry'.'` ADD FOREIGN KEY (`id_image`) REFERENCES `' . _DB_PREFIX_ . 'image'.'`(`id_image`) ON DELETE CASCADE ON UPDATE NO ACTION;
    INSERT IGNORE INTO  ' . _DB_PREFIX_ . 'image_centry'.' SELECT * from  ' . _DB_PREFIX_ . 'image_centry_tmp'.';'
  );
  if($success8){
    Log::d("Tabla image modificada correctamente");
  }
  else{
    Log::d("Tabla image no se modificó");
  }

  return $success;

}