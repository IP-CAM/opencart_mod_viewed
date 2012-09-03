<?php
class ControllerModuleViewed extends Controller
{
    private $module = "viewed";
    
    protected function index($setting)
    {
        $this->language->load('module/'.$this->module);
        
        $this->load->model('module/'.$this->module);
        $this->load->model('tool/image');
        
        $this->data["heading_title"] = $this->language->get('heading_title');
		$this->data["heading_title"] = $this->data["heading_title"][$setting["type"]];
		
		$this->data["button_cart"] = $this->language->get('button_cart');
        
        $this->data['products'] = array();
        
        if(!empty($this->request->get["route"]) && $this->request->get["route"] == 'product/product') {
            if(!empty($this->request->get["product_id"])) {
                eval('$this->model_module_'.$this->module.'->add'.$this->module.'Products($this->request->get["product_id"]);');
            }
        }
        
        eval('$products = $this->model_module_'.$this->module.'->get'.$this->module.'Products($setting["limit"], $setting["type"]);');
        
        foreach($products as $product) {
            if(($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
                $price = $this->currency->format($this->tax->calculate($product["price"], $product["tax_class_id"], $this->config->get('config_tax')));
            } else {
                $price = false;
            }
            
            $this->data["products"][] = array('product_id' => $product["product_id"],
                'thumb'   	 => $product["image"] ? $this->model_tool_image->resize($product["image"], $setting['image_width'], $setting['image_height']) : false,
                'name'    	 => $product["name"],
                'price'   	 => $price,
                'special' 	 => (float)$product["special"] ? $this->currency->format($this->tax->calculate($product["special"], $product["tax_class_id"], $this->config->get('config_tax'))) : false,
                'rating'     => $this->config->get('config_review_status') ? $product["rating"] : false,
                'reviews'    => sprintf($this->language->get('text_reviews'), (int)$product["reviews"]),
                'href'    	 => $this->url->link('product/product', 'product_id=' . $product["product_id"]),
			);
        }
        
        if(count($this->data["products"])) {
            if(file_exists(DIR_TEMPLATE.$this->config->get('config_template').'/template/module/'.$this->module.'.tpl')) {
    			$this->template = $this->config->get('config_template')."/template/module/".$this->module.".tpl";
    		} else {
    			$this->template = "default/template/module/".$this->module.".tpl";
    		}
    		
    		$this->render();
		}
    }
    
}