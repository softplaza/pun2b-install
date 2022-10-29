<?php if (!defined('APP_UNINSTALL')) die();

$DBLayer->config_remove('o_jquery_version');
$DBLayer->config_remove('o_jquery_include_method');
$DBLayer->config_remove('o_jquery_2x_version_number');
$DBLayer->config_remove('o_jquery_1x_version_number');

