<?php
use cms\models\tables\MenusTable;
use fay\helpers\HtmlHelper;

F::form('create')->setModel(MenusTable::model());
F::form('edit')->setModel(MenusTable::model());

/**
 * @var $root array
 */
?>
<div class="hide">
    <div id="edit-menu-dialog" class="dialog">
        <div class="dialog-content w550">
            <h4>编辑菜单<em>（当前菜单：<span id="edit-menu-title" class="fc-orange"></span>）</em></h4>
            <?php echo F::form('edit')->open(array('cms/admin/menu/edit'), 'post', array(
                'class'=>'form-inline',
            ))?>
                <?php echo HtmlHelper::inputHidden('id')?>
                <table class="form-table">
                    <tr>
                        <th class="adaption">标题<em class="required">*</em></th>
                        <td>
                            <?php echo HtmlHelper::inputText('title', '', array(
                                'class'=>'form-control',
                            ))?>
                            <span class="fc-grey">主显标题</span>
                        </td>
                    </tr>
                    <tr>
                        <th valign="top" class="adaption">链接地址</th>
                        <td>
                            <?php echo HtmlHelper::inputText('link', '', array(
                                'class'=>'form-control wp100',
                            ))?>
                            <p class="fc-grey">若是本站地址，域名部分用<span class="fc-red">{$base_url}</span>代替</p>
                            <p class="fc-grey">若是外站地址，不要忘了http(s)://</p>
                        </td>
                    </tr>
                    <tr>
                        <th class="adaption">别名</th>
                        <td>
                            <?php echo HtmlHelper::inputText('alias', '', array(
                                'class'=>'form-control',
                            ))?>
                            <span class="fc-grey">别名用于特殊调用，不可重复，可为空</span>
                        </td>
                    </tr>
                    <tr>
                        <th class="adaption">二级标题</th>
                        <td>
                            <?php echo HtmlHelper::inputText('sub_title', '', array(
                                'class'=>'form-control',
                            ))?>
                            <span class="fc-grey">该字段用途视主题而定</span>
                        </td>
                    </tr>
                    <tr>
                        <th class="adaption">class</th>
                        <td>
                            <?php echo HtmlHelper::inputText('css_class', '', array(
                                'class'=>'form-control',
                            ))?>
                            <span class="fc-grey">该字段效果视主题而定</span>
                        </td>
                    </tr>
                    <tr>
                        <th class="adaption">是否启用</th>
                        <td><?php
                            echo HtmlHelper::inputRadio('enabled', 1, false, array(
                                'label'=>'是',
                            ));
                            echo HtmlHelper::inputRadio('enabled', 0, false, array(
                                'label'=>'否',
                            ));
                        ?></td>
                    </tr>
                    <tr>
                        <th class="adaption">排序</th>
                        <td>
                            <?php echo HtmlHelper::inputText('sort', '100', array(
                                'class'=>'form-control w100',
                            ))?>
                            <span class="fc-grey">0-255之间，数值越小，排序越靠前</span>
                        </td>
                    </tr>
                    <tr>
                        <th class="adaption">打开方式</th>
                        <td>
                            <?php echo HtmlHelper::select('target', array(
                                ''=>'默认',
                                '_blank'=>'_blank — 新窗口或新标签',
                                '_top'=>'_top — 不包含框架的当前窗口或标签',
                                '_self'=>'_self — 同一窗口或标签',
                            ), '', array(
                                'class'=>'form-control',
                            ))?>
                        </td>
                    </tr>
                    <tr>
                        <th class="adaption">父节点</th>
                        <td>
                            <?php echo HtmlHelper::select('parent', array(
                                $root['id']=>'根节点',
                            )+HtmlHelper::getSelectOptions($menus, 'id', 'title'), '', array(
                                'class'=>'form-control',
                            ))?>
                        </td>
                    </tr>
                    <tr>
                        <th class="adaption"></th>
                        <td>
                            <?php echo F::form('edit')->submitLink('编辑菜单', array(
                                'class'=>'btn',
                            ))?>
                            <a href="javascript:" class="btn btn-grey fancybox-close">取消</a>
                        </td>
                    </tr>
                </table>
            <?php echo F::form('edit')->close()?>
        </div>
    </div>
