<?php
use cms\helpers\ListTableHelper;
use cms\models\tables\PagesTable;
use cms\services\PageService;
use fay\helpers\HtmlHelper;

$cols = F::form('setting')->getData('cols', array());
?>
<div class="row">
    <div class="col-12">
        <?php echo F::form('search')->open(null, 'get', array(
            'class'=>'form-inline',
        ))?>
            <div class="mb5">
                <?php echo F::form('search')->select('keyword_field', array(
                    'title'=>'标题',
                    'alias'=>'别名',
                ), array(
                    'class'=>'form-control'
                ));?>
                <?php echo F::form('search')->inputText('keywords' ,array(
                    'class'=>'form-control w200',
                ));?>
                |
                <?php echo F::form('search')->select('cat_id', array(''=>'--分类--') + HtmlHelper::getSelectOptions($cats, 'id', 'title'), array(
                    'class'=>'form-control'
                ))?>
            </div>
            <div>
                <?php echo F::form('search')->select('time_field', array(
                    'create_time'=>'创建时间',
                    'update_time'=>'更新时间',
                ), array(
                    'class'=>'form-control'
                ));?>
                <?php echo F::form('search')->inputText('start_time', array(
                    'class'=>'form-control datetimepicker',
                ));?>
                -
                <?php echo F::form('search')->inputText('end_time', array(
                    'class'=>'form-control datetimepicker',
                ));?>
                <?php echo F::form('search')->submitLink('查询', array(
                    'class'=>'btn btn-sm',
                ))?>
            </div>
        <?php echo F::form('search')->close()?>
        <ul class="subsubsub">
            <li class="all <?php if(F::input()->get('status') === null && F::input()->get('deleted') === null)echo 'sel';?>">
                <a href="<?php echo $this->url('cms/admin/page/index')?>">全部</a>
                <span class="fc-grey">(<?php echo PageService::service()->getCount()?>)</span>
                |
            </li>
            <li class="publish <?php if(F::input()->get('status') == PagesTable::STATUS_PUBLISHED && F::input()->get('deleted') != 1)echo 'sel';?>">
                <a href="<?php echo $this->url('cms/admin/page/index', array('status'=>PagesTable::STATUS_PUBLISHED))?>">已发布</a>
                <span class="fc-grey">(<?php echo PageService::service()->getCount(PagesTable::STATUS_PUBLISHED)?>)</span>
                |
            </li>
            <li class="draft <?php if(F::input()->get('status', 'intval') === PagesTable::STATUS_DRAFT && F::input()->get('deleted') != 1)echo 'sel';?>">
                <a href="<?php echo $this->url('cms/admin/page/index', array('status'=>PagesTable::STATUS_DRAFT))?>">草稿</a>
                <span class="fc-grey">(<?php echo PageService::service()->getCount(PagesTable::STATUS_DRAFT)?>)</span>
                |
            </li>
            <li class="trash <?php if(F::input()->get('deleted') == 1)echo 'sel';?>">
                <a href="<?php echo $this->url('cms/admin/page/index', array('deleted'=>1))?>">回收站</a>
                <span class="fc-grey">(<?php echo PageService::service()->getDeletedCount()?>)</span>
            </li>
        </ul>
        <table class="list-table">
            <thead>
                <tr>
                    <th>标题</th>
                    <?php if(in_array('category', $cols)){?>
                    <th>分类</th>
                    <?php }?>
                    <?php if(in_array('status', $cols)){?>
                    <th>状态</th>
                    <?php }?>
                    <?php if(in_array('alias', $cols)){?>
                    <th>别名</th>
                    <?php }?>
                    <?php if(in_array('views', $cols)){?>
                    <th><?php echo ListTableHelper::getSortLink('views', '阅读数')?></th>
                    <?php }?>
                    <?php if(in_array('update_time', $cols)){?>
                    <th><?php echo ListTableHelper::getSortLink('update_time', '更新时间')?></th>
                    <?php }?>
                    <?php if(in_array('create_time', $cols)){?>
                    <th><?php echo ListTableHelper::getSortLink('create_time', '创建时间')?></th>
                    <?php }?>
                    <?php if(in_array('sort', $cols)){?>
                    <th class="w90"><?php echo ListTableHelper::getSortLink('sort', '排序')?></th>
                    <?php }?>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th>标题</th>
                    <?php if(in_array('category', $cols)){?>
                    <th>分类</th>
                    <?php }?>
                    <?php if(in_array('status', $cols)){?>
                    <th>状态</th>
                    <?php }?>
                    <?php if(in_array('alias', $cols)){?>
                    <th>别名</th>
                    <?php }?>
                    <?php if(in_array('views', $cols)){?>
                    <th><?php echo ListTableHelper::getSortLink('views', '阅读数')?></th>
                    <?php }?>
                    <?php if(in_array('update_time', $cols)){?>
                    <th><?php echo ListTableHelper::getSortLink('update_time', '更新时间')?></th>
                    <?php }?>
                    <?php if(in_array('create_time', $cols)){?>
                    <th><?php echo ListTableHelper::getSortLink('create_time', '创建时间')?></th>
                    <?php }?>
                    <?php if(in_array('sort', $cols)){?>
                    <th><?php echo ListTableHelper::getSortLink('sort', '排序')?></th>
                    <?php }?>
                </tr>
            </tfoot>
            <tbody>
        <?php
            $listview->showData(array(
                'cols'=>$cols,
            ));
        ?>
            </tbody>
        </table>
        <?php $listview->showPager();?>
    </div>
</div>
<script type="text/javascript" src="<?php echo $this->assets('faycms/js/admin/fayfox.editsort.js')?>"></script>
<script>
$(function(){
    $(".page-sort").feditsort({
        'url':system.url("cms/admin/page/sort")
    });
});
</script>