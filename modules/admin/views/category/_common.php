<?php
use fay\helpers\HtmlHelper;

/**
 * @var $root int
 * @var $cats array
 * @var $group_key string 归档字段（若是归档树，会有这个变量）
 * @var $group_value int 归档值（若是归档树，会有这个变量）
 * @var $get_cat_url string ajax获取分类信息链接
 * @var $create_cat_url string 创建分类表单提交地址
 * @var $edit_cat_url string 编辑分类表单提交地址
 */
?>
<div class="hide">
    <div id="edit-cat-dialog" class="dialog">
        <div class="dialog-content w600">
            <h4>编辑分类<em>（当前分类：<span id="edit-cat-title" class="fc-orange"></span>）</em></h4>
            <?php echo F::form('edit')->open(empty($edit_cat_url) ? array('cms/admin/category/edit') : $edit_cat_url)?>
                <?php echo HtmlHelper::inputHidden('id')?>
                <table class="form-table">
                    <tr>
                        <th class="adaption">标题<em class="required">*</em></th>
                        <td><?php echo HtmlHelper::inputText('title', '', array(
                            'class'=>'form-control',
                        ))?></td>
                    </tr>
                    <tr>
                        <th class="adaption">别名</th>
                        <td>
                            <?php echo HtmlHelper::inputText('alias', '', array(
                                'class'=>'form-control w150 ib',
                            ))?>
                            <span class="fc-grey">若您不确定它的用途，请不要修改</span>
                        </td>
                    </tr>
                    <tr>
                        <th valign="top" class="adaption">描述</th>
                        <td><?php echo HtmlHelper::textarea('description', '', array(
                            'class'=>'form-control h90 autosize',
                        ))?></td>
                    </tr>
                    <tr>
                        <th class="adaption">排序</th>
                        <td>
                            <?php echo HtmlHelper::inputNumber('sort', '1000', array(
                                'class'=>'form-control w100 ib',
                            ))?>
                            <span class="fc-grey">0-65535之间，数值越小，排序越靠前</span>
                        </td>
                    </tr>
                    <tr>
                        <th class="adaption">父节点</th>
                        <td>
                            <?php echo HtmlHelper::select('parent', array($root=>'根节点')+HtmlHelper::getSelectOptions($cats, 'id', 'title'), '', array(
                                'class'=>'form-control',
                            ))?>
                        </td>
                    </tr>
                    <tr>
                        <th class="adaption">导航</th>
                        <td>
                            <?php echo HtmlHelper::inputCheckbox('is_nav', '1', false, array(
                                'label'=>'在导航栏显示',
                            ))?>
                            <span class="fc-grey">（该选项实际效果视主题而定）</span>
                        </td>
                    </tr>
                    <tr>
                        <th valign="top" class="adaption">插图</th>
                        <td><?php echo $this->renderPartial('file/_upload_image', array(
                            'cat'=>'cat',
                            'field'=>'file_id',
                            'preview_image_width'=>'thumbnail',
                            'label'=>'插图',
                        ))?></td>
                    </tr>
                    <tr>
                        <th class="adaption"><a href="javascript:" class="toggle-seo-info" style="font-weight:normal;text-decoration:underline;">SEO信息</a></th>
                        <td></td>
                    </tr>
                    <tr class="hide toggle">
                        <th class="adaption">Title</th>
                        <td><?php echo HtmlHelper::inputText('seo_title', '', array(
                            'class'=>'form-control',
                        ))?></td>
                    </tr>
                    <tr class="hide toggle">
                        <th class="adaption">Keywords</th>
                        <td><?php echo HtmlHelper::inputText('seo_keywords', '', array(
                            'class'=>'form-control',
                        ))?></td>
                    </tr>
                    <tr class="hide toggle">
                        <th valign="top" class="adaption">Description</th>
                        <td><?php echo HtmlHelper::textarea('seo_description', '', array(
                            'class'=>'form-control',
                            'rows'=>5,
                        ))?></td>
                    </tr>
                    <tr>
                        <th class="adaption"></th>
                        <td>
                            <a href="javascript:" class="btn" id="edit-form-submit">编辑分类</a>
                            <a href="javascript:" class="btn btn-grey fancybox-close">取消</a>
                        </td>
                    </tr>
                </table>
            <?php echo F::form('edit')->close()?>
        </div>
    </div>
