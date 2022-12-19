<?php 

if (!defined('APP_UNINSTALL')) die();

$DBLayer->drop_table('hca_wom_work_orders');
$DBLayer->drop_table('hca_wom_tasks');

// hidden to save data
//$DBLayer->drop_table('hca_wom_types');
//$DBLayer->drop_table('hca_wom_problems');
//$DBLayer->drop_table('hca_wom_items');

$DBLayer->drop_table('hca_wom_tpl_wo');
$DBLayer->drop_table('hca_wom_tpl_tasks');

$DBLayer->drop_table('hca_wom_actions');

// Drop rights
$DBLayer->delete('user_access', 'a_to=\''.$DBLayer->escape('hca_wom').'\'');
$DBLayer->delete('user_permissions', 'p_to=\''.$DBLayer->escape('hca_wom').'\'');
$DBLayer->delete('user_notifications', 'n_to=\''.$DBLayer->escape('hca_wom').'\'');

// Remove files from DB. To delete files from server go to admin panel
$DBLayer->delete('sm_uploader', 'table_name=\'hca_wom\'');

config_remove(array(
	'o_hca_wom_notify_technician',
	'o_hca_wom_notify_managers'
));