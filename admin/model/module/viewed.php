<?php class ModelmoduleViewed extends Model
{
    
    public function createTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `".DB_PREFIX."customer_to_product` (
                `customer_id` INT( 11 ) NULL DEFAULT NULL ,
                `customer_ip` VARCHAR(15) NULL DEFAULT NULL ,
                `customer_php_sessid` VARCHAR(40) NULL DEFAULT NULL ,
                `product_id` INT( 11 ) NOT NULL ,
                `date_added` DATETIME NOT NULL ,
                INDEX ( `product_id` )
            ) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_bin";
        
        $query = $this->db->query($sql);
        
        return true;
    }
    
}