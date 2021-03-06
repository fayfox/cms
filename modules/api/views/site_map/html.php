<?php
use cms\helpers\LinkHelper;
use fay\helpers\HtmlHelper;

/**
 * @var $this \fay\core\View
 * @var $last_post array
 * @var $seo_title string
 * @var $sitename string
 * @var $cat_map array
 * @var $cat_posts array
 * @var $pages array
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title>Site Map</title>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
    <style type="text/css">
        body {
            background-color: #DDD;
            font: normal 80%  "Trebuchet MS", "Helvetica", sans-serif;
            margin:0;
            text-align:center;
        }
        #cont{
            margin:auto;
            width:800px;
            text-align:left;
        }
        a:link,a:visited {
            color: #0180AF;
            text-decoration: underline;
        }
        a:hover {
            color: #666;
        }
        h1 {
            background-color:#fff;
            padding:20px;
            color:#00AEEF;
            text-align:left;
            font-size:32px;
            margin:0px;
        }
        h3 {
            font-size:12px;
            background-color:#B8DCE9;
            margin:0px;
            padding:10px;
        }
        h3 a {
            float:right;
            font-weight:normal;
            display:block;
        }
        th {
            text-align:center;
            background-color:#00AEEF;
            color:#fff;
            padding:4px;
            font-weight:normal;
            font-size:12px;
        }
        td {
            font-size:12px;
            padding:3px;
            text-align:left;
        }
        tr {background: #fff}
        tr:nth-child(odd) {background: #f0f0f0}
        #footer {
            background-color:#B8DCE9;
            padding:10px;
        }
        .pager,.pager a {
            background-color:#00AEEF;
            color:#fff;
            padding:3px;
        }
        .lhead {
            background-color:#fff;
            padding:3px;
            font-weight:bold;
            font-size:16px;
        }
        .lpart {
            background-color:#f0f0f0;
            padding:0px;
        }
        .lpage {
            font:normal 12px verdana;
        }
        .lcount {
            background-color:#00AEEF;
            color:#fff;
            padding:2px;
            margin:2px;
            font:bold 12px verdana;
        }
        a.aemphasis {
            color:#009;
            font-weight:bold;
        }
    </style>

</head>
<body>
<div id="cont">
    <h1>HTML Site Map</h1>
    <h3><a href="<?php echo $this->url()?>">Homepage</a>
        Last updated: <?php echo date('Y, F d')?>
    </h3>


    <table cellpadding="0" cellspacing="0" border="0" width="100%">

        <tr valign="top">
            <td class="lpart" colspan="100"><div class="lhead">/
                    <span class="lcount">1 pages</span></div>

                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr><td class="lpage"><?php echo HtmlHelper::link("{$seo_title}_{$sitename}", $this->url())?></td></tr>
                    <?php foreach($pages as $page){?>
                        <tr><td class="lpage"><?php echo HtmlHelper::link($page['title'], LinkHelper::generatePageLink($page))?></td></tr>
                    <?php }?>
                </table>
            </td>
        </tr>
        <?php foreach($cat_posts as $cat_id => $posts){?>
        <tr valign="top">
            <td class="lbullet">&nbsp;&nbsp;&nbsp;&nbsp;</td>
            <td class="lpart" colspan="99"><div class="lhead"><a href="<?php echo LinkHelper::generateCatLink($cat_map[$cat_id])?>" title="<?php echo HtmlHelper::encode($cat_map[$cat_id]['title'])?>"><?php echo HtmlHelper::encode($cat_map[$cat_id]['title'])?></a>
                    <span class="lcount"><?php echo count($posts)?> 条记录</span></div>

                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                    <?php foreach($posts as $post){?>
                    <tr><td class="lpage"><?php echo HtmlHelper::link($post['title'], LinkHelper::generatePostLink($post))?></td></tr>
                    <?php }?>
                </table>
            </td>
        </tr>
        <?php }?>

    </table>
</div>
</body>
</html>
