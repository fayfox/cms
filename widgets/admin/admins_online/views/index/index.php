<?php
use fay\helpers\DateHelper;

?>
<div class="box" data-name="<?php echo $this->__name?>">
    <div class="box-title">
        <a class="tools remove" title="隐藏"></a>
        <h4>在线管理员</h4>
    </div>
    <div class="box-content">
        <ul class="online-admins">
        <?php foreach($admins as $a){?>
            <li>
                <span class="fl"><?php
                    echo $a['username'];
                    echo $a['nickname'] ? ' - ' . $a['nickname'] : '';
                    echo ' (', long2ip($a['last_login_ip']), ')';
                ?></span>
                <span class="fr"><?php echo DateHelper::niceShort($a['last_login_time'])?></span>
                <div class="clear"></div>
            </li>
        <?php }?>
        </ul>
    </div>
</div>