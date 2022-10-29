<?php 

if (!defined('APP_UNINSTALL')) die();

if ($DBLayer->table_exists('sm_pest_control_records'))
	$DBLayer->drop_table('sm_pest_control_records');
	
if ($DBLayer->table_exists('sm_pest_control_events'))
	$DBLayer->drop_table('sm_pest_control_events');

if ($DBLayer->table_exists('sm_pest_control_forms'))
	$DBLayer->drop_table('sm_pest_control_forms');

if ($DBLayer->field_exists('users', 'sm_pest_control_notify_time'))
	$DBLayer->drop_field('users', 'sm_pest_control_notify_time');

if ($DBLayer->field_exists('users', 'sm_pc_notify_by_email'))
	$DBLayer->drop_field('users', 'sm_pc_notify_by_email');

if ($DBLayer->field_exists('users', 'sm_pc_access'))
	$DBLayer->drop_field('users', 'sm_pc_access');

if ($DBLayer->field_exists('sm_vendors', 'sm_pest_control'))
	$DBLayer->drop_field('sm_vendors', 'sm_pest_control');

config_remove(array(
	'o_sm_pest_control_users',
	'o_sm_pest_control_proj_manager_notify',//replaced to sm_pest_control_notify_time
	'o_sm_pest_control_manager_period_notify',
	'o_sm_pest_control_manager_email_msg'
));