</div>
<div class="hide">
    <div id="create-menu-dialog" class="dialog">
        <div class="dialog-content w550">
            <h4>添加子项<em>（父节点：<span id="create-menu-parent" class="fc-orange"></span>）</em></h4>
            <?php echo F::form('create')->open(array('cms/admin/menu/create'), 'post', array(
                'class'=>'form-inline',
            ))?>
                <?php echo HtmlHelper::inputHidden('parent')?>
                <table class="form-table">
                    <tr>
                        <th class="adaption">标题<em class="required">*</em></th>
                        <td>
                            <?php echo HtmlHelper::inputText('title', '', array(
                                'class'=>'form-control',
                            ))?>
                            <span class="fc-grey">主显标题</span>
                        </td>
                    </tr>
                    <tr>
                        <th valign="top" class="adaption">链接地址</th>
                        <td>
                            <?php echo HtmlHelper::inputText('link', '{$base_url}', array(
                                'class'=>'form-control wp100',
                            ))?>
                            <p class="fc-grey">若是本站地址，域名部分用<span class="fc-red">{$base_url}</span>代替</p>
                            <p class="fc-grey">若是外站地址，不要忘了http(s)://</p>
                        </td>
                    </tr>
                    <tr>
                        <th class="adaption">别名</th>
                        <td>
                            <?php echo HtmlHelper::inputText('alias', '', array(
                                'class'=>'form-control',
                            ))?>
                            <span class="fc-grey">别名用于特殊调用，不可重复，可为空</span>
                        </td>
                    </tr>
                    <tr>
                        <th class="adaption">二级标题</th>
                        <td>
                            <?php echo HtmlHelper::inputText('sub_title', '', array(
                                'class'=>'form-control',
                            ))?>
                            <span class="fc-grey">该字段用途视主题而定</span>
                        </td>
                    </tr>
                    <tr>
                        <th class="adaption">css class</th>
                        <td>
                            <?php echo HtmlHelper::inputText('css_class', '', array(
                                'class'=>'form-control',
                            ))?>
                            <span class="fc-grey">该字段效果视主题而定</span>
                        </td>
                    </tr>
                    <tr>
                        <th class="adaption">是否启用</th>
                        <td><?php
                            echo HtmlHelper::inputRadio('enabled', 1, true, array(
                                'label'=>'是',
                            ));
                            echo HtmlHelper::inputRadio('enabled', 0, false, array(
                                'label'=>'否',
                            ));
                        ?></td>
                    </tr>
                    <tr>
                        <th class="adaption">排序</th>
                        <td>
                            <?php echo HtmlHelper::inputText('sort', '100', array(
                                'class'=>'form-control w100',
                            ))?>
                            <span class="fc-grey">0-255之间，数值越小，排序越靠前</span>
                        </td>
                    </tr>
                    <tr>
                        <th class="adaption">打开方式</th>
                        <td>
                            <?php echo HtmlHelper::select('target', array(
                                ''=>'默认',
                                '_blank'=>'_blank — 新窗口或新标签',
                                '_top'=>'_top — 不包含框架的当前窗口或标签',
                                '_self'=>'_self — 同一窗口或标签',
                            ), '', array(
                                'class'=>'form-control',
                            ))?>
                        </td>
                    </tr>
                    <tr>
                        <th class="adaption"></th>
                        <td>
                            <?php echo F::form('create')->submitLink('添加新菜单', array(
                                'class'=>'btn',
                            ))?>
                            <a href="javascript:" class="btn btn-grey fancybox-close">取消</a>
                        </td>
                    </tr>
                </table>
            <?php echo F::form('create')->close()?>
        </div>
    </div>
