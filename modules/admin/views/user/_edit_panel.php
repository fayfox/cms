<?php
use cms\models\tables\UsersTable;
use cms\services\file\FileService;
use cms\services\OptionService;
use fay\helpers\HtmlHelper;

/**
 * @var $roles array
 */
?>
<div class="form-field">
    <label class="title bold">登录名<em class="required">*</em></label>
    <?php echo F::form()->inputText('username', array(
        'class'=>'form-control mw400',
        'disabled'=>F::form()->getScene() == 'edit' ? 'disabled' : false,
        'readonly'=>F::form()->getScene() == 'edit' ? 'readonly' : false,
    ))?>
</div>
<div class="form-field">
    <label class="title bold">密码<?php
        if(F::form()->getScene() == 'create'){
            echo HtmlHelper::tag('em', array(
                'class'=>'required',
            ), '*');
        }
    ?></label>
    <?php
        echo F::form()->inputText('password', array(
            'class'=>'form-control mw400',
        ));
        if(F::form()->getScene() == 'edit'){
            echo HtmlHelper::tag('p', array(
                'class'=>'description',
            ), '若为空，则不会修改密码字段');
        }
    ?>
</div>
<?php if($roles){?>
    <div class="form-field">
        <label class="title bold">角色</label>
        <div class="mw400"><?php foreach($roles as $r){
            echo '<span class="ib w200">', F::form()->inputCheckbox('roles[]', $r['id'], array(
                'label'=>$r['title'],
                'class'=>'user-roles',
            )), '</span>';
        }?></div>
    </div>
<?php }?>
<div class="form-field">
    <label class="title bold">手机号码</label>
    <?php echo F::form()->inputText('mobile', array(
            'class'=>'form-control mw400',
        ))?>
</div>
<div class="form-field">
    <label class="title bold">邮箱</label>
    <?php echo F::form()->inputText('email', array(
        'class'=>'form-control mw400',
    ))?>
</div>
<div class="form-field">
    <label class="title bold">昵称<?php if(OptionService::get('system:user_nickname_required')){?>
        <em class="required">*</em>
    <?php }?></label>
    <?php echo F::form()->inputText('nickname', array('class'=>'form-control mw400'))?>
</div>
<div class="form-field">
    <label class="title bold">审核状态</label>
    <?php echo F::form()->select('status', array(
        UsersTable::STATUS_UNCOMPLETED=>'用户信息不完整',
        UsersTable::STATUS_PENDING=>'未审核',
        UsersTable::STATUS_VERIFIED=>'通过审核',
        UsersTable::STATUS_VERIFY_FAILED=>'未通过审核',
    ), array(
        'class'=>'form-control mw400',
    ), UsersTable::STATUS_VERIFIED)?>
</div>
<div class="form-field">
    <label class="title bold">登陆状态</label>
    <?php
        echo F::form()->inputRadio('block', 0, array(
            'wrapper'=>array(
                'tag'=>'label',
                'class'=>'fc-green',
            ),
            'after'=>'正常登录',
        ), true);
        echo F::form()->inputRadio('block', 1, array(
            'wrapper'=>array(
                'tag'=>'label',
                'class'=>'fc-red',
            ),
            'after'=>'限制登录',
        ));
    ?>
</div>
<div class="form-field">
    <label class="title bold">头像</label>
    <div id="avatar-container"><?php
        if(!empty($user['user']['avatar']['id'])){
            echo HtmlHelper::inputHidden('avatar', $user['user']['avatar']['id'], array('id'=>'avatar-id'));
            echo HtmlHelper::link(HtmlHelper::img($user['user']['avatar']['id'], FileService::PIC_RESIZE, array(
                'dw'=>178,
                'dh'=>178,
                'id'=>'avatar-img',
            )), $user['user']['avatar']['url'], array(
                'encode'=>false,
                'title'=>false,
                'data-fancybox'=>null,
            ));
            echo HtmlHelper::link(HtmlHelper::img($user['user']['avatar']['thumbnail'], FileService::PIC_THUMBNAIL, array(
                'id'=>'avatar-img-circle',
            )), $user['user']['avatar']['url'], array(
                'encode'=>false,
                'title'=>false,
                'data-fancybox'=>null,
            ));
        }else{
            echo HtmlHelper::inputHidden('avatar', '0', array('id'=>'avatar-id'));
            echo HtmlHelper::link(HtmlHelper::img($this->assets('images/avatar.png'), 0, array(
                'id'=>'avatar-img',
            )), $this->assets('images/avatar.png'), array(
                'encode'=>false,
                'title'=>false,
                'data-fancybox'=>null,
            ));
            echo HtmlHelper::link(HtmlHelper::img($this->assets('images/avatar.png'), 0, array(
                'id'=>'avatar-img-circle',
            )), $this->assets('images/avatar.png'), array(
                'encode'=>false,
                'title'=>false,
                'data-fancybox'=>null,
            ));
        }
        echo HtmlHelper::link('上传头像', 'javascript:;', array(
            'id'=>'upload-avatar',
            'class'=>'btn btn-grey',
        ));
    ?></div>
</div>
<script type="text/javascript" src="<?php echo $this->assets('faycms/js/admin/user.js')?>"></script>
<script type="text/javascript" src="<?php echo $this->assets('faycms/js/admin/prop.js')?>"></script>
<script>
    prop.init();
</script>