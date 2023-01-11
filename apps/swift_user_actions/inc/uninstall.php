<?php 

if (!defined('APP_UNINSTALL')) die();

$DBLayer->drop_table('swift_user_actions');
$DBLayer->drop_table('swift_user_actions_ips');