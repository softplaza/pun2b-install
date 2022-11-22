<?php

// Use the main stylesheet to set up general rules for all color themes.
$Loader->add_css(BASE_URL.'/style/main.css?v='.time(), [
    'type' => 'url', 
    'group' => SPM_CSS_GROUP_SYSTEM, 
    'media' => 'screen'
]);

// Use a custom stylesheet to customize each theme individually
$Loader->add_css(BASE_URL.'/style/'.$User->get('style').'/custom.css?v='.time(), [
    'type' => 'url', 
    'group' => SPM_CSS_GROUP_SYSTEM, 
    'media' => 'screen'
]);

$Loader->add_css(BASE_URL.'/vendor/bootstrap/css/bootstrap.min.css');

$Loader->add_js(BASE_URL.'/vendor/jquery/js/jquery-2.2.4.min.js', [
    'type' => 'url', 
    'async' => false, 
    'group' => -100 , 
    'weight' => 75
]);

$Loader->add_js(BASE_URL.'/vendor/bootstrap/js/bootstrap.bundle.min.js', [
    'type' => 'url', 
    'async' => false, 
    'group' => -100 , 
    'weight' => 75
]);

//$Loader->add_css(BASE_URL.'/vendor/fontawesome/6.2.1/css/fontawesome.min.css', array('type' => 'url', 'media' => 'screen'));
//$Loader->add_css(BASE_URL.'/vendor/fontawesome/6.2.1/css/regular.min.css', array('type' => 'url', 'media' => 'screen'));
//$Loader->add_css(BASE_URL.'/vendor/fontawesome/6.2.1/css/solid.min.css', array('type' => 'url', 'media' => 'screen'));

$Loader->add_css(BASE_URL.'/vendor/icofont/icofont.min.css', array('type' => 'url', 'media' => 'screen'));
