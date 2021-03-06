<?php
namespace cms\widgets\images\controllers;

use fay\widget\Widget;

class AdminController extends Widget{
    public function initConfig($config){
        empty($config['files']) && $config['files'] = array();
        $config['random'] = empty($config['random']) ? 0 : 1;
        $config['limit'] = empty($config['limit']) ? 0 : $config['limit'];
        
        //设置模版
        $this->parseTemplateForEdit($config);
        
        return $this->config = $config;
    }
    
    public function index(){
        return $this->view->render();
    }
    
    public function onPost(){
        $data = $this->form->getFilteredData();
        
        $files = $this->input->post('files', 'intval', array());
        $links = $this->input->post('links', 'trim');
        $titles = $this->input->post('titles', 'trim');
        $start_times = $this->input->post('start_time', 'trim|strtotime');
        $end_times = $this->input->post('end_time', 'trim|strtotime');
        foreach($files as $p){
            $data['files'][] = array(
                'file_id'=>$p,
                'link'=>$links[$p],
                'title'=>$titles[$p],
                'start_time'=>$start_times[$p] ? $start_times[$p] : 0,
                'end_time'=>$end_times[$p] ? $end_times[$p] : 0,
            );
        }

        $this->saveConfig($data);
    }
    
    public function rules(){
        return array(
            array(array('width', 'height'), 'int', array('min'=>1)),
            array(array('start_time', 'end_time'), 'datetime'),
            array('links', 'url'),
        );
    }
    
    public function labels(){
        return array(
            'title'=>'标题',
            'template'=>'模版',
            'width'=>'图片宽度',
            'height'=>'图片高度',
            'links'=>'链接',
            'start_time'=>'生效时间',
            'end_time'=>'过期时间',
        );
    }
    
    public function filters(){
        return array(
            'title'=>'trim',
            'width'=>'intval',
            'height'=>'intval',
            'random'=>'intval',
            'limit'=>'intval',
            'template'=>'trim',
            'template_code'=>'trim',
        );
    }
}