<div class="row">
    <div class="col-12">
        <div class="tabbable">
            <ul class="nav-tabs">
                <li class="active"><a href="#settings-options">站点参数</a></li>
                <li><a href="#settings-system">系统参数</a></li>
                <li><a href="#settings-sitemap">站点地图</a></li>
                <li><a href="#settings-email">邮箱参数</a></li>
            </ul>
            <div class="tab-content">
                <div id="settings-options" class="tab-pane p5">
                    <?php echo $this->renderPartial('_settings_options')?>
                </div>
                <div id="settings-system" class="tab-pane p5 hide">
                    <?php echo $this->renderPartial('_settings_system')?>
                </div>
                <div id="settings-sitemap" class="tab-pane p5 hide">
                    <?php echo $this->renderPartial('_settings_sitemap')?>
                </div>
                <div id="settings-email" class="tab-pane p5 hide">
                    <?php echo $this->renderPartial('_settings_email')?>
                </div>
            </div>
        </div>
    </div>
</div>