<?php

if (!defined('DB_CONFIG')) die();

function fontawesome_hd_head()
{
    global $Loader;
    $Loader->add_css(BASE_URL.'/apps/fontawesome/css/fontawesome.min.css', array('type' => 'url', 'media' => 'screen'));
	$Loader->add_css(BASE_URL.'/apps/fontawesome/css/regular.min.css', array('type' => 'url', 'media' => 'screen'));
	$Loader->add_css(BASE_URL.'/apps/fontawesome/css/solid.min.css', array('type' => 'url', 'media' => 'screen'));

    $Loader->add_css(BASE_URL.'/vendor/icofont/icofont.min.css', array('type' => 'url', 'media' => 'screen'));
}

