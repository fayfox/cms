<?php
namespace cms\services\prop\elements;

/**
 * 数字输入框
 * 虽然业务上和单选框不一样，但处理逻辑是一样的
 */
class PropElementNumber extends PropElementRadio{
    /**
     * 获取表单元素名称
     * @return string
     */
    public static function getName(){
        return '数字输入框';
    }
}