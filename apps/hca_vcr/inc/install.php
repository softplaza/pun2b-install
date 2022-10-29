<?php 

if (!defined('APP_INSTALL')) die();

$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'property_id'			=> $DBLayer->dt_int(),
		'property_name'			=> $DBLayer->dt_varchar(),
		'unit_number'			=> $DBLayer->dt_varchar(),
		'unit_size'				=> $DBLayer->dt_varchar(),
		'move_out_date'			=> $DBLayer->dt_int(),
		'move_out_comment'		=> $DBLayer->dt_text(),
		'pre_walk_name'			=> $DBLayer->dt_varchar(),
		'pre_walk_date'			=> $DBLayer->dt_int(),
		'pre_walk_comment'		=> $DBLayer->dt_text(),
		'maint_fs_req_id'		=> $DBLayer->dt_int(),
		'paint_inhouse'			=> $DBLayer->dt_int('TINYINT(1)'),
		'paint_fs_req_id'		=> $DBLayer->dt_int(),
		'crpt_urine_scan'		=> $DBLayer->dt_int('TINYINT(1)'),//????
		'urine_scan'			=> $DBLayer->dt_int('TINYINT(1)'),//????
		'crpt_replaced'			=> $DBLayer->dt_int('TINYINT(1)'),
		'crpt_repair'			=> $DBLayer->dt_int('TINYINT(1)'),
		'vinyl_replaced'		=> $DBLayer->dt_int('TINYINT(1)'),
		'refinish_check'		=> $DBLayer->dt_int('TINYINT(1)'),
		'walk'					=> $DBLayer->dt_varchar(),
		'walk_date'				=> $DBLayer->dt_int(),
		'walk_comment'			=> $DBLayer->dt_text(),
		'move_in_date'			=> $DBLayer->dt_int(),
		'move_in_comment'		=> $DBLayer->dt_text(),
		'remarks'				=> $DBLayer->dt_text(),
		'submited_by'			=> $DBLayer->dt_varchar(),
		'submited_by_uid'		=> $DBLayer->dt_int(),
		'submited_date'			=> $DBLayer->dt_int(),
		'status'				=> $DBLayer->dt_int('TINYINT(1)'),
		'turn_over_id'			=> $DBLayer->dt_int(),
		'fs_req_id'				=> $DBLayer->dt_int(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_vcr_projects', $schema);

$schema = array(
	'FIELDS'		=> array(
		'id'				=> $DBLayer->dt_serial(),
		'project_id'		=> $DBLayer->dt_int(),
		'project_name'		=> $DBLayer->dt_varchar(),
		'vendor_id'			=> $DBLayer->dt_int(),
		'vendor_group_id'	=> $DBLayer->dt_int(),
//		'vendor_name'		=> $DBLayer->dt_varchar(),//remove
		'po_number'			=> $DBLayer->dt_varchar(),
//		'date_time'			=> $DBLayer->dt_int(), //replace to...
		'start_date'		=> $DBLayer->dt_int(), // this
		'shift'				=> $DBLayer->dt_int('TINYINT(1)'),
		'remarks'			=> $DBLayer->dt_text(),
//		'emailed_time'		=> $DBLayer->dt_int(),//remove
		'in_house'			=> $DBLayer->dt_int('TINYINT(1)'),
		'fs_request_id'		=> $DBLayer->dt_int(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_vcr_invoices', $schema);

$schema = array(
	'FIELDS'		=> array(
		'id'				=> $DBLayer->dt_serial(),
		'vendor_id'			=> $DBLayer->dt_int(),
		'group_id'			=> $DBLayer->dt_int('TINYINT(3)'),
		'enabled'			=> $DBLayer->dt_int('TINYINT(1)', false, '1'),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_vcr_vendors', $schema);

$DBLayer->add_field('users', 'hca_vcr_access', 'TINYINT(1)', false, '0');
$DBLayer->add_field('users', 'hca_vcr_perms', 'VARCHAR(255)', false, '');
$DBLayer->add_field('users', 'hca_vcr_notify', 'VARCHAR(255)', false, '');
$DBLayer->add_field('users', 'hca_vcr_groups', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('users', 'hca_vcr_group', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('sm_vendors', 'hca_vcr', 'TINYINT(1)', false, '1');

// 2021-07-13
$DBLayer->add_field('hca_vcr_projects', 'crpt_clean_vendor_id', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('hca_vcr_invoices', 'project_name', 'VARCHAR(255)', false, 'hca_vcr_projects');
//	0 - Any time, 1 - All day, 2 - A.M., 3 - P.M.
$DBLayer->add_field('hca_vcr_invoices', 'shift', 'TINYINT(1)', false, '0');
$DBLayer->add_field('hca_vcr_invoices', 'start_date', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('hca_vcr_invoices', 'fs_request_id', 'INT(10) UNSIGNED', false, '0');

config_add('o_hca_vcr_home_office_emails', '');
config_add('o_hca_vcr_default_carpet_vendor', '0');
config_add('o_hca_vcr_default_vinyl_vendor', '0');
config_add('o_hca_vcr_default_urine_vendor', '0');
config_add('o_hca_vcr_default_pest_vendor', '0');
config_add('o_hca_vcr_default_cleaning_vendor', '0');
config_add('o_hca_vcr_default_painter_vendor', '0');
config_add('o_hca_vcr_default_refinish_vendor', '0');
config_add('o_hca_vcr_email_text_vendor_schedule', '0');
config_add('o_hca_vcr_complete_expired_days', '0');

