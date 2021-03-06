<?php
use fay\helpers\HtmlHelper;

/**
 * @var $widget \cms\widgets\jq_nivo_slider\controllers\IndexController
 * @var $files array
 */

$element_id = $widget->config['element_id'] ? $widget->config['element_id'] : $widget->alias;
?>
<div id="<?php echo $element_id?>">
    <div class="nivo-slider">
    <?php foreach($files as $f){
        if(empty($f['link'])){
            $f['link'] = 'javascript:';
        }
        echo HtmlHelper::link(HtmlHelper::img($f['src'], 0, array(
            'alt'=>HtmlHelper::encode($f['title']),
            'title'=>HtmlHelper::encode($f['title']),
        )), $f['link'], array(
            'encode'=>false,
            'title'=>HtmlHelper::encode($f['title']),
        ));
    }?>
    </div>
</div>
<link type="text/css" rel="stylesheet" href="<?php echo $this->assets('css/nivo-slider.css')?>" >
<script src="<?php echo $this->assets('js/jquery.nivo.slider.pack.js')?>"></script>
<script>
$(function(){
    $("#<?php echo $element_id?> .nivo-slider").nivoSlider({
        'animSpeed':<?php echo $widget->config['animSpeed']?>,
        'pauseTime':<?php echo $widget->config['pauseTime']?>,
        'directionNav':<?php echo $widget->config['directionNav'] ? 'true' : 'false'?>,
        'effect':'<?php echo $widget->config['effect']?>'
    });
});
</script>