<?php
namespace cms\widgets\tags\controllers;

use cms\services\tag\TagService;
use fay\widget\Widget;

class IndexController extends Widget{
    public function initConfig($config){
        empty($config['number']) && $config['number'] = 10;
        empty($config['uri']) && $config['uri'] = 'tag/{$title}';
        empty($config['title']) && $config['title'] = '';
        
        //排序方式
        if(!isset($config['sort']) || !in_array($config['sort'], array_keys($this->order_map))){
            $config['sort'] = 'sort';
        }
        
        return $this->config = $config;
    }
    
    private $fields = array(
        'tag'=>array(
            'id', 'title'
        ),
        'counter'=>array(
            'posts'
        )
    );
    
    /**
     * 排序方式
     */
    private $order_map = array(
        'sort'=>'t.sort, t.id DESC',
        'posts'=>'tc.posts DESC',
        'create_time'=>'id DESC',
    );
    
    public function getData(){
        $tags = TagService::service()->getLimit(
            $this->fields,
            $this->config['number'],
            $this->getOrder()
        );
        
        //格式化分类链接
        $tags = $this->assembleLink($tags);
        
        return $tags;
    }
    
    public function index(){
        $tags = $this->getData();
        
        //若无分类可显示，则不显示该widget
        if($tags){
            $this->renderTemplate(array(
                'tags'=>$tags,
            ));
        }
    }
    
    /**
     * 获取排序方式
     * @return string
     */
    private function getOrder(){
        if(!empty($this->config['order']) && isset($this->order_map[$this->config['order']])){
            return $this->order_map[$this->config['order']];
        }else{
            return $this->order_map['sort'];
        }
    }
    
    /**
     * 为分类列表添加link字段
     * @param array $tags
     * @return array
     */
    private function assembleLink($tags){
        foreach($tags as &$t){
            $t['tag']['link'] = $this->view->url(str_replace(array(
                '{$id}', '{$title}',
            ), array(
                $t['tag']['id'], urlencode($t['tag']['title']),
            ), $this->config['uri']));
        }
        
        return $tags;
    }
}