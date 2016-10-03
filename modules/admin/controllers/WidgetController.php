<?php
namespace cms\modules\admin\controllers;

use cms\library\AdminController;
use fay\models\tables\Widgets;
use fay\helpers\StringHelper;
use fay\models\tables\Actionlogs;
use fay\core\Sql;
use fay\common\ListView;
use fay\services\File;
use fay\core\Response;
use fay\core\HttpException;
use fay\core\Loader;

class WidgetController extends AdminController{
	public function __construct(){
		parent::__construct();
		$this->layout->current_directory = 'site';
	}
	
	public function index(){
		$this->layout->subtitle = '所有小工具';
		
		$this->layout->sublink = array(
			'uri'=>array('admin/widgetarea/index'),
			'text'=>'小工具域',
		);
		
		$widget_instances = array();
		
		//获取当前application下的widgets
		$app_widgets = File::getFileList(APPLICATION_PATH . 'widgets');
		foreach($app_widgets as $w){
			$widget_instances[] = \F::widget()->get($w['name'], true);
		}
		
		//获取系统公用widgets
		$common_widgets = File::getFileList(SYSTEM_PATH . 'fay' . DS . 'widgets');
		foreach($common_widgets as $w){
			$widget_instances[] = \F::widget()->get('fay/'.$w['name'], true);
		}
		
		$this->view->widgets = $widget_instances;

		//小工具域列表
		$widgetareas = $this->config->getFile('widgetareas');
		$widgetareas_arr = array();
		foreach($widgetareas as $wa){
			$widgetareas_arr[$wa['alias']] = $wa['description'] . ' - ' . $wa['alias'];
		}
		$this->view->widgetareas = $widgetareas_arr;
		
		$this->view->render();
	}
	
	public function edit(){
		$this->layout->sublink = array(
			'uri'=>array('admin/widgetarea/index'),
			'text'=>'小工具域',
		);
		
		$id = $this->input->get('id', 'intval');

		$widget = Widgets::model()->find($id);
		if(!$widget){
			throw new HttpException('指定的小工具ID不存在');
		}
		$widget_obj = \F::widget()->get($widget['widget_name'], true);
		
		if(file_exists($widget_obj->path . 'README.md')){
			Loader::vendor('Markdown/markdown');
			$this->layout->_help_content = '<div class="text">' . Markdown(file_get_contents($widget_obj->path . 'README.md')) . '</div>';
		}
		
		$this->form('widget')->setRules(array(
			array('f_widget_alias', 'string', array('max'=>255,'format'=>'alias')),
			array('f_widget_alias', 'required'),
			array('f_widget_description', 'string', array('max'=>255)),
			array('f_widget_alias', 'unique', array('table'=>'widgets', 'field'=>'alias', 'except'=>'id', 'ajax'=>array('admin/widget/is-alias-not-exist'))),
			
		))->setLabels(array(
			'f_widget_alias'=>'别名',
			'f_widget_description'=>'描述',
		));
		
		$widget_admin = \F::widget()->get($widget['widget_name'], true);
		$this->form('widget')->setRules($widget_admin->rules())
			->setLabels($widget_admin->labels())
			->setFilters($widget_admin->filters());
		
		if($this->input->post() && $this->form('widget')->check()){
			$f_widget_cache = $this->input->post('f_widget_cache');
			$f_widget_cache_expire = $this->input->post('f_widget_cache_expire', 'intval');
			$alias = $this->input->post('f_widget_alias', 'trim');
			Widgets::model()->update(array(
				'alias'=>$alias,
				'description'=>$this->input->post('f_widget_description', 'trim'),
				'enabled'=>$this->input->post('f_widget_enabled') ? 1 : 0,
				'ajax'=>$this->input->post('f_widget_ajax') ? 1 : 0,
				'cache'=>$f_widget_cache && $f_widget_cache_expire >= 0 ? $f_widget_cache_expire : -1,
				'widgetarea'=>$this->input->post('f_widget_widgetarea', 'trim'),
			), $id);
			
			$widget_obj->alias = $alias;
			if(method_exists($widget_obj, 'onPost')){
				$widget_obj->onPost();
			}
			$widget = Widgets::model()->find($id);
			\F::cache()->delete($alias);
		}
		
		$this->view->widget = $widget;
		if($widget['options']){
			$this->view->widget_config = json_decode($widget['options'], true);
			$this->form('widget')->setData($this->view->widget_config);
		}else{
			$this->view->widget_config = array();
		}
		
		$this->view->widget_admin = $widget_admin;
		$this->layout->subtitle = '编辑小工具  - '.$this->view->widget_admin->title;

		//小工具域列表
		$widgetareas = $this->config->getFile('widgetareas');
		$widgetareas_arr = array();
		foreach($widgetareas as $wa){
			$widgetareas_arr[$wa['alias']] = $wa['description'] . ' - ' . $wa['alias'];
		}
		$this->view->widgetareas = $widgetareas_arr;
		
		$this->view->render();
	}
	
