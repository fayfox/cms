<?php 
namespace cms\helpers;

use fay\core\Uri;
use fay\helpers\HtmlHelper;

class ListTableHelper{
    /**
     * 仅适用于跟list-table th中的排序
     * @param string $field
     * @param string $label
     * @return string
     */
    public static function getSortLink($field, $label){
        $text = "<span class='fl'>{$label}</span><span class='sorting-indicator'></span>";

        $class = \F::input()->get('order') == 'desc' ? 'sortable desc' : 'sortable asc';
        if(\F::input()->get('orderby') == $field){
            $class .= ' sorted';
        }
        return HtmlHelper::link($text, array(Uri::getInstance()->router, array(
            'orderby'=>$field,
            'order'=>\F::input()->get('order') == 'desc' ? 'asc' : 'desc',
            'page'=>1,
        )+\F::input()->get()), array(
            'class'=>$class,
            'encode'=>false,
            'title'=>\F::input()->get('order') == 'desc' ? '点击升序' : '点击降序',
        ));
    }
}