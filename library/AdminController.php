<?php
namespace cms\library;

use cms\models\forms\SettingForm;
use cms\models\tables\ActionlogsTable;
use cms\models\tables\RolesTable;
use cms\services\FlashService;
use cms\services\MenuService;
use cms\services\SettingService;
use cms\services\user\UserService;
use fay\core\Controller;
use fay\exceptions\AccessDeniedHttpException;
use fay\core\exceptions\HttpException;
use fay\exceptions\ValidationException;
use fay\core\Form;
use fay\core\Request;
use fay\core\Uri;
use fay\helpers\ArrayHelper;

class AdminController extends Controller{
    public $layout_template = 'admin';
    
    public $_left_menu = array();
    
    public $_top_nav = array(
        array(
            'label'=>'站点首页',
            'icon'=>'fa fa-home',
            'router'=>null,
            'target'=>'_blank',
        ),
        array(
            'label'=>'控制台',
            'icon'=>'fa fa-dashboard',
            'router'=>'cms/admin/index/index',
        ),
        array(
            'label'=>'Tools',
            'icon'=>'fa fa-wrench',
            'router'=>'tools',
            'roles'=>RolesTable::ITEM_SUPER_ADMIN,
        ),
    );
    
    public function __construct(){
        parent::__construct();
        //可以通过url参数指定仅输出主体部分（没有左侧菜单和顶部导航）
        $this->layout_template = $this->input->get('layout', 'trim') == 'main' ? 'main' : 'admin';
        
        //重置session.namespace
        $this->config->set('session.namespace', $this->config->get('session.namespace').'_admin');
        
        //设置当前用户id
        $this->current_user = \F::session()->get('user.id', 0);
        
        //验证session中是否有值
        if(!UserService::service()->isAdmin()){
            $this->response->redirect(
                'cms/admin/login/index',
                array('redirect'=>base64_encode($this->view->url(Uri::getInstance()->router, $this->input->get())))
            );
        }
        $this->layout->current_directory = '';
        $this->layout->subtitle = '';

        //权限判断
        if(!$this->checkPermission(Uri::getInstance()->router)){
            throw new AccessDeniedHttpException('您无权限做此操作');
        }
        
        if(!$this->input->isAjaxRequest()){
            $this->_left_menu = MenuService::service()->getTree('_admin_main');
        }
    }
    
    /**
     * 表单验证，若发生错误，返回第一个报错信息
     * 调用该函数前需先设置表单验证规则
     * @param \fay\core\Form $form
     * @throws HttpException
     */
    public function onFormError(Form $form){
        $errors = $form->getErrors();
        
        if($form->getScene() == 'final' || Request::isAjax()){
            //final类型的场景，直接抛异常中断直行（一般是列表页参数错误）
            throw new ValidationException($errors[0]['message']);
        }else{
            foreach($errors as $e){
                FlashService::set($e['message']);
            }
        }
    }
    
    /**
     * 后台管理员操作日志
     * @param string $type
     * @param string $note
     * @param int $refer 引用，例如操作为“编辑文章”则该字段为文章id
     */
    public function actionlog($type, $note, $refer = 0){
        ActionlogsTable::model()->insert(array(
            'user_id'=>$this->current_user,
            'create_time'=>$this->current_time,
            'ip_int'=>$this->ip_int,
            'type'=>$type,
            'note'=>$note,
            'refer'=>is_array($refer) ? implode(',', $refer) : $refer,
        ));
    }
    
    /**
     * 添加一个菜单组，一次只能添加一组
     * @param $menu
     * @param null $offset
     */
    public function addMenuTeam($menu, $offset = null){
        if($offset === null){
            $this->_left_menu[] = $menu;
        }else{
            array_splice($this->_left_menu, $offset, 0, array($menu));
        }
    }
    
    /**
     * 在已有的菜单组中添加一个菜单项
     * @param array $sub_menu
     * @param string $directory 菜单组别名
     * @param int $offset 偏移，从0开始，若为负数，则从末尾倒数
     */
    public function addMenuItem($sub_menu, $directory, $offset = -1){
        foreach($this->_left_menu as $k => &$menu){
            if($menu['alias'] == $directory){
                array_splice($menu['children'], $offset, 0, array($sub_menu));
            }
        }
    }
    
    /**
     * 根据directory删除一组菜单，若指定index，则只删除指定的某个菜单项。
     * index从0开始
     * @param $directory
     * @param null $index
     */
    public function removeMenuTeam($directory, $index = null){
        foreach($this->_left_menu as $k => &$menu){
            if($menu['alias'] == $directory){
                if($index === null){
                    unset($this->_left_menu[$k]);
                    break;
                }else{
                    unset($menu['sub'][$index]);
                }
            }
        }
    }
    
    /**
     * 添加一个顶部菜单
     * @param $menu
     * @param null $offset
     */
    public function addTopNav($menu, $offset = null){
        if($offset === null){
            $this->_top_nav[] = $menu;
        }else{
            array_splice($this->_top_nav, $offset, 0, array($menu));
        }
    }
    
    /**
     * 将boxes二维数组转换为仅包含name值的一维数组返回
     * @return array
     */
    protected function getBoxNames(){
        if(empty($this->boxes)){
            return array();
        }else{
            return ArrayHelper::column($this->boxes, 'name');
        }
    }
    
    /**
     * 获取用户启用的boxes
     *
     * @param null|string|array $settings 若为null 会去View中获取key
     *  - 若是string 视为key
     *  - 若是array 视为传入配置数组
     * @return array
     */
    protected function getEnabledBoxes($settings = null){
        $settings === null && $settings = $this->view->_setting_key;
        if(!is_array($settings)){
            $settings = SettingService::service()->get($settings);
        }
        if(!empty($settings['boxes'])){
            return array_intersect($this->getBoxNames(), isset($settings['boxes']) ? $settings['boxes'] : array());
        }else{
            return $this->getBoxNames();
        }
    }
    
    /**
     * 添加一个box
     * @param $box
     * @param null $offset
     */
    public function addBox($box, $offset = null){
        if($offset === null){
            $this->boxes[] = $box;
        }else{
            $this->boxes = array_splice($this->boxes, $offset, 0, array($box));
        }
        
    }
    
    /**
     * 根据box name删除一个box
     * @param $name
     */
    public function removeBox($name){
        if(isset($this->boxes) && is_array($this->boxes)){
            foreach($this->boxes as $key=>$box){
                if($box['name'] == $name){
                    unset($this->boxes[$key]);
                    break;
                }
            }
        }
    }
    
    /**
     * 初始化设置表单
     * @param string $key 设置key
     * @param string $panel 设置面板
     * @param array $default 默认值
     * @param array $data 附加数据（函数内部会通过$key获取用户设置，有些特殊设置需要处理后传入，可以在这个参数传入）
     * @return array|null
     */
    public function settingForm($key, $panel, $default = array(), $data = array()){
        $this->layout->_setting_panel = $panel;
        
        $settings = SettingService::service()->get($key);
        $settings || $settings = $default;
        
        $this->form('setting')
            ->setModel(SettingForm::model())
            ->setJsModel('setting')
            ->setData($settings)
            ->setData(array(
                '_key'=>$key,
            ));
        
        if($data){
            $this->form('setting')
                ->setData($data);
        }
        
        return $this->form('setting')->getAllData(false);
    }
}