	/**
	 * 加载一个widget
	 */
	public function render(){
		if($this->input->get('name')){
			$widget_obj = \F::widget()->get($this->input->get('name', 'trim'));
			if($widget_obj == null){
				throw new HttpException('Widget不存在或已被删除');
			}
			$action = StringHelper::hyphen2case($this->input->get('action', 'trim', 'index'), false);
			if(method_exists($widget_obj, $action)){
				$widget_obj->{$action}($this->input->get());
			}else if(method_exists($widget_obj, $action.'Action')){
				$widget_obj->{$action.'Action'}($this->input->get());
			}else{
				throw new HttpException('Widget方法不存在');
			}
		}else{
			throw new HttpException('不完整的请求');
		}
	}
	
	public function createInstance(){
		if($this->input->post()){
			$widget_instance_id = Widgets::model()->insert(array(
				'widget_name'=>$this->input->post('widget_name'),
				'alias'=>$this->input->post('alias') ? $this->input->post('alias') : 'w' . uniqid(),
				'description'=>$this->input->post('description'),
				'widgetarea'=>$this->input->post('widgetarea', 'trim'),
				'options'=>'',
			));
			$this->actionlog(Actionlogs::TYPE_WIDGET, '创建了一个小工具实例', $widget_instance_id);
			
			Response::notify('success', '小工具实例创建成功', array('admin/widget/edit', array(
				'id'=>$widget_instance_id,
			)));
		}else{
			throw new HttpException('不完整的请求');
		}
	}
	
	public function instances(){
		$this->layout->subtitle = '小工具实例';
		
		//页面设置
		$this->settingForm('admin_widget_instances', '_setting_instance', array(
			'page_size'=>20,
		));
		
		$sql = new Sql();
		$sql->from('widgets')
			->order('id DESC');
		$this->view->listview = new ListView($sql, array(
			'page_size'=>$this->form('setting')->getData('page_size', 20),
			'empty_text'=>'<tr><td colspan="5" align="center">无相关记录！</td></tr>',
		));
		
		$this->view->render();
	}
	
	public function removeInstance(){
		$id = $this->input->get('id', 'intval');
		Widgets::model()->delete($id);
		$this->actionlog(Actionlogs::TYPE_WIDGET, '删除了一个小工具实例', $id);

		Response::notify('success', array(
			'message'=>'一个小工具实例被删除',
		));
	}
	
	public function isAliasNotExist(){
		if(Widgets::model()->fetchRow(array(
			'alias = ?'=>$this->input->request('alias', 'trim'),
			'id != ?'=>$this->input->request('id', 'intval', false)
		))){
			Response::json('', 0, '别名已存在');
		}else{
			Response::json();
		}
	}
	
	public function copy(){
		$id = $this->input->get('id', 'intval');
		$widget = Widgets::model()->find($id);
		if(!$widget){
			throw new HttpException('指定小工具ID不存在');
		}
		
		$widget_id = Widgets::model()->insert(array(
			'alias'=>'w' . uniqid(),
			'options'=>$widget['options'],
			'widget_name'=>$widget['widget_name'],
			'description'=>$widget['description'],
			'enabled'=>$widget['enabled'],
			'widgetarea'=>$widget['widgetarea'],
			'sort'=>$widget['sort'],
			'ajax'=>$widget['ajax'],
			'cache'=>$widget['cache'],
		));
		
		$this->actionlog(Actionlogs::TYPE_WIDGET, '复制了小工具实例' . $id, $widget_id);
		
		Response::notify('success', array(
			'message'=>'一个小工具实例被复制',
		), array('admin/widgetarea/index'));
	}
}