<?php
class ModelModuleViewed extends Model {
    
    private $module = "viewed";
    
    public function addViewedProducts($product_id)
    {
        $customer_id         = null;
        $customer_ip         = $this->db->escape($this->request->server['REMOTE_ADDR']);
        $customer_php_sessid = null;
        
        if($this->customer->isLogged()) {
            $customer_id = $this->customer->getId();
        }
        
        if(isset($this->request->server['HTTP_COOKIE'])) {
            foreach(explode('; ', $this->request->server['HTTP_COOKIE']) as $cookie_item) {
                $cookie_item = explode('=', $cookie_item);
                
                if(!empty($cookie_item[0]) && $cookie_item[0] == 'PHPSESSID' && !empty($cookie_item[1])) {
                    $customer_php_sessid = $cookie_item[1];
                }
            }
        }
        
        $sql = "INSERT INTO `".DB_PREFIX."customer_to_product`
            SET
                `customer_id` = ".(empty($customer_id) ? "NULL" : $customer_id).",
                `customer_ip` = ".(empty($customer_ip) ? "NULL" : "'".$customer_ip."'").",
                `customer_php_sessid` = ".(empty($customer_php_sessid) ? "NULL" : "'".$customer_php_sessid."'").",
                `product_id` = ".$product_id.",
                `date_added` = NOW()";
        
        if($this->db->query($sql) === false) return false;
        
        $this->cache->delete('product.'.$this->module);
        
        return true;
    }
    
    public function getViewedProducts($limit, $type = null)
    {
        $this->load->model('catalog/product');
        
        $limit = (int)$limit;
        
        $language_id         = (int)$this->config->get('config_language_id');
        $store_id            = (int)$this->config->get('config_store_id');
        $customer_id         = 0;
        $customer_group_id   = $this->config->get('config_customer_group_id');
        $customer_ip         = $this->db->escape($this->request->server['REMOTE_ADDR']);
        $customer_php_sessid = null;
        $product_id          = 0;
        
        if($this->customer->isLogged()) {
            $customer_id       = $this->customer->getId();
            $customer_group_id = $this->customer->getCustomerGroupId();
        }
        
        if(isset($this->request->server["HTTP_COOKIE"])) {
            foreach(explode('; ', $this->request->server["HTTP_COOKIE"]) as $cookie_item) {
                $cookie_item = explode('=', $cookie_item);
                
                if(!empty($cookie_item[0]) && $cookie_item[0] == 'PHPSESSID' && !empty($cookie_item[1])) {
                    $customer_php_sessid = $cookie_item[1];
                }
            }
        }
        
        if(!empty($this->request->get["product_id"])) {
            $product_id = $this->request->get["product_id"];
        }
        
        $cache_data = $this->cache->get('product.'.$this->module.'.'.$type.'-'.$language_id.'.'.$store_id.'.'.$customer_group_id.'.'.$product_id.'.'.$limit);
        
        if(!$cache_data) {
            $cache_data = array();
            
            switch($type) {
                case "lastviewed" :
                    $sql = "SELECT p.`product_id`, MAX(c2p.`date_added`) AS date_added
                        FROM `".DB_PREFIX."customer_to_product` c2p
                            LEFT JOIN `".DB_PREFIX."product` p ON c2p.`product_id` = p.`product_id`
                            LEFT JOIN `".DB_PREFIX."product_to_store` p2s ON p.`product_id` = p2s.`product_id`
                        WHERE p.`status` = '1'
                        AND p.`date_available` <= '".date('Y-m-d')."'
                        AND p2s.`store_id` = '".$store_id."'
                        AND (
                            c2p.`customer_id` = ".$customer_id."
                            OR (
                                c2p.`customer_ip` = '".$customer_ip."'
                                AND c2p.`customer_php_sessid` = '".$customer_php_sessid."'
                            )
                        )
                        AND c2p.`product_id` != ".$product_id."
                        GROUP BY p.`product_id`
                        ORDER BY date_added DESC
                        LIMIT ".(int)$limit;
                    break;
                case "othersbuyed" :
                    $sql = "SELECT p.`product_id`, COUNT(op.`order_id`) AS solded
                        FROM `".DB_PREFIX."product` p
                            INNER JOIN `".DB_PREFIX."product_to_store` p2s ON p.`product_id` = p2s.`product_id`
                            INNER JOIN `".DB_PREFIX."order_product` op ON p.`product_id` = op.`product_id`
                        WHERE p.`status` = '1'
                        AND p.`date_available` <= '".date('Y-m-d')."'
                        AND p2s.`store_id` = '".$store_id."'
                        AND op.`order_id` IN (
                            SELECT op_sub.`order_id`
                            FROM `".DB_PREFIX."order` o_sub
                                INNER JOIN `".DB_PREFIX."order_product` op_sub ON o_sub.`order_id` = op_sub.`order_id`
                            WHERE o_sub.`store_id` = ".$store_id."
                            AND o_sub.`order_status_id` > 0
                            AND op_sub.`product_id` = ".$product_id."
                        )
                        AND op.`product_id` != ".$product_id."
                        GROUP BY p.`product_id`
                        ORDER BY solded DESC
                        LIMIT ".(int)$limit;
                    break;
                case "othersviewed" :
                    $sql = "SELECT c2p_main.`product_id`
                        FROM `".DB_PREFIX."customer_to_product` c2p_main
                            LEFT JOIN `".DB_PREFIX."product` p ON c2p_main.`product_id` = p.`product_id`
                            LEFT JOIN `".DB_PREFIX."product_to_store` p2s ON p.`product_id` = p2s.`product_id`
                            INNER JOIN `".DB_PREFIX."customer_to_product` c2p ON (
                                c2p_main.`customer_id` = c2p.`customer_id`
                                OR (
                                    c2p_main.`customer_ip` = c2p.`customer_ip`
                                    AND c2p_main.`customer_php_sessid` = c2p.`customer_php_sessid`
                                )
                            )
                        WHERE p.`status` = '1'
                        AND p.`date_available` <= '".date('Y-m-d')."'
                        AND c2p_main.`product_id` != ".$product_id."
                        AND p2s.`store_id` = '".$store_id."'
                        AND c2p.`product_id` = ".$product_id."
                        AND (
                            c2p.`customer_id` != ".$customer_id."
                            AND c2p.`customer_ip` != '".$customer_ip."'
                            AND c2p.`customer_php_sessid` != '".$customer_php_sessid."'
                        )
                        GROUP BY p.`product_id`
                        ORDER BY p.`viewed` DESC
                        LIMIT ".(int)$limit;
                    break;
            }
            
            $query = $this->db->query($sql);
            
            foreach($query->rows as $result) {
                $cache_data[$result["product_id"]] = $this->model_catalog_product->getProduct($result["product_id"]);
            }
            
            $this->cache->set('product.'.$this->module.'.'.$type.'-'.$language_id.'.'.$store_id.'.'.$customer_group_id.'.'.$product_id.'.'.$limit, $cache_data);
        }
        
        return $cache_data;
    }
    
}