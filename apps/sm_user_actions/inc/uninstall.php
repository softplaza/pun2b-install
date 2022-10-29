<?php 

if (!defined('APP_UNINSTALL')) die();

if ($DBLayer->table_exists('sm_user_actions'))
	$DBLayer->drop_table('sm_user_actions');
