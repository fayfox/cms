<?php
namespace cms\modules\install\controllers;

use cms\library\InstallController;
use cms\services\CategoryService;
use cms\services\MenuService;
use fay\core\Db;
use fay\core\Request;
use fay\core\Response;

class DbController extends InstallController{
    
    public function __construct(){
        parent::__construct();
        
        $this->checkToken();
        
        $this->db = Db::getInstance();
    }
    
    public function createTables(){
        $prefix = $this->config->get('db.table_prefix');
        $charset = $this->config->get('db.charset');
        $sql = file_get_contents(FAYSOFT_PATH . 'cms/db/tables.sql');
        $sql = str_replace(array('{{$prefix}}', '{{$time}}', '{{$charset}}'), array($prefix, $this->current_time, $charset), $sql);
        $this->db->exec($sql, true);
        
        //安装日志
        file_put_contents(APPLICATION_PATH . 'runtimes/installed.lock', date('Y-m-d H:i:s [') . Request::getUserIP() . "]\r\ntables-completed");
        
        return Response::json(array(
            '_token'=>$this->getToken(),
        ));
    }
    
    public function setCities(){
        $prefix = $this->config->get('db.table_prefix');
        $sql = file_get_contents(FAYSOFT_PATH . 'cms/db/cities.sql');
        $sql = str_replace(array('{{$prefix}}', '{{$time}}'), array($prefix, $this->current_time), $sql);
        $this->db->exec($sql);//此表较大，且是较为固定的内容，就不拆分开逐条执行了
        
        //安装日志
        file_put_contents(APPLICATION_PATH . 'runtimes/installed.lock', "\r\ncities-completed", FILE_APPEND);
        
        return Response::json(array(
            '_token'=>$this->getToken(),
        ));
    }
    
    public function setRegions(){
        $prefix = $this->config->get('db.table_prefix');
        $sql = file_get_contents(FAYSOFT_PATH . 'cms/db/regions.sql');
        $sql = str_replace(array('{{$prefix}}', '{{$time}}'), array($prefix, $this->current_time), $sql);
        $this->db->exec($sql);//此表较大，且是较为固定的内容，就不拆分开逐条执行了
        
        //安装日志
        file_put_contents(APPLICATION_PATH . 'runtimes/installed.lock', "\r\nregions-completed", FILE_APPEND);
        
        return Response::json(array(
            '_token'=>$this->getToken(),
        ));
    }
    
    public function setCats(){
        $prefix = $this->config->get('db.table_prefix');
        $sql = file_get_contents(FAYSOFT_PATH . 'cms/db/cats.sql');
        $sql = str_replace(array('{{$prefix}}', '{{$time}}'), array($prefix, $this->current_time), $sql);
        $this->db->exec($sql, true);
        
        //安装日志
        file_put_contents(APPLICATION_PATH . 'runtimes/installed.lock', "\r\ncategoties-completed", FILE_APPEND);
        
        return Response::json(array(
            '_token'=>$this->getToken(),
        ));
    }
    
    public function setActions(){
        $prefix = $this->config->get('db.table_prefix');
        $sql = file_get_contents(FAYSOFT_PATH . 'cms/db/actions.sql');
        $sql = str_replace(array('{{$prefix}}', '{{$time}}'), array($prefix, $this->current_time), $sql);
        $this->db->exec($sql, true);
        
        //安装日志
        file_put_contents(APPLICATION_PATH . 'runtimes/installed.lock', "\r\nactions-completed", FILE_APPEND);
        
        return Response::json(array(
            '_token'=>$this->getToken(),
        ));
    }
    
    public function setMenus(){
        $prefix = $this->config->get('db.table_prefix');
        $sql = file_get_contents(FAYSOFT_PATH . 'cms/db/menus.sql');
        $sql = str_replace(array('{{$prefix}}', '{{$time}}'), array($prefix, $this->current_time), $sql);
        $this->db->exec($sql, true);
        
        //安装日志
        file_put_contents(APPLICATION_PATH . 'runtimes/installed.lock', "\r\nmenus-completed", FILE_APPEND);
        
        return Response::json(array(
            '_token'=>$this->getToken(),
        ));
    }
    
    public function setSystem(){
        $prefix = $this->config->get('db.table_prefix');
        $sql = file_get_contents(FAYSOFT_PATH . 'cms/db/system.sql');
        $sql = str_replace(array('{{$prefix}}', '{{$time}}'), array($prefix, $this->current_time), $sql);
        $this->db->exec($sql, true);
        
        //安装日志
        file_put_contents(APPLICATION_PATH . 'runtimes/installed.lock', "\r\nsystem-data-completed", FILE_APPEND);
        
        return Response::json(array(
            '_token'=>$this->getToken(),
        ));
    }
    
    /**
     * 安装用户自定义数据
     */
    public function setCustom(){
        if(file_exists(APPLICATION_PATH . 'data/custom.sql')){
            $prefix = $this->config->get('db.table_prefix');
            $charset = $this->config->get('db.charset');
            if($sql = file_get_contents(APPLICATION_PATH . 'data/custom.sql')){
                $sql = str_replace(array('{{$prefix}}', '{{$time}}', '{{$charset}}'), array($prefix, $this->current_time, $charset), $sql);
                $this->db->exec($sql, true);
            }
        }
        
        //安装日志
        file_put_contents(APPLICATION_PATH . 'runtimes/installed.lock', "\r\ncustom-data-completed", FILE_APPEND);
        
        return Response::json(array(
            '_token'=>$this->getToken(),
        ));
    }
    
    /**
     * 对categories表和menus表进行索引
     */
    public function indexCats(){
        CategoryService::service()->buildIndex();
        MenuService::service()->buildIndex();
        
        //安装日志
        file_put_contents(APPLICATION_PATH . 'runtimes/installed.lock', "\r\nindex-tree-tables-completed", FILE_APPEND);
        file_put_contents(APPLICATION_PATH . 'runtimes/installed.lock', "\r\n" . date('Y-m-d H:i:s [') . Request::getUserIP() . "]\r\ndatabase-completed", FILE_APPEND);
        
        return Response::json(array(
            '_token'=>$this->getToken(),
        ));
    }
}