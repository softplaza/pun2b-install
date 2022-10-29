<?php if (!defined('APP_INSTALL')) die();

$DBLayer->config_add('o_jquery_version', '0');
$DBLayer->config_add('o_jquery_include_method', '0');

$jquery_1x = @file_get_contents('http://cdn.jsdelivr.net/jquery/1/jquery.min.js');
preg_match('/v(\d+\.\d+\.\d+)/', $jquery_1x, $match1);
$DBLayer->config_add('o_jquery_1x_version_number', $match1[1]);
@chmod($app_info['path'].'/js/', 0777);
file_put_contents($app_info['path'].'/js/jquery-'.$match1[1].'.min.js', $jquery_1x);

$jquery_2x = @file_get_contents('http://cdn.jsdelivr.net/jquery/2/jquery.min.js');
preg_match('/v(\d+\.\d+\.\d+)/', $jquery_2x, $match2);
$DBLayer->config_add('o_jquery_2x_version_number', $match2[1]);
file_put_contents($app_info['path'].'/js/jquery-'.$match2[1].'.min.js', $jquery_2x);
