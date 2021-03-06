<?php
namespace cms\modules\admin\controllers;

use cms\library\AdminController;
use cms\services\SettingService;

class IndexController extends AdminController{
    //首页的boxes，本质上是widget
    public $boxes = array(
        array('name'=>'cms/admin/change_app', 'title'=>'平台切换'),
        array('name'=>'cms/admin/tongji_chart', 'title'=>'访问统计（图表）'),
        array('name'=>'cms/admin/tongji', 'title'=>'访问统计（概况）'),
        array('name'=>'cms/admin/ip_statistics', 'title'=>'IP统计'),
        array('name'=>'cms/admin/user_info', 'title'=>'用户信息'),
        array('name'=>'cms/admin/check_system', 'title'=>'系统检测'),
        array('name'=>'cms/admin/admins_online', 'title'=>'在线管理员'),
        array('name'=>'cms/admin/feeds', 'title'=>'Feeds'),
    );
    public $ajax_boxes = array('cms/admin/ip_statistics', 'cms/admin/check_system');
    
    //不写构造函数的话，index方法会被认为是构造函数
    public function __construct(){
        parent::__construct();
    }
    
    public function index(){
        $this->layout->subtitle = '控制台';
        
        //页面设置
        $_setting_key = 'admin_dashboard_boxes';
        $this->settingForm($_setting_key, '_setting_index');
        $this->view->enabled_boxes = $this->getEnabledBoxes($_setting_key);
        
        //box排序
        $this->view->_settings = SettingService::service()->get('admin_dashboard_box_sort');
        
        if($this->view->_settings === null){
            $this->view->_settings = array();
            $half = intval(count($this->view->enabled_boxes) / 2);
            $this->view->_settings['dashboard-left'] = array_slice($this->view->enabled_boxes, 0, $half);
            $this->view->_settings['dashboard-right'] = array_slice($this->view->enabled_boxes, $half);
        }
        
        return $this->view->render();
    }
}