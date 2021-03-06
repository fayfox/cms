<?php
namespace cms\services\post;

use cms\models\tables\PostMetaTable;
use fay\core\Loader;
use fay\core\Service;
use fay\helpers\FieldsHelper;

class PostMetaService extends Service{
    /**
     * 默认返回字段
     */
    public static $default_fields = array('comments', 'views', 'likes');
    
    /**
     * @return $this
     */
    public static function service(){
        return Loader::singleton(__CLASS__);
    }
    
    /**
     * 获取文章计数信息
     * @param int $post_id 文章ID
     * @param string $fields 字段（post_meta表字段）
     * @return array 返回包含文章meta信息的一维数组
     */
    public function get($post_id, $fields = null){
        $fields = new FieldsHelper(
            $fields ? $fields : self::$default_fields,
            '',
            PostMetaTable::model()->getFields('real_views,real_comments,real_likes,real_favorites')
        );

        return PostMetaTable::model()->fetchRow(array(
            'post_id = ?'=>$post_id,
        ), $fields->getFields());
    }
    
    /**
     * 批量获取文章计数信息
     * @param array $post_ids 文章ID一维数组
     * @param string $fields 字段（post_meta表字段）
     * @return array 返回以文章ID为key的二维数组
     */
    public function mget($post_ids, $fields = null){
        if(!$post_ids){
            return array();
        }

        $fields = new FieldsHelper(
            $fields ? $fields : self::$default_fields,
            '',
            PostMetaTable::model()->getFields('real_views,real_comments,real_likes,real_favorites')
        );
        
        if(!$fields->hasField('post_id')){
            $fields->addFields('post_id');
            $remove_post_id = true;
        }else{
            $remove_post_id = false;
        }
        $metas = PostMetaTable::model()->fetchAll(array(
            'post_id IN (?)'=>$post_ids,
        ), $fields->getFields(), 'post_id');
        $return = array_fill_keys($post_ids, array());
        foreach($metas as $m){
            $p = $m['post_id'];
            if($remove_post_id){
                unset($m['post_id']);
            }
            $return[$p] = $m;
        }
        return $return;
    }
    
    /**
     * 将meta信息装配到$posts中
     * @param array $posts 包含文章信息的三维数组
     *   若包含$posts.post.id字段，则以此字段作为文章ID
     *   若不包含$posts.post.id，则以$posts的键作为文章ID
     * @param null|string $fields 字段（post_meta表字段）
     */
    public function assemble(&$posts, $fields = null){
        $fields || $fields = self::$default_fields;
        
        //获取所有文章ID
        $post_ids = array();
        foreach($posts as $k => $p){
            if(isset($p['post']['id'])){
                $post_ids[] = $p['post']['id'];
            }else{
                $post_ids[] = $k;
            }
        }
        
        $meta_map = $this->mget($post_ids, $fields);
        
        foreach($posts as $k => $p){
            if(isset($p['post']['id'])){
                $post_id = $p['post']['id'];
            }else{
                $post_id = $k;
            }
            
            $p['meta'] = $meta_map[$post_id];
            
            $posts[$k] = $p;
        }
    }
}