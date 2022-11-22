<?php

if (!defined('DB_CONFIG')) die();

function fontawesome_hd_head()
{
    global $Loader;

    //$Loader->add_css('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', array('type' => 'url', 'media' => 'screen'));

    $Loader->add_css(BASE_URL.'/apps/fontawesome/6.2.1/css/fontawesome.min.css', array('type' => 'url', 'media' => 'screen'));
	$Loader->add_css(BASE_URL.'/apps/fontawesome/6.2.1/css/regular.min.css', array('type' => 'url', 'media' => 'screen'));
	$Loader->add_css(BASE_URL.'/apps/fontawesome/6.2.1/css/solid.min.css', array('type' => 'url', 'media' => 'screen'));

    //$Loader->add_css(BASE_URL.'/vendor/icofont/icofont.min.css', array('type' => 'url', 'media' => 'screen'));
}

