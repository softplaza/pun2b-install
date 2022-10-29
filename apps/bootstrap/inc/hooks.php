<?php

if (!defined('DB_CONFIG')) die();

function bootstrap_hd_head()
{
    global $Loader;
    $Loader->add_css(BASE_URL.'/apps/bootstrap/css/bootstrap.min.css');
}

function bootstrap_ft_js_include()
{
    global $Loader;

    //$Loader->add_js(BASE_URL.'/apps/bootstrap/js/bootstrap.min.js', array('type' => 'url', 'async' => false, 'group' => -100 , 'weight' => 75));
    $Loader->add_js(BASE_URL.'/apps/bootstrap/js/bootstrap.bundle.min.js', array('type' => 'url', 'async' => false, 'group' => -100 , 'weight' => 75));

    // SHOWS Errors: A page or script is accessing at least one of navigator.userAgent, navigator.appVersion, and navigator.platform. Starting in Chrome 101, the amount of information available in the User Agent string will be reduced.
    //$Loader->add_js('https://cdn.jsdelivr.net/npm/docsearch.js@2/dist/cdn/docsearch.min.js', array('type' => 'url', 'async' => false, 'group' => -100 , 'weight' => 75));
}
