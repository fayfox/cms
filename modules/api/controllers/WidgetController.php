<?php
namespace cms\modules\api\controllers;

use cms\library\ApiController;
use fay\exceptions\NotFoundHttpException;
use fay\core\Response;
use fay\helpers\StringHelper;

/**
 * 小工具
 */
class WidgetController extends ApiController{
    /**
     * 根据widget name及其他传入参数，渲染一个widget
     * @parameter string $name 小工具名称
     */
    public function render(){
        //验证必须get方式发起请求
        $this->checkMethod('GET');
        
        //表单验证
        $this->form()->setRules(array(
            array(array('name'), 'required'),
        ))->setFilters(array(
            'name'=>'trim',
            'action'=>'trim',
            '_index'=>'trim',
            '_alias'=>'trim',
        ))->setLabels(array(
            'name'=>'名称',
        ))->check();
        
        $widget_obj = \F::widget()->get($this->form()->getData('name'));
        if($widget_obj == null){
            throw new NotFoundHttpException('Widget不存在或已被删除');
        }
        
        $widget_obj->_index = $this->form()->getData('_index');
        $widget_obj->alias = $this->form()->getData('_alias');
        
        $action = StringHelper::hyphen2case($this->form()->getData('action', 'index'), false);
        if(method_exists($widget_obj, $action)){
            $widget_obj->{$action}($this->input->request());
        }else if(method_exists($widget_obj, $action.'Action')){
            $widget_obj->{$action.'Action'}($this->input->request());
        }else{
            throw new NotFoundHttpException('Widget方法不存在');
        }
    }
    
    /**
     * 根据别名渲染一个widget
     * @parameter param string $alias 小工具别名
     */
    public function load(){
        //验证必须get方式发起请求
        $this->checkMethod('GET');
        
        //表单验证
        $this->form()->setRules(array(
            array(array('alias'), 'required'),
        ))->setFilters(array(
            'alias'=>'trim',
            'action'=>'trim',
        ))->setLabels(array(
            'alias'=>'别名',
            'action'=>'方法',
        ))->check();
        
        \F::widget()->load(
            $this->form()->getData('alias'),
            $this->form()->getData('_index'),
            false,
            $this->form()->getData('action', 'index')
        );
    }
    
    /**
     * 获取widget实例参数，需要widget实现IndexController::getData()方法
     * @parameter param string $alias 小工具别名
     */
    public function data(){
        //验证必须get方式发起请求
        $this->checkMethod('GET');
        
        //表单验证
        $this->form()->setRules(array(
            array(array('alias'), 'required'),
        ))->setFilters(array(
            'alias'=>'trim',
        ))->setLabels(array(
            'alias'=>'别名',
        ))->check();
        
        $data = \F::widget()->getData($this->form()->getData('alias'));
        if($this->input->get('callback')){
            return Response::jsonp($this->input->get('callback'), $data);
        }else{
            return Response::json($data);
        }
    }
}