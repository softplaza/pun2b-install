<?php 

if (!defined('APP_UNINSTALL')) die();

$DBLayer->drop_table('hca_fs_requests');
$DBLayer->drop_table('hca_fs_assignment');
$DBLayer->drop_table('hca_fs_weekly');
$DBLayer->drop_table('hca_fs_tasks');

$DBLayer->drop_table('hca_fs_vacations');
$DBLayer->drop_table('hca_fs_permanent_assignments');
$DBLayer->drop_table('hca_fs_emergency_schedule');

$DBLayer->drop_field('groups', 'hca_fs');
//$DBLayer->drop_field('users', 'hca_fs_access');
$DBLayer->drop_field('users', 'hca_fs_perms');
$DBLayer->drop_field('users', 'hca_fs_mailing');
	
$DBLayer->drop_field('users', 'hca_fs_groups');
$DBLayer->drop_field('users', 'hca_fs_group');

$DBLayer->drop_field('users', 'hca_fs_property_id');
$DBLayer->drop_field('users', 'hca_fs_property_days');
$DBLayer->drop_field('sm_property_db', 'emergency_uid');

config_remove(array(
	'o_hca_fs_msg',//remove
	'o_hca_fs_mailed_property',
	
	'o_hca_fs_mailing_workers',
	'o_hca_fs_geo_codes',
	'o_hca_fs_maintenance',
	'o_hca_fs_painters',
	'o_hca_fs_unit_sizes',
	'o_hca_fs_vcr_mailing_list',
	'o_hca_fs_number_of_week',
));
