<?php 

if (!defined('APP_INSTALL')) die();

// For apply any changes go to manifest and up version
$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'property_id'			=> $DBLayer->dt_int(),
		'property_name'			=> $DBLayer->dt_varchar(),
		'unit_number'			=> $DBLayer->dt_varchar(),
		'geo_code'				=> $DBLayer->dt_varchar(),
		'group_id'				=> $DBLayer->dt_int(),
		'employee_id'			=> $DBLayer->dt_int(),
		//'employee_name'			=> $DBLayer->dt_varchar(), //remove
		'week_of'				=> $DBLayer->dt_int(),
		'created'				=> $DBLayer->dt_int(),
		'scheduled'				=> $DBLayer->dt_int(),
		'start_date'			=> $DBLayer->dt_int(),
		'end_date'				=> $DBLayer->dt_int(),
		'new_start_date'		=> $DBLayer->dt_int(),
		'time_slot'				=> $DBLayer->dt_int('TINYINT(1)'),
		'mailed_time'			=> $DBLayer->dt_int(),
		'viewed_time'			=> $DBLayer->dt_int(),
		'submitted_time'		=> $DBLayer->dt_int(),
		'completed_time'		=> $DBLayer->dt_int(),
		'request_msg'			=> $DBLayer->dt_text(),
		'msg_for_maint'			=> $DBLayer->dt_text(),
		'msg_from_maint'		=> $DBLayer->dt_text(),
		'remarks'				=> $DBLayer->dt_text(),
		'work_status'			=> $DBLayer->dt_int('TINYINT(1)'),
		'day_off'				=> $DBLayer->dt_int('TINYINT(1)'),
		'requested_by'			=> $DBLayer->dt_varchar(),
		'permission_enter'		=> $DBLayer->dt_int('TINYINT(1)'),
		'execution_priority'	=> $DBLayer->dt_int('TINYINT(1)'),
		'has_animal'			=> $DBLayer->dt_int('TINYINT(1)'),
		'template_type'			=> $DBLayer->dt_int('TINYINT(1)'),
		'date_start'			=> $DBLayer->dt_date(),
		'start_time'			=> $DBLayer->dt_time(),
		'end_time'				=> $DBLayer->dt_time(),
		'date_requested'		=> $DBLayer->dt_date(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_fs_requests', $schema);

// hca_fs_weekly
$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'user_id'				=> $DBLayer->dt_int(),
		'week_of'				=> $DBLayer->dt_int(),
		'mailed_time'			=> $DBLayer->dt_int(),
		'submitted_time'		=> $DBLayer->dt_int(),
		'hash'					=> $DBLayer->dt_varchar(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_fs_weekly', $schema);

// hca_fs_vacations
$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'user_id'				=> $DBLayer->dt_int(),
		'start_date'			=> $DBLayer->dt_int(),
		'end_date'				=> $DBLayer->dt_int(),
		'date_off'				=> $DBLayer->dt_date(),
		'off_type'				=> $DBLayer->dt_int('TINYINT(1)'),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_fs_vacations', $schema);

// hca_fs_permanent_assignments
$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'user_id'				=> $DBLayer->dt_int(),
		'group_id'				=> $DBLayer->dt_int(),
		'property_id'			=> $DBLayer->dt_int(),
		'day_of_week'			=> $DBLayer->dt_int('TINYINT(1)'),
		'time_slot'				=> $DBLayer->dt_int('TINYINT(1)'),
		'start_time'			=> $DBLayer->dt_int(),
		'end_time'				=> $DBLayer->dt_int(),
		'start_date'			=> $DBLayer->dt_int(),
		'end_date'				=> $DBLayer->dt_int(),
		'time_shift'			=> $DBLayer->dt_int('TINYINT(1)', false, '1'),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_fs_permanent_assignments', $schema);

// hca_fs_emergency_schedule
$schema = array(
	'FIELDS'		=> array(
		'id'				=> $DBLayer->dt_serial(),
		'user_id'			=> $DBLayer->dt_int(),
		'zone'				=> $DBLayer->dt_int('TINYINT(1)'),
		'week_of'			=> $DBLayer->dt_int(),
		'date_week_of'		=> $DBLayer->dt_date(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_fs_emergency_schedule', $schema);

$schema = [
	'FIELDS'		=> [
		'id'						=> $DBLayer->dt_serial(),
		'request_id'				=> $DBLayer->dt_int(),
		'request_text'				=> $DBLayer->dt_text(),
		'completion_text'			=> $DBLayer->dt_text(),
		'time_start'				=> $DBLayer->dt_time(),
		'time_end'					=> $DBLayer->dt_time(),
	],
	'PRIMARY KEY'	=> ['id']
];
$DBLayer->create_table('hca_fs_tasks', $schema);

$DBLayer->add_field('groups', 'hca_fs', 'TINYINT(1)', false, '0'); // DO NOT REMOVE!!!
//$DBLayer->add_field('users', 'hca_fs_access', 'TINYINT(1)', false, '0');
$DBLayer->add_field('users', 'hca_fs_mailing', 'VARCHAR(255)', false, '');
$DBLayer->add_field('users', 'hca_fs_perms', 'VARCHAR(255)', false, '');

$DBLayer->add_field('users', 'hca_fs_group', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('users', 'hca_fs_property_id', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('users', 'hca_fs_zone', 'TINYINT(1)', false, '0');
$DBLayer->add_field('sm_property_db', 'emergency_uid', 'INT(10) UNSIGNED', false, '0');

// New date fields
$DBLayer->add_field('hca_fs_requests', 'template_type', 'TINYINT(1)', false, '0');
$DBLayer->add_field('hca_fs_requests', 'start_time', 'TIME', false, '00:00:00');
$DBLayer->add_field('hca_fs_requests', 'end_time', 'TIME', false, '00:00:00');

$DBLayer->add_field('hca_fs_requests', 'date_requested', 'DATE', false, '1000-01-01');

// use serialised array (day => property_id)
//$DBLayer->add_field('users', 'hca_fs_property_days', 'VARCHAR(255)', false); // REMOVE
//$DBLayer->add_field('users', 'hca_fs_groups', 'VARCHAR(255)', false, ''); // REMOVE
$DBLayer->drop_field('hca_fs_requests', 'vcr_id');

config_add('o_hca_fs_msg', 'Please see the schedule for the week.');//remove
//config_add('o_hca_fs_mailed_property', '0'); // remove
//config_add('o_hca_fs_mailing_workers', '0'); // remove
config_add('o_hca_fs_geo_codes', '');
config_add('o_hca_fs_maintenance', '0');
config_add('o_hca_fs_painters', '0');
config_add('o_hca_fs_unit_sizes', '');//Replace on Prop Mngmnt
config_add('o_hca_fs_vcr_mailing_list', '');
config_add('o_hca_fs_number_of_week', '8');
