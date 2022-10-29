<?php 

if (!defined('APP_INSTALL')) die();

$schema = [
	'FIELDS'		=> [
		'id'					=> $DBLayer->dt_serial(),
		'pro_name'				=> $DBLayer->dt_varchar(),
		'zone'					=> $DBLayer->dt_int('TINYINT(1)'),
		'enabled'				=> $DBLayer->dt_int('TINYINT(1)', false, '1'),
// REMOVE start
		'pro_address'			=> $DBLayer->dt_varchar(),
		'max_apt_num'			=> $DBLayer->dt_int(),
		'manual_numbering'		=> $DBLayer->dt_int(),
// REMOVE end
		
		'manager_id'			=> $DBLayer->dt_int(), // need to be REMOVE - see profile settings
		'manager_name'			=> $DBLayer->dt_varchar(),
		'manager_email'			=> $DBLayer->dt_varchar(),
		'emergency_uid'			=> $DBLayer->dt_int(),
		'total_bldgs'			=> $DBLayer->dt_int(),
		'total_units'			=> $DBLayer->dt_int(),
		'display_position'		=> $DBLayer->dt_int(),
		'map_link'				=> $DBLayer->dt_varchar(),
		'office_address'		=> $DBLayer->dt_varchar(),
		'office_phone'			=> $DBLayer->dt_varchar('VARCHAR(80)', false, ''),
		'office_fax'			=> $DBLayer->dt_varchar('VARCHAR(80)', false, ''),

		// Optional
		'water_heater'			=> $DBLayer->dt_int('TINYINT(1)'),
		'hvac'					=> $DBLayer->dt_int('TINYINT(1)'),
		'washers'				=> $DBLayer->dt_int('TINYINT(1)'),
		'attics'				=> $DBLayer->dt_int('TINYINT(1)'),
		'furnace'				=> $DBLayer->dt_int('TINYINT(1)'),
		'hash'					=> $DBLayer->dt_varchar(),
	],
	'PRIMARY KEY'	=> ['id']
];
$DBLayer->create_table('sm_property_db', $schema);

$schema = [
	'FIELDS'		=> [
		'id'					=> $DBLayer->dt_serial(),
		'property_id'			=> $DBLayer->dt_int(),
		'bldg_number'			=> $DBLayer->dt_varchar(),

		// need to be install
		'bldg_desc'				=> $DBLayer->dt_varchar(),
		'pos_x'					=> $DBLayer->dt_varchar('VARCHAR(20)', false, ''),
		'pos_y'					=> $DBLayer->dt_varchar('VARCHAR(20)', false, ''),
	],
	'PRIMARY KEY'	=> ['id']
];
$DBLayer->create_table('sm_property_buildings', $schema);

$schema = [
	'FIELDS'		=> [
		'id'					=> $DBLayer->dt_serial(),
		'unit_number'			=> $DBLayer->dt_varchar(),
		'property_id'			=> $DBLayer->dt_int(),
		'bldg_id'				=> $DBLayer->dt_int(),
		'map_id'				=> $DBLayer->dt_int(),
		'unit_type'				=> $DBLayer->dt_varchar(),
		'square_feet'			=> $DBLayer->dt_varchar(),
		'street_address'		=> $DBLayer->dt_varchar(),
		'city'					=> $DBLayer->dt_varchar(),
		'state'					=> $DBLayer->dt_varchar(),
		'zip_code'				=> $DBLayer->dt_varchar(),
		'key_number'			=> $DBLayer->dt_varchar(),
		'mbath'					=> $DBLayer->dt_int('TINYINT(1)'),
		'hbath'					=> $DBLayer->dt_int('TINYINT(1)'),
		'pos_x'					=> $DBLayer->dt_varchar('VARCHAR(20)', false, ''),
		'pos_y'					=> $DBLayer->dt_varchar('VARCHAR(20)', false, ''),
	],
	'PRIMARY KEY'	=> ['id']
];
$DBLayer->create_table('sm_property_units', $schema);

$schema = [
	'FIELDS'		=> [
		'id'					=> $DBLayer->dt_serial(),
		'map_name'				=> $DBLayer->dt_varchar(),
		'map_title'				=> $DBLayer->dt_varchar(),
		'map_description'		=> $DBLayer->dt_varchar(),
		'property_id'			=> $DBLayer->dt_int(),

		//'legend_pos_x'			=> $DBLayer->dt_varchar('VARCHAR(20)'),
		//'legend_pos_y'			=> $DBLayer->dt_varchar('VARCHAR(20)'),
	],
	'PRIMARY KEY'	=> ['id']
];
$DBLayer->create_table('sm_property_maps', $schema);

$schema = [
	'FIELDS'		=> [
		'id'					=> $DBLayer->dt_serial(),
		'location_name'			=> $DBLayer->dt_varchar(),
	],
	'PRIMARY KEY'	=> ['id']
];
$DBLayer->create_table('sm_property_locations', $schema);

$schema = [
	'FIELDS'		=> [
		'id'					=> $DBLayer->dt_serial(),
		'gl_code'				=> $DBLayer->dt_varchar(),
		'dept_name'				=> $DBLayer->dt_varchar(),
		'dept_decription'		=> $DBLayer->dt_text(),
	],
	'PRIMARY KEY'	=> ['id']
];
$DBLayer->create_table('sm_property_departments', $schema);

$schema = [
	'FIELDS'		=> [
		'id'					=> $DBLayer->dt_serial(),
		'job_title'				=> $DBLayer->dt_varchar(),
		'job_description'		=> $DBLayer->dt_varchar(),
	],
	'PRIMARY KEY'	=> ['id']
];
$DBLayer->create_table('sm_property_job_categories', $schema);

$schema = [
	'FIELDS'		=> [
		'id'					=> $DBLayer->dt_serial(),
		'size_title'			=> $DBLayer->dt_varchar(),
		'size_description'		=> $DBLayer->dt_varchar(),
	],
	'PRIMARY KEY'	=> ['id']
];
$DBLayer->create_table('sm_property_unit_sizes', $schema);

$DBLayer->add_field('users', 'sm_pm_property_id', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('groups', 'g_sm_property_mngr', 'TINYINT(1)', false, '0');

// Access to the properties. Max 10 allowed
$DBLayer->add_field('users', 'property_access', 'VARCHAR(255)', false, '');

config_add('o_sm_pm_unit_sizes', '');
