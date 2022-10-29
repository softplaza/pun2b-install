<?php

if (!defined('DB_CONFIG')) die();

function fancybox_hd_head()
{
    global $Loader;
    $Loader->add_css('https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css');
    //$Loader->add_css(BASE_URL.'/apps/fancybox/custom.css?'.time());
}

function fancybox_ft_js_include()
{
    global $Config, $Loader;

    $Loader->add_js('https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js', array('type' => 'url', 'async' => false, 'group' => -100 , 'weight' => 75));
    //$Loader->add_js(BASE_URL.'/apps/fancybox/custom.js?'.time(), array('type' => 'url', 'async' => false, 'group' => -100 , 'weight' => 75));
}
