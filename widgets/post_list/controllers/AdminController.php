<?php
namespace cms\widgets\post_list\controllers;

use cms\services\CategoryService;
use fay\widget\Widget;

class AdminController extends Widget{
    public function initConfig($config){
        //设置模版
        //$this->parseTemplateForEdit($config);
        $this->parseTemplateForEdit($config);
        
        return $this->config = $config;
    }
    
    public function index(){
        $root_node = CategoryService::service()->get('_system_post', 'id');
        return $this->view->assign(array(
            'cats'=>array(
                array(
                    'id'=>$root_node['id'],
                    'title'=>'顶级',
                    'children'=>CategoryService::service()->getTree($root_node['id']),
                ),
            )
        ));
        
        return $this->view->render();
    }
    
    /**
     * 当有post提交的时候，会自动调用此方法
     */
    public function onPost(){
        $data = $this->form->getFilteredData();
        
        if(empty($data['fields'])){
            $data['fields'] = array();
        }
        $this->saveConfig($data);
    }
    
    public function rules(){
        return array(
            array('page_size', 'int', array('min'=>1)),
            array(array('file_thumbnail_width', 'file_thumbnail_height', 'post_thumbnail_width', 'post_thumbnail_height'), 'int', array('min'=>0)),
            array('pager', 'range', array('range'=>array('system', 'custom'))),
            array('cat_id', 'exist', array('table'=>'categories', 'field'=>'id')),
        );
    }
    
    public function labels(){
        return array(
            'page_size'=>'分页大小',
            'page_key'=>'页码字段',
            'cat_key'=>'分类字段',
            'cat_id'=>'默认分类',
            'post_thumbnail_width'=>'文章缩略图宽度',
            'post_thumbnail_height'=>'文章缩略图高度',
            'file_thumbnail_width'=>'附件缩略图宽度',
            'file_thumbnail_height'=>'附件缩略图高度',
        );
    }
    
    public function filters(){
        return array(
            'page_size'=>'intval',
            'page_key'=>'trim',
            'cat_key'=>'trim',
            'order'=>'trim',
            'date_format'=>'trim',
            'template'=>'trim',
            'template_code'=>'trim',
            'fields'=>'trim',
            'pager'=>'trim',
            'pager_template'=>'trim',
            'empty_text'=>'trim',
            'cat_id'=>'intval',
            'subclassification'=>'intval',
            'post_thumbnail_width'=>'intval',
            'post_thumbnail_height'=>'intval',
            'file_thumbnail_width'=>'intval',
            'file_thumbnail_height'=>'intval',
        );
    }
}