<?php 

if (!defined('APP_UNINSTALL')) die();

$DBLayer->drop_table('sm_special_projects_records');
$DBLayer->drop_table('sm_special_projects_invoices');
$DBLayer->drop_table('sm_special_projects_events');
$DBLayer->drop_table('sm_special_projects_actions');
$DBLayer->drop_table('sm_special_projects_forms');
$DBLayer->drop_table('sm_special_projects_files');
$DBLayer->drop_table('sm_special_projects_recommendations');

$DBLayer->drop_field('users', 'sm_special_projects_access');
$DBLayer->drop_field('users', 'sm_special_projects_permission');
$DBLayer->drop_field('users', 'sm_special_projects_notify_time');
$DBLayer->drop_field('users', 'sm_special_projects_details');
$DBLayer->drop_field('users', 'sm_sp_mailing');
$DBLayer->drop_field('sm_vendors', 'hca_sp');
/*
config_remove([
	''
]);
*/