</div>
<div class="hide">
    <div id="create-cat-dialog" class="dialog">
        <div class="dialog-content w600">
            <h4>添加子分类<em>（父分类：<span id="create-cat-parent" class="fc-orange"></span>）</em></h4>
            <?php echo F::form('create')->open(empty($create_cat_url) ? array('cms/admin/category/create') : $create_cat_url)?>
                <?php echo HtmlHelper::inputHidden('parent')?>
                <?php
                    if(!empty($group_key)){
                        echo HtmlHelper::inputHidden($group_key, $group_value);
                    }
                ?>
                <table class="form-table">
                    <tr>
                        <th class="adaption">标题<em class="required">*</em></th>
                        <td><?php echo HtmlHelper::inputText('title', '', array(
                            'class'=>'form-control',
                        ))?></td>
                    </tr>
                    <tr>
                        <th class="adaption">别名</th>
                        <td>
                            <?php echo HtmlHelper::inputText('alias', '', array(
                                'class'=>'form-control w150 ib',
                            ))?>
                            <span class="fc-grey">若您不确定它的用途，请不要修改</span>
                        </td>
                    </tr>
                    <tr>
                        <th valign="top" class="adaption">描述</th>
                        <td><?php echo HtmlHelper::textarea('description', '', array(
                            'class'=>'form-control h90 autosize',
                        ))?></td>
                    </tr>
                    <tr>
                        <th class="adaption">排序</th>
                        <td>
                            <?php echo HtmlHelper::inputNumber('sort', '1000', array(
                                'class'=>'form-control w100 ib',
                            ))?>
                            <span class="fc-grey">0-65535之间，数值越小，排序越靠前</span>
                        </td>
                    </tr>
                    <tr>
                        <th class="adaption">导航</th>
                        <td>
                            <?php echo HtmlHelper::inputCheckbox('is_nav', '1', true, array(
                                'label'=>'在导航栏显示',
                            ))?>
                            <span class="fc-grey">（该选项实际效果视主题而定）</span>
                        </td>
                    </tr>
                    <tr>
                        <th valign="top" class="adaption">插图</th>
                        <td><?php echo $this->renderPartial('file/_upload_image', array(
                            'cat'=>'cat',
                            'field'=>'file_id',
                            'preview_image_width'=>'thumbnail',
                            'label'=>'插图',
                        ))?></td>
                    </tr>
                    <tr>
                        <th class="adaption"><a href="javascript:" class="toggle-seo-info" style="font-weight:normal;text-decoration:underline;">SEO信息</a></th>
                        <td></td>
                    </tr>
                    <tr class="hide toggle">
                        <th class="adaption">Title</th>
                        <td><?php echo HtmlHelper::inputText('seo_title', '', array(
                            'class'=>'form-control',
                        ))?></td>
                    </tr>
                    <tr class="hide toggle">
                        <th class="adaption">Keywords</th>
                        <td><?php echo HtmlHelper::inputText('seo_keywords', '', array(
                            'class'=>'form-control',
                        ))?></td>
                    </tr>
                    <tr class="hide toggle">
                        <th valign="top" class="adaption">Description</th>
                        <td><?php echo HtmlHelper::textarea('seo_description', '', array(
                            'class'=>'form-control',
                            'rows'=>5,
                        ))?></td>
                    </tr>
                    <tr>
                        <th class="adaption"></th>
                        <td>
                            <a href="javascript:" class="btn" id="create-form-submit">添加新分类</a>
                            <a href="javascript:" class="btn btn-grey fancybox-close">取消</a>
                        </td>
                    </tr>
                </table>
            <?php echo F::form('edit')->close()?>
        </div>
    </div>
</div>
<script type="text/javascript" src="<?php echo $this->assets('faycms/js/admin/cat.js')?>"></script>
<script>
$(function(){
    cat.init();
    <?php if(!empty($get_cat_url)){?>
    cat.getCatUrl = '<?php echo $get_cat_url?>';
    <?php }?>
})
</script>