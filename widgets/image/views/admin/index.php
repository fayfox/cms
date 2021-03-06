<?php
use fay\helpers\HtmlHelper;

?>
<style>
#file-preview{text-align:center;}
#file-preview img{max-width:100%;}
</style>
<div class="box">
    <div class="box-content" id="file-preview" <?php if(!$widget->config['file_id'])echo 'style="display:none;"'?>>
        <?php echo HtmlHelper::img($widget->config['file_id'])?>
    </div>
</div>
<input type="hidden" value="<?php echo intval($widget->config['file_id'])?>" name="file_id" id="file_id" />
<div class="margin-top-20" id="widget-image-upload-container">
    <a href="javascript:" class="btn btn-grey" id="widget-image-upload">上传</a>
</div>
<div class="fc-grey">提示：点击侧边栏“提交”后，修改才会生效</div>
<?php F::app()->view->renderPartial('admin/widget/_template_box')?>
<script type="text/javascript" src="<?php echo $this->assets('js/plupload.full.js')?>"></script>
<script type="text/javascript">
$(function(){
    var uploader = new plupload.Uploader({
        runtimes : 'html5,html4,flash,gears,silverlight,browserplus',
        browse_button : 'widget-image-upload',
        container: 'widget-image-upload-container',
        max_file_size : '2mb',
        url : system.url('cms/admin/file/upload', {'cat':'widget'}),
        flash_swf_url : system.url()+'flash/plupload.flash.swf',
        silverlight_xap_url : system.url()+'js/plupload.silverlight.xap',
        filters : [
            {title : 'Image files', extensions : 'jpg,gif,png,jpeg'}
        ]
    });
    
    uploader.init();
    
    uploader.bind('FilesAdded', function(up, files) {
        $('#file-preview').html('<img src="'+system.assets('images/loading.gif')+'" />').show();
        uploader.start();
    });
    
    uploader.bind('UploadProgress', function(up, file) {
        
    });
    
    uploader.bind('Error', function(up, error) {
        if(error.code == -600){
            alert('文件大小不能超过'+(parseInt(uploader.settings.max_file_size) / (1024 * 1024))+'M');
            return false;
        }else if(error.code == -601){
            alert('非法的文件类型');
            return false;
        }else{
            alert(error.message);
        }
    });
    
    uploader.bind('FileUploaded', function(up, file, response) {
        var resp = $.parseJSON(response.response);
        $('#file_id').val(resp.data.id);
        $('#file-preview').html('<img src="'+resp.data.url+'" />');
        $("input[name='width']").val(resp.data.image_width);
        $("input[name='height']").val(resp.data.image_height);
    });
});
</script>