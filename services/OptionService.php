<?php
namespace cms\services;

use cms\models\tables\OptionsTable;
use fay\core\Loader;
use fay\core\Service;
use fay\helpers\ArrayHelper;

class OptionService extends Service{
    /**
     * 用于缓存
     */
    private static $options = array();
    
    /**
     * @return $this
     */
    public static function service(){
        return Loader::singleton(__CLASS__);
    }
    
    /**
     * 获取一个参数
     * @param string $name 参数名
     * @param mixed $default 若不存在，返回默认值
     * @param bool $no_cache 若为true，则强行从数据库获取，默认为false
     * @return mixed
     */
    public static function get($name, $default = null, $no_cache = false){
        if(!$no_cache && key_exists($name, self::$options)){
            if(self::$options[$name] !== null){
                return self::$options[$name];
            }else{
                return $default;
            }
        }
        
        $option = OptionsTable::model()->fetchRow(array('option_name = ?'=>$name), 'option_value');
        if($option){
            self::$options[$name] = $option['option_value'];
            return $option['option_value'];
        }else{
            self::$options[$name] = null;
            return $default;
        }
    }
    
    /**
     * 一次性获取多个参数
     * @param string|array $names 允许是逗号分割的字符串，或者数组
     * @return array 返回以name项作为key的数组，若name不存在则返回null
     */
    public static function mget($names){
        if(is_string($names)){
            $names = explode(',', $names);
        }
        
        $return = array();
        //先试图从缓存获取
        foreach($names as $k => $n){
            if(key_exists($n, self::$options)){
                $return[$n] = self::$options[$n];
                unset($names[$k]);
            }
        }
        
        $return2 = array();
        //若有的key缓存里没有，从数据库里搜
        if($names){
            $options = OptionsTable::model()->fetchAll(array('option_name IN (?)'=>$names), 'option_name,option_value');
            $return2 = ArrayHelper::column($options, 'option_value', 'option_name');
            foreach($names as $n){
                if($n && !isset($return2[$n])){
                    $return2[$n] = null;
                }
                self::$options[$n] = $return2[$n];
            }
        }
        return $return + $return2;
    }
    
    /**
     * 设置一个参数
     * @param string $name 参数名
     * @param mixed $value 参数值
     */
    public static function set($name, $value){
        $option = OptionsTable::model()->fetchRow(array('option_name = ?'=>$name), 'option_value');
        if($option){
            OptionsTable::model()->update(array(
                'option_value'=>$value,
                'update_time'=>\F::app()->current_time,
            ), array(
                'option_name = ?'=>$name,
            ));
        }else{
            OptionsTable::model()->insert(array(
                'option_name'=>$name,
                'option_value'=>$value,
                'create_time'=>\F::app()->current_time,
                'update_time'=>\F::app()->current_time,
            ));
        }
    }

    /**
     * 批量设置系统参数
     * @param array $data 键值数组
     */
    public static function mset($data){
        $names = array_keys($data);
        $options = self::mget($names);
        $new_options = array();
        foreach($data as $name => $value){
            if(!$name){
                continue;
            }
            if($options[$name] === null){
                //新增参数，等后面一起批量插入
                $new_options[] = array(
                    'option_name'=>$name,
                    'option_value'=>$value,
                    'create_time'=>\F::app()->current_time,
                    'update_time'=>\F::app()->current_time,
                );
            }else if($value != $options[$name]){
                //更新参数
                OptionsTable::model()->update(array(
                    'option_value'=>$value,
                    'update_time'=>\F::app()->current_time,
                ), array(
                    'option_name = ?'=>$name,
                ));
            }
            self::$options[$name] = $value;
        }
        
        if($new_options){
            OptionsTable::model()->bulkInsert($new_options);
        }
    }
    
    /**
     * 根据配置项前缀获取配置（返回数组的key不会包含前缀部分）
     * @param string $name 配置项前缀
     * @return array
     */
    public static function getGroup($name){
        $options = OptionsTable::model()->fetchAll(array('option_name LIKE ?'=>$name.':%'), 'option_name,option_value');
        $return = array();
        foreach($options as $o){
            $return[substr($o['option_name'], strlen($name) + 1)] = $o['option_value'];
        }
        
        return $return;
    }
}