<?php

if (!defined('DB_CONFIG')) die();

function swift_updater_es_essentials()
{
    require SITE_ROOT.'apps/swift_updater/inc/functions.php';
}

function swift_updater_hd_head()
{
    global $User, $Loader;
    
    if (file_exists(SITE_ROOT.'apps/swift_updater/css/'.$User->get('style').'.css'))
        $Loader->add_css(BASE_URL.'/apps/swift_updater/css/'.$User->get('style').'.css?v='.time(), array('type' => 'url', 'media' => 'screen'));
    else
        $Loader->add_css(BASE_URL.'/apps/swift_updater/css/Default.css?v='.time(), array('type' => 'url', 'media' => 'screen'));
}

