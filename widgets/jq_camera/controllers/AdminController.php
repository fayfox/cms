<?php
namespace cms\widgets\jq_camera\controllers;

use fay\widget\Widget;

class AdminController extends Widget{
    public function initConfig($config){
        empty($config['files']) && $config['files'] = array();
        isset($config['height']) || $config['height'] = 450;
        isset($config['transPeriod']) || $config['transPeriod'] = 800;
        isset($config['time']) || $config['time'] = 5000;
        isset($config['fx']) || $config['fx'] = 'random';
        
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
            array('links', 'url'),
            array(array('transPeriod', 'time'), 'int'),
        );
    }
    
    public function labels(){
        return array(
            'links'=>'链接地址',
            'height'=>'高度',
            'transPeriod'=>'过渡动画时长',
            'time'=>'播放间隔时长',
            'start_time'=>'生效时间',
            'end_time'=>'过期时间',
        );
    }
    
    public function filters(){
        return array(
            'element_id'=>'trim',
            'height'=>'trim',
            'transPeriod'=>'intval',
            'time'=>'intval',
            'fx'=>'trim',
            'template'=>'trim',
            'template_code'=>'trim',
        );
    }
}