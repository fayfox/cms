<?php
namespace cms\models\tables;

use fay\core\db\Table;
use fay\core\Loader;

class RolesTable extends Table{
    /**
     * 超级管理员
     */
    const ITEM_SUPER_ADMIN = 1;
    
    protected $_name = 'roles';
    
    /**
     * @return $this
     */
    public static function model(){
        return Loader::singleton(__CLASS__);
    }
    
    public function rules(){
        return array(
            array(array('id'), 'int', array('min'=>0, 'max'=>65535)),
            array(array('title', 'description'), 'string', array('max'=>255)),
            array(array('admin'), 'range', array('range'=>array(0, 1))),
            
            array(array('title'), 'unique', array('table'=>'roles', 'except'=>'id', 'ajax'=>array('cms/admin/role/is-title-not-exist'))),
            array(array('title'), 'required'),
        );
    }

    public function labels(){
        return array(
            'id'=>'Id',
            'title'=>'角色名',
            'description'=>'描述',
            'delete_time'=>'删除时间',
            'admin'=>'是否管理员角色',
        );
    }

    public function filters(){
        return array(
            'title'=>'trim',
            'description'=>'trim',
            'admin'=>'intval',
        );
    }
    
    public function getNotWritableFields($scene){
        switch($scene){
            case 'insert':
            case 'update':
            default:
                return array(
                    'id'
                );
        }
    }
}