</div>
<script type="text/javascript" src="<?php echo $this->assets('faycms/js/admin/fayfox.editsort.js')?>"></script>
<script>
var menu = {
    'events':function(){
        $('.tree-container').on('click', '.leaf-title.parent', function(){
            $li = $(this).parent().parent();
            if($li.hasClass('close')){
                $li.children('ul').slideDown(function(){
                    $li.removeClass('close');
                });
            }else{
                $li.children('ul').slideUp(function(){
                    $li.addClass('close');
                });
            }
        });

        $('.edit-sort').feditsort({
            'url':system.url('cms/admin/menu/sort')
        });
    },
    'editMenu':function(){
        common.loadFancybox(function(){
            $('.edit-menu-link').fancybox({
                'onComplete': function(instance, slide){
                    $('#edit-menu-form').find('.submit-loading').remove();
                    $('#edit-menu-dialog').block({
                        'zindex': 120000
                    });
                    $.ajax({
                        type: 'GET',
                        url: system.url('cms/admin/menu/get'),
                        data: {'id': slide.opts.$orig.attr('data-id')},
                        dataType: 'json',
                        cache: false,
                        success: function(resp){
                            var $editMenuDialog = $('#edit-menu-dialog');
                            $editMenuDialog.unblock();
                            if(resp.status){
                                $('#edit-menu-title').text(resp.data.menu.title);
                                $editMenuDialog.find("input[name='id']").val(resp.data.menu.id);
                                $editMenuDialog.find("input[name='title']").val(resp.data.menu.title);
                                $editMenuDialog.find("input[name='sub_title']").val(resp.data.menu.sub_title);
                                $editMenuDialog.find("input[name='css_class']").val(resp.data.menu.css_class);
                                $editMenuDialog.find("input[name='enabled'][value='"+resp.data.menu.enabled+"']").prop('checked', 'checked');
                                $editMenuDialog.find("input[name='alias']").val(resp.data.menu.alias);
                                $editMenuDialog.find("input[name='sort']").val(resp.data.menu.sort);
                                $editMenuDialog.find("input[name='link']").val(resp.data.menu.link);
                                $editMenuDialog.find("select[name='target']").val(resp.data.menu.target);
                                $editMenuDialog.find("select[name='parent']").val(resp.data.menu.parent);
                                //父节点不能被挂载到其子节点上
                                $editMenuDialog.find("select[name='parent'] option").attr('disabled', false).each(function(){
                                    if(system.inArray($(this).attr("value"), resp.data.menu.children) || $(this).attr("value") == resp.data.menu.id){
                                        $(this).attr('disabled', 'disabled');
                                    }
                                });
                                
                            }else{
                                common.alert(resp.message);
                            }
                        }
                    });
                }
            });
        });
    },
    'createMenu':function(){
        common.loadFancybox(function(){
            $('.create-menu-link').fancybox({
                'onComplete': function(instance, slide){
                    $('#create-menu-parent').text(slide.opts.$orig.attr('data-title'));
                    $('#create-menu-dialog').find('input[name="parent"]').val(slide.opts.$orig.attr('data-id'));
                }
            });
        });
    },
    'enabled':function(){
        $('.tree-container').on('click', '.enabled-link', function(){
            var o = this;
            $(this).find('span').hide().after('<img src="'+system.assets('images/throbber.gif')+'" />');
            $.ajax({
                type: 'GET',
                url: system.url('cms/admin/menu/set-enabled'),
                data: {
                    'id': $(this).attr('data-id'),
                    'enabled': $(this).find('span').hasClass('tick-circle') ? 0 : 1
                },
                dataType: 'json',
                cache: false,
                success: function(resp){
                    if(resp.status){
                        $(o).find('span').removeClass('tick-circle')
                            .removeClass('cross-circle')
                            .addClass(resp.data.enabled == 1 ? 'tick-circle' : 'cross-circle')
                            .show()
                            .next('img').remove();
                    }else{
                        common.alert(resp.message);
                    }
                }
            });
            return false;
        });
    },
    'init':function(){
        this.events();
        this.editMenu();
        this.createMenu();
        this.enabled();
    }
};
$(function(){
    menu.init();
})
</script>