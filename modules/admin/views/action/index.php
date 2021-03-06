<?php
/**
 * @var $cats array
 * @var $listview \fay\common\ListView
 */
?>
<div class="row">
    <div class="col-5">
        <?php echo F::form()->open(array('cms/admin/action/create'))?>
            <?php echo $this->renderPartial('_edit_panel', array(
                'cats'=>$cats,
            ));?>
            <div class="form-field">
                <?php echo F::form()->submitLink('添加权限', array(
                    'class'=>'btn',
                ))?>
            </div>
        <?php echo F::form()->close()?>
    </div>
    <div class="col-7">
        <?php echo $this->renderPartial('_right', array(
            'listview'=>$listview,
            'cats'=>$cats,
        ))?>
    </div>
</div>