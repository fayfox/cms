<?php
namespace cms\modules\tools\controllers;

use cms\library\Db;
use cms\library\ToolsController;
use cms\models\tables\CategoriesTable;
use cms\models\tables\MenusTable;
use cms\models\tables\RolesTable;
use cms\models\tables\UsersTable;
use cms\services\CategoryService;
use cms\services\FlashService;
use cms\services\MenuService;
use fay\core\Request;
use fay\core\Response;
use fay\helpers\IPHelper;
use fay\helpers\LocalFileHelper;
use fay\helpers\StringHelper;

class ApplicationController extends ToolsController{
    /**
     * @var Db
     */
    private $db;
    
    public function __construct(){
        parent::__construct();
        $this->layout->current_directory = 'application';
        
        //登陆检查，仅超级管理员可访问本模块
        $this->isLogin();
    }
    
    public function index(){
        $this->layout->subtitle = 'Application List';
        
        return $this->view->render();
    }
    
    public function create(){
        $this->layout->subtitle = '创建项目';
        
        FlashService::set('此工具用于快速创建一个项目', 'info');
        if(!is_writable(BASEPATH.'..'.DS.'apps/')){
            FlashService::set('app目录不可写！用此功能创建项目，请确保系统对此目录拥有写权限。');
        }
        
        if($this->input->post()){
            $app_name = $this->input->post('name');
            $table_prefix = $this->input->post('table_prefix');
            $charset = $this->input->post('charset');
            //创建主配置文件
            $config_file = file_get_contents(CMS_PATH.'modules/tools/views/application/_templates/config/main.txt');
            $config_file = str_replace(array(
                '{{$host}}',
                '{{$user}}',
                '{{$password}}',
                '{{$port}}',
                '{{$dbname}}',
                '{{$charset}}',
                '{{$table_prefix}}',
                '{{$name}}',
            ), array(
                $this->input->post('host'),
                $this->input->post('user'),
                $this->input->post('password'),
                $this->input->post('port', 'intval', 3306),
                $this->input->post('dbname'),
                $charset,
                $table_prefix,
                $app_name,
            ), $config_file);
            LocalFileHelper::createFile(BASEPATH.'..'.DS.'apps/'.$app_name.'/configs/main.php', $config_file);
            
            //创建前端控制器基类
            $front_controller = file_get_contents(CMS_PATH.'modules/tools/views/application/_templates/library/FrontController.txt');
            $front_controller = str_replace('{{$name}}', $app_name, $front_controller);
            LocalFileHelper::createFile(BASEPATH.'..'.DS.'apps/'.$app_name.'/library/FrontController.php', $front_controller);
            
            //创建默认控制器
            $index_controller = file_get_contents(CMS_PATH.'modules/tools/views/application/_templates/module/IndexController.txt');
            $index_controller = str_replace('{{$name}}', $app_name, $index_controller);
            LocalFileHelper::createFile(BASEPATH.'..'.DS.'apps/'.$app_name.'/modules/frontend/controllers/IndexController.php', $index_controller);
            
            //创建默认视图
            LocalFileHelper::createFile(BASEPATH.'..'.DS.'apps/'.$app_name.'/modules/frontend/views/index/index.php', file_get_contents(CMS_PATH.'modules/tools/views/application/_templates/module/index.txt'));
            
            //创建默认layout
            LocalFileHelper::createFile(BASEPATH.'..'.DS.'apps/'.$app_name.'/modules/frontend/views/layouts/frontend.php', file_get_contents(CMS_PATH.'modules/tools/views/application/_templates/module/frontend.txt'));
            
            //创建默认css
            LocalFileHelper::createFile(BASEPATH.'apps/'.$app_name.'/css/style.css', file_get_contents(CMS_PATH.'modules/tools/views/application/_templates/static/style.css'));
            
            if($this->input->post('database')){
                //安装数据库
                $this->db = new \cms\library\Db(array(
                    'host'=>$this->input->post('host'),
                    'user'=>$this->input->post('user'),
                    'password'=>$this->input->post('password'),
                    'port'=>$this->input->post('port', 'intval', 3306),
                    'dbname'=>$this->input->post('dbname'),
                    'table_prefix'=>$table_prefix,
                    'charset'=>$charset,
                ));
                $this->createTables($table_prefix, $charset);
                $this->setCities($table_prefix);
                $this->setRegions($table_prefix);
                $this->setCats($table_prefix);
                $this->setMenus($table_prefix);
                $this->setActions($table_prefix);
                $this->setSystem($table_prefix);
                $this->indexCats();
                $this->indexMenus();
                
                $salt = StringHelper::random('alnum', 5);
                $password = $this->input->post('user_password');
                $password = md5(md5($password).$salt);
                $user_id = $this->db->insert('users', array(
                    'username'=>$this->input->post('user_username'),
                    'password'=>$password,
                    'salt'=>$salt,
                    'status'=>UsersTable::STATUS_VERIFIED,
                    'admin'=>1,
                ));
                
                $this->db->insert('user_profile', array(
                    'user_id'=>$user_id,
                    'reg_time'=>$this->current_time,
                    'reg_ip'=>IPHelper::ip2int(Request::getUserIP()),
                    'trackid'=>'tools_create',
                ));
                
                $this->db->insert('users_roles', array(
                    'user_id'=>$user_id,
                    'role_id'=>RolesTable::ITEM_SUPER_ADMIN,
                ));
                
                $this->db->insert('options', array(
                    'option_name'=>'site:sitename',
                    'option_value'=>$this->input->post('sitename'),
                ));
                
                LocalFileHelper::createFile(BASEPATH.'..'.DS.'apps/'.$app_name.'/runtimes/installed.lock', date('Y-m-d H:i:s [') . Request::getUserIP() . "] \r\ninstallation-completed");
            }
        }
        
        return $this->view->render();
    }
    
