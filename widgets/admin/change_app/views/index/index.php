<?php
use fay\helpers\HtmlHelper;

?>
<div class="box" data-name="<?php echo $this->__name?>">
    <div class="box-title">
        <a class="tools remove" title="隐藏"></a>
        <h4>平台切换</h4>
    </div>
    <div class="box-content">
        <form action="<?php echo $this->url('cms/admin/widget/render', array(
            'name'=>'cms/admin/change_app',
            'action'=>'change',
        ), false)?>" method="post" id="change-app-form" class="form-inline">
            <?php
                echo HtmlHelper::select('app', $options, APPLICATION, array(
                    'class'=>'form-control',
                ));
                echo HtmlHelper::link('切换', 'javascript:;', array(
                    'id'=>'change-app-form-submit',
                    'class'=>'btn btn-sm',
                ));
            ?>
            <span>（平台切换仅在当前登陆session有效）</span>
        </form>
        <div class="clear"></div>
    </div>
</div>