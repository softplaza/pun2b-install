<?php

if (!defined('DB_CONFIG')) die();

function punbb_code_PunbbForumPostPreMessage()
{
    global $Hooks;
?>
    <div class="bb-codes mb-2">
        <button type="button" class="btn btn-sm btn-secondary" onclick="insertBBcode('b', 'b')"><i class="fa fa-bold"></i></button>
        <button type="button" class="btn btn-sm btn-secondary" onclick="insertBBcode('i', 'i')"><i class="fa fa-italic"></i></button>
        <button type="button" class="btn btn-sm btn-secondary" onclick="insertBBcode('u', 'u')"><i class="fa fa-underline"></i></button>
        <button type="button" class="btn btn-sm btn-secondary" onclick="insertBBcode('code', 'code')"><i class="fa fa-code"></i></button>
        <button type="button" class="btn btn-sm btn-secondary" onclick="insertBBcode('url', 'url')"><i class="fa fa-link"></i></button>
        <button type="button" class="btn btn-sm btn-secondary" onclick="insertBBcode('img', 'img')"><i class="fa fa-picture-o"></i></button>
        <button type="button" class="btn btn-sm btn-secondary" onclick="insertBBcode('color', 'color')"><i class="fas fa-palette"></i></button>
        <button type="button" class="btn btn-sm btn-secondary" onclick="insertBBcode('spoiler', 'spoiler')"><i class="fas fa-plus-square"></i></button>
        <button type="button" class="btn btn-sm btn-secondary" onclick="insertBBcode('video', 'video')"><i class="fa fa-video"></i></button>
        <?php $Hooks->get_hook('PunbbBBcodeEndAddButton'); ?>
    </div>
<?php
}

function punbb_code_PunbbForumPostsPreMessage()
{
    global $Hooks;
?>
    <div class="bb-codes mb-2">
        <button type="button" class="btn btn-sm btn-secondary" onclick="insertBBcode('b', 'b')"><i class="fa fa-bold"></i></button>
        <button type="button" class="btn btn-sm btn-secondary" onclick="insertBBcode('i', 'i')"><i class="fa fa-italic"></i></button>
        <button type="button" class="btn btn-sm btn-secondary" onclick="insertBBcode('u', 'u')"><i class="fa fa-underline"></i></button>
        <button type="button" class="btn btn-sm btn-secondary" onclick="insertBBcode('code', 'code')"><i class="fa fa-code"></i></button>
        <button type="button" class="btn btn-sm btn-secondary" onclick="insertBBcode('url', 'url')"><i class="fa fa-link"></i></button>
        <button type="button" class="btn btn-sm btn-secondary" onclick="insertBBcode('img', 'img')"><i class="fa fa-picture-o"></i></button>
        <button type="button" class="btn btn-sm btn-secondary" onclick="insertBBcode('color', 'color')"><i class="fas fa-palette"></i></button>
        <button type="button" class="btn btn-sm btn-secondary" onclick="insertBBcode('spoiler', 'spoiler')"><i class="fas fa-plus-square"></i></button>
        <button type="button" class="btn btn-sm btn-secondary" onclick="insertBBcode('video', 'video')"><i class="fa fa-video"></i></button>
        <?php $Hooks->get_hook('PunbbBBcodeEndAddButton'); ?>
    </div>
<?php
}

function punbb_code_hd_head()
{
    global $Loader;

    if (PAGE_SECTION_ID == 'punbb')
    {
        $Loader->add_js(BASE_URL.'/apps/punbb_code/js/bbcode.js', array('type' => 'url', 'async' => false, 'group' => -100 , 'weight' => 75));
        $Loader->add_css(BASE_URL.'/apps/punbb_code/css/style.css?'.time());  
    }
}