    public function isAppNotExist(){
        $value = $this->input->post('value');
        
        $apps = LocalFileHelper::getFileList(APPLICATION_PATH.'..');
        foreach($apps as $app){
            if($value == $app['name']){
                return Response::json('', 0, '项目名已存在');
            }
        }
        return Response::json();
    }
    
    private function createTables($prefix, $charset){
        $sql = file_get_contents(FAYSOFT_PATH . '/cms/db/tables.sql');
        $sql = str_replace(array('{{$prefix}}', '{{$time}}', '{{$charset}}'), array($prefix, $this->current_time, $charset), $sql);
        $this->db->execute($sql);
    }
    
    private function setCities($prefix){
        $sql = file_get_contents(FAYSOFT_PATH . '/cms/db/cities.sql');
        $sql = str_replace(array('{{$prefix}}', '{{$time}}'), array($prefix, $this->current_time), $sql);
        $this->db->execute($sql);
    }
    
    private function setRegions($prefix){
        $sql = file_get_contents(FAYSOFT_PATH . '/cms/db/regions.sql');
        $sql = str_replace(array('{{$prefix}}', '{{$time}}'), array($prefix, $this->current_time), $sql);
        $this->db->execute($sql);
    }
    
    private function setCats($prefix){
        $sql = file_get_contents(FAYSOFT_PATH . '/cms/db/cats.sql');
        $sql = str_replace(array('{{$prefix}}', '{{$time}}'), array($prefix, $this->current_time), $sql);
        $this->db->execute($sql);
    }
    
    private function setMenus($prefix){
        $sql = file_get_contents(FAYSOFT_PATH . '/cms/db/menus.sql');
        $sql = str_replace(array('{{$prefix}}', '{{$time}}'), array($prefix, $this->current_time), $sql);
        $this->db->execute($sql);
    }
    
    private function setActions($prefix){
        $sql = file_get_contents(FAYSOFT_PATH . '/cms/db/actions.sql');
        $sql = str_replace(array('{{$prefix}}', '{{$time}}'), array($prefix, $this->current_time), $sql);
        $this->db->execute($sql);
    }
    
    private function setSystem($prefix){
        $sql = file_get_contents(FAYSOFT_PATH . '/cms/db/system.sql');
        $sql = str_replace(array('{{$prefix}}', '{{$time}}'), array($prefix, $this->current_time), $sql);
        $this->db->execute($sql);
    }
    
    /**
     * 对categories表进行索引
     */
    private function indexCats(){
        CategoriesTable::model()->setDb($this->db);
        CategoryService::service()->buildIndex();
    }
    
    /**
     * 对menus表进行索引
     */
    private function indexMenus(){
        MenusTable::model()->setDb($this->db);
        MenuService::service()->buildIndex();
    }
}