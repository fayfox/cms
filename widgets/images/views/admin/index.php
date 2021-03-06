<?php
use cms\services\file\FileService;
use fay\helpers\HtmlHelper;

?>
<div class="mb30"><?php echo F::form('widget')->inputText('title', array(
    'id'=>'title',
    'class'=>'form-control bigtxt',
    'placeholder'=>'在此键入标题，实际用途视主题而定',
))?></div>
<div class="drag-drop-area" id="drag-drop-area">
    <div class="drag-drop-inside">
        <p class="drag-drop-info">将文件拖拽至此</p>
        <p>或</p>
        <p class="drag-drop-buttons">
            <a class="plupload-browse-button btn btn-grey" id="plupload-browse-button">选择文件</a>
        </p>
    </div>
</div>
<div class="dragsort-list file-list">
<?php foreach($widget->config['files'] as $f){?>
    <div class="dragsort-item <?php if(!empty($f['start_time']) && \F::app()->current_time < $f['start_time']){
        echo 'bl-yellow';
    }else if(!empty($f['end_time']) && \F::app()->current_time > $f['end_time']){
        echo 'bl-red';
    }?>">
        <?php echo HtmlHelper::inputHidden('files[]', $f['file_id'])?>
        <a class="dragsort-rm" href="javascript:"></a>
        <a class="dragsort-item-selector"></a>
        <div class="dragsort-item-container">
            <span class="fl">
            <?php
                echo HtmlHelper::link(HtmlHelper::img($f['file_id'], 2), FileService::getUrl($f['file_id']), array(
                    'encode'=>false,
                    'title'=>HtmlHelper::encode($f['title']),
                    'data-fancybox'=>'images',
                    'data-caption'=>HtmlHelper::encode(HtmlHelper::encode($f['title'])),
                    'class'=>'mask ib',
                ));
            ?>
            </span>
            <div class="ml120">
                <?php echo HtmlHelper::inputText("titles[{$f['file_id']}]", $f['title'], array(
                    'class'=>'file-title mb5 form-control',
                    'placeholder'=>'标题',
                ))?>
                <?php echo HtmlHelper::inputText("links[{$f['file_id']}]", $f['link'], array(
                    'class'=>'file-link mb5 form-control',
                    'placeholder'=>'链接地址',
                ))?>
                <?php echo HtmlHelper::inputText("start_time[{$f['file_id']}]", $f['start_time'] ? date('Y-m-d H:i:s', $f['start_time']) : '', array(
                    'class'=>'file-starttime datetimepicker mb5 form-control wp49 fl',
                    'placeholder'=>'生效时间',
                    'autocomplete'=>'off',
                ))?>
                <?php echo HtmlHelper::inputText("end_time[{$f['file_id']}]", $f['end_time'] ? date('Y-m-d H:i:s', $f['end_time']) : '', array(
                    'class'=>'file-endtime datetimepicker mb5 form-control wp49 fr',
                    'placeholder'=>'过期时间',
                    'autocomplete'=>'off',
                ))?>
            </div>
            <div class="clear"></div>
        </div>
    </div>
<?php }?>
</div>
<?php F::app()->view->renderPartial('admin/widget/_template_box')?>
<script type="text/javascript">
var jq_camera = {
    'uploadObj':null,
    'files':function(){
        system.getScript(system.assets('faycms/js/admin/uploader.js'), function(){
            uploader.files({
                'browse_button': 'plupload-browse-button',
                'container': 'drag-drop-area',
                'drop_element': 'drag-drop-area',
                'cat': 'widget',
                'image_only': true,
                'file_info': ['title', 'link', 'validity']
            });
        });
    },
    'init':function(){
        this.files();
    }
};
$(function(){
    jq_camera.init();
    
});
</script>