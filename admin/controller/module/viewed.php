<?php class ControllerModuleViewed extends Controller {
    
    private $module = "viewed";
    private $error  = array();
    
    public function index()
    {
        $this->load->language('module/'.$this->module);
        
        $this->load->model('design/layout');
        $this->load->model('setting/setting');
        
        if(($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting($this->module, $this->request->post);
            
            $this->cache->delete('product.'.$this->module);
            
            $this->session->data["success"] = $this->language->get('text_success');
            
            $this->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'));
		}
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $data = array();
        $data["module_name"] = $this->module;
        $data["heading_title"] = $this->language->get('heading_title');
        $data["error_warning"] = isset($this->error["warning"]) ? $this->error["warning"] : null;
        
        $data["action"] = $this->url->link('module/'.$this->module, 'token=' . $this->session->data["token"], 'SSL');
        $data["cancel"] = $this->url->link('extension/module', 'token=' . $this->session->data["token"], 'SSL');
        
        $data["breadcrumbs"]   = array();
        $data["breadcrumbs"][] = array('href' => $this->url->link('common/home', 'token='.$this->session->data["token"], 'SSL'),
            'text'      => $this->language->get('text_home'),
            'separator' => false);
        $data["breadcrumbs"][] = array('href' => $this->url->link('extension/module', 'token='.$this->session->data["token"], 'SSL'),
            'text'      => $this->language->get('text_module'),
            'separator' => ' :: ');
        $data["breadcrumbs"][] = array('href' => $this->url->link('module/'.$this->module, 'token='.$this->session->data["token"], 'SSL'),
            'text'      => $this->language->get('heading_title'),
            'separator' => ' :: ');
        
        $data["button_save"]       = $this->language->get('button_save');
        $data["button_cancel"]     = $this->language->get('button_cancel');
        $data["button_add_module"] = $this->language->get('button_add_module');
		$data["button_remove"]     = $this->language->get('button_remove');
        
        $data["entry_limit"]      = $this->language->get('entry_limit');
        $data["entry_image"]      = $this->language->get('entry_image');
        $data["entry_layout"]     = $this->language->get('entry_layout');
        $data["entry_position"]   = $this->language->get('entry_position');
        $data["entry_type"]       = $this->language->get('entry_type');
        $data["entry_status"]     = $this->language->get('entry_status');
        $data["entry_sort_order"] = $this->language->get('entry_sort_order');
        
        $data["text_enabled"]        = $this->language->get('text_enabled');
		$data["text_disabled"]       = $this->language->get('text_disabled');
		$data["text_content_top"]    = $this->language->get('text_content_top');
		$data["text_content_bottom"] = $this->language->get('text_content_bottom');
		$data["text_column_left"]    = $this->language->get('text_column_left');
		$data["text_column_right"]   = $this->language->get('text_column_right');
        $data["text_lastviewed"]     = $this->language->get('text_lastviewed');
        $data["text_othersbuyed"]    = $this->language->get('text_othersbuyed');
        $data["text_othersviewed"]   = $this->language->get('text_othersviewed');
        
        $data["error_warning"] = isset($this->error["warning"]) ? $this->error["warning"] : null;
        $data["error_limit"]   = isset($this->error["limit"]) ? $this->error["limit"] : null;
        $data["error_image"]   = isset($this->error["image"]) ? $this->error["image"] : null;
        
        $data["modules"] = array();
        $data["layouts"] = $this->model_design_layout->getLayouts();
        
        if(isset($this->request->post[$this->module."_module"])) {
            $data["modules"] = $this->request->post[$this->module."_module"];
        } elseif($this->config->get($this->module.'_module')) { 
            $data["modules"] = $this->config->get($this->module.'_module');
        }
        
        if(count($data["modules"]) == 0) {
            $data["modules"][] = array('limit' => 6,
                'image_width'  => 80,
                'image_height' => 80,
                'layout_id'    => 2,
                'position'     => null,
                'type'         => null,
                'status'       => 1,
                'sort_order'   => null);
        }
        
        $this->children = array('common/header', 'common/footer');
        $this->data     = $data;
        $this->template = "module/".$this->module.".tpl";
		
        $this->response->setOutput($this->render(true), $this->config->get('config_compression'));
    }
    
    public function install()
    {
        $this->load->model('module/'.$this->module);
        
        eval('$this->model_module_'.$this->module.'->createTable();');
    }
    
    private function validate()
    {
        if(!$this->user->hasPermission('modify', 'module/'.$this->module)) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if(isset($this->request->post[$this->module."_module"])) {
            foreach($this->request->post[$this->module."_module"] as $key => $value) {
                if(!$value['limit']) {
                    $this->error['limit'][$key] = $this->language->get('error_limit');
                }
                
                if(!$value['image_width'] || !$value['image_height']) {
                    $this->error['image'][$key] = $this->language->get('error_image');
                }
            }
        }
		
        if(!$this->error) {
            return true;
        } else {
            return false;
        }
	}
    
}