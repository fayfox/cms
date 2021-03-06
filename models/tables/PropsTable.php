<?php
namespace cms\models\tables;

use fay\core\db\Table;
use fay\core\Loader;

/**
 * 自定义属性表
 *
 * @property int $id Id
 * @property int $usage_type 用途
 * @property string $title 属性名称
 * @property int $element 表单元素
 * @property int $required 必选标记
 * @property string $alias 别名
 * @property int $delete_time 删除时间
 * @property int $create_time 创建时间
 * @property int $is_show 是否默认显示
 */
class PropsTable extends Table{
    /**
     * 表单元素-文本框
     */
    const ELEMENT_TEXT = 1;

    /**
     * 表单元素-单选框
     */
    const ELEMENT_RADIO = 2;

    /**
     * 表单元素-下拉框
     */
    const ELEMENT_SELECT = 3;

    /**
     * 表单元素-多选框
     */
    const ELEMENT_CHECKBOX = 4;

    /**
     * 表单元素-文本域
     */
    const ELEMENT_TEXTAREA = 5;

    /**
     * 表单元素-纯数字输入框
     */
    const ELEMENT_NUMBER = 6;

    /**
     * 表单元素-图片
     */
    const ELEMENT_IMAGE = 7;

    /**
     * 表单元素-文件
     */
    const ELEMENT_FILE = 8;
    /**
     * 用途 - 文章分类属性
     */
    const USAGE_POST_CAT = 1;

    /**
     * 用途 - 角色附加属性
     */
    const USAGE_ROLE = 2;
    
    protected $_name = 'props';
    
    public static $element_map = array(
        self::ELEMENT_TEXT => '文本框',
        self::ELEMENT_RADIO => '单选框',
        self::ELEMENT_SELECT => '下拉框',
        self::ELEMENT_CHECKBOX => '多选框',
        self::ELEMENT_TEXTAREA => '文本域',
        self::ELEMENT_NUMBER => '数字输入框',
        self::ELEMENT_IMAGE => '图片',
        self::ELEMENT_FILE => '文件',
    );

    /**
     * @return $this
     */
    public static function model(){
        return Loader::singleton(__CLASS__);
    }

    public function rules(){
        return array(
            array(array('id'), 'int', array('min'=>0, 'max'=>16777215)),
            array(array('usage_type', 'element'), 'int', array('min'=>0, 'max'=>255)),
            array(array('title', 'alias'), 'string', array('max'=>50)),
            array(array('is_show', 'required'), 'range', array('range'=>array(0, 1))),
            
            array('title', 'required'),
            array('alias', 'unique', array('table'=>'props', 'field'=>'alias', 'except'=>'id', 'ajax'=>array('cms/admin/prop/is-alias-not-exist'))),
        );
    }

    public function labels(){
        return array(
            'id'=>'Id',
            'usage_type'=>'用途类型',
            'title'=>'属性名称',
            'element'=>'表单元素',
            'required'=>'必选标记',
            'alias'=>'别名',
            'delete_time'=>'删除时间',
            'create_time'=>'创建时间',
            'is_show'=>'是否默认显示',
        );
    }

    public function filters(){
        return array(
            'id'=>'intval',
            'usage_type'=>'intval',
            'title'=>'trim',
            'element'=>'intval',
            'required'=>'intval',
            'alias'=>'trim',
            'is_show'=>'intval',
        );
    }

    public function getNotWritableFields($scene){
        switch($scene){
            case 'insert':
                return array('id');
                break;
            case 'update':
                return array(
                    'id', 'create_time', 'delete_time', 'usage_type'//用途不允许修改，改掉的话老数据就很难处理了
                );
                break;
            default:
                return array();
        }
    }
}