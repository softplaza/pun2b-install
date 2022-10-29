<?php 

if (!defined('APP_INSTALL')) die();

$schema = array(
	'FIELDS'		=> array(
		'id' => $DBLayer->dt_serial(),
		'payee_id'			=> $DBLayer->dt_varchar(),
		'vendor_name'		=> $DBLayer->dt_varchar(),
		'phone_number'		=> $DBLayer->dt_varchar(),
		'email'				=> $DBLayer->dt_varchar(),
		'service'			=> $DBLayer->dt_varchar(),
		'group_id'			=> $DBLayer->dt_int(),
		'orders_limit'		=> $DBLayer->dt_int('TINYINT(3) UNSIGNED'),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('sm_vendors', $schema);

$schema = array(
	'FIELDS'		=> array(
		'id' 				=> $DBLayer->dt_serial(),
		'group_name'		=> $DBLayer->dt_varchar(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('sm_vendors_groups', $schema);

$schema = array(
	'FIELDS' => array(
		'id' => $DBLayer->dt_serial(),
		'project_name'		=> $DBLayer->dt_varchar(),
		'project_id'		=> $DBLayer->dt_int(),
		'vendor_id'			=> $DBLayer->dt_int(),
		'vendor_group_id'	=> $DBLayer->dt_int(),
		'po_number'			=> $DBLayer->dt_varchar(),
		'start_date'		=> $DBLayer->dt_int(),
		'remarks'			=> $DBLayer->dt_text(),
		'week_of'			=> $DBLayer->dt_int(),
		'assoc_table_name'	=> $DBLayer->dt_varchar(),
		'assoc_table_id'	=> $DBLayer->dt_int(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('sm_vendors_schedule', $schema);

$schema = array(
	'FIELDS' => array(
		'id' => $DBLayer->dt_serial(),
		'project_name'		=> $DBLayer->dt_varchar(),
		'project_id'		=> $DBLayer->dt_int(),
		'vendor_id'			=> $DBLayer->dt_int(),
		'vendor_group_id'	=> $DBLayer->dt_int(),
		'po_number'			=> $DBLayer->dt_varchar(),
		'start_date'		=> $DBLayer->dt_int(),
		'remarks'			=> $DBLayer->dt_text(),
		'week_of'			=> $DBLayer->dt_int(),
		'assoc_table_name'	=> $DBLayer->dt_varchar(),
		'assoc_table_id'	=> $DBLayer->dt_int(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('sm_vendor_schedule', $schema);

//$DBLayer->add_field('users', 'sm_vendors_access', 'TINYINT(1)', false, '0');
//$DBLayer->add_field('users', 'sm_vendors_perms', 'VARCHAR(255)', false, '');
$DBLayer->drop_field('users', 'sm_vendors_access');
$DBLayer->drop_field('users', 'sm_vendors_perms');

$DBLayer->add_field('sm_vendors', 'group_id', 'INT(10) UNSIGNED', false, '0');
	
//2021/06/16
$DBLayer->add_field('sm_vendors_schedule', 'project_name', 'VARCHAR(160)', false, '');
$DBLayer->add_field('sm_vendors_schedule', 'project_id', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('sm_vendors_schedule', 'vendor_group_id', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('sm_vendors_schedule', 'po_number', 'VARCHAR(80)', false, '');
$DBLayer->add_field('sm_vendors_schedule', 'po_number', 'TEXT', false);
$DBLayer->add_field('sm_vendors', 'orders_limit', 'TINYINT(3) UNSIGNED', false, '0');
