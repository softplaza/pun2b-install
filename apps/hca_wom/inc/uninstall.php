<?php 

if (!defined('APP_UNINSTALL')) die();

$DBLayer->drop_table('hca_wom_work_orders');
$DBLayer->drop_table('hca_wom_tasks');
//$DBLayer->drop_table('hca_wom_items');
$DBLayer->drop_table('hca_wom_actions');

$DBLayer->delete('user_access', 'a_to=\''.$DBLayer->escape('hca_wom').'\'');
$DBLayer->delete('user_permissions', 'p_to=\''.$DBLayer->escape('hca_wom').'\'');
$DBLayer->delete('user_notifications', 'n_to=\''.$DBLayer->escape('hca_wom').'\'');