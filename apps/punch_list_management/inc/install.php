<?php 

if (!defined('APP_INSTALL')) die();

// Maintenence - LOCATION / EQUIPMENTS / ITEMS
$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'location_name'			=> $DBLayer->dt_varchar(),
		'loc_position'			=> $DBLayer->dt_int(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('punch_list_management_maint_locations', $schema);

$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'equipment_name'		=> $DBLayer->dt_varchar(),
		'location_id'			=> $DBLayer->dt_int(),
		'eq_position'			=> $DBLayer->dt_int(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('punch_list_management_maint_equipments', $schema);

$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'item_name'				=> $DBLayer->dt_varchar(),
		'equipment_id'			=> $DBLayer->dt_int(),
		'location_id'			=> $DBLayer->dt_int(),
		'property_exceptions'	=> $DBLayer->dt_text(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('punch_list_management_maint_items', $schema);

$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'item_id'				=> $DBLayer->dt_int(),
		'property_id'			=> $DBLayer->dt_int(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('punch_list_management_maint_properties', $schema);

// MOISTURE LIST
$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'moisture_name'			=> $DBLayer->dt_varchar(),
		'location_id'			=> $DBLayer->dt_int(),
		'default_status'		=> $DBLayer->dt_int('TINYINT(1)'),
		'status_exceptions'		=> $DBLayer->dt_varchar(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('punch_list_management_maint_moisture', $schema);

// PARTS
$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'group_name'			=> $DBLayer->dt_varchar(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('punch_list_management_maint_parts_group', $schema);

$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'part_number'			=> $DBLayer->dt_varchar(),
		'part_name'				=> $DBLayer->dt_varchar(),
		'part_cost'				=> $DBLayer->dt_varchar(),
		'group_id'				=> $DBLayer->dt_int(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('punch_list_management_maint_parts', $schema);

// MAINTENANCE FORM
$schema = array(
	'FIELDS'		=> array(
		'id'						=> $DBLayer->dt_serial(),
		'form_type'					=> $DBLayer->dt_int('TINYINT(1)'),
		'property_id'				=> $DBLayer->dt_int(),
		'property_name'				=> $DBLayer->dt_varchar(),//remove
		'unit_number'				=> $DBLayer->dt_varchar(),
		'technician'				=> $DBLayer->dt_varchar(),//remove
		'technician_id'				=> $DBLayer->dt_int(),
		'completed'					=> $DBLayer->dt_int('TINYINT(1)'),
		'remarks'					=> $DBLayer->dt_text(),
		'moisture_comment'			=> $DBLayer->dt_text(),
		'materials_comment'			=> $DBLayer->dt_text(),
		'time_spent'				=> $DBLayer->dt_varchar(),
		'wo_number'					=> $DBLayer->dt_int(),
		'total_cost'				=> $DBLayer->dt_varchar(),
		'start_time'				=> $DBLayer->dt_time(),//00:00:00
		'end_time'					=> $DBLayer->dt_time(),//00:00:00
		'date_submitted'			=> $DBLayer->dt_int(),//remove
		'date_requested'			=> $DBLayer->dt_int(),
		'submitted_by_technician'	=> $DBLayer->dt_int(),//time()
		'submitted_by_manager'		=> $DBLayer->dt_int(),//time()
		'hash_key'					=> $DBLayer->dt_varchar(),
		'file_path'					=> $DBLayer->dt_varchar(),
		'current_water_pressure'	=> $DBLayer->dt_int(),
		'adjusted_water_pressure'	=> $DBLayer->dt_int(),
		'current_water_temp'		=> $DBLayer->dt_int(),
		'adjusted_water_temp'		=> $DBLayer->dt_int(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('punch_list_management_maint_request_form', $schema);

$DBLayer->add_field('punch_list_management_maint_request_form', 'current_water_pressure', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('punch_list_management_maint_request_form', 'adjusted_water_pressure', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('punch_list_management_maint_request_form', 'current_water_temp', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('punch_list_management_maint_request_form', 'adjusted_water_temp', 'INT(10) UNSIGNED', false, '0');

$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'item_id'				=> $DBLayer->dt_int(),
		'item_description'		=> $DBLayer->dt_varchar(),
		'item_status'			=> $DBLayer->dt_int('TINYINT(1)'),
		'form_id'				=> $DBLayer->dt_int(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('punch_list_management_maint_request_items', $schema);

$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'form_id'				=> $DBLayer->dt_int(),
		'group_id'				=> $DBLayer->dt_int(),
		'part_number'			=> $DBLayer->dt_varchar(),
		'part_description'		=> $DBLayer->dt_varchar(),
		'part_quantity'			=> $DBLayer->dt_varchar(),
		'cost_per'				=> $DBLayer->dt_varchar(),
		'cost_total'			=> $DBLayer->dt_varchar(),
		'type_work'				=> $DBLayer->dt_int('TINYINT(1)'),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('punch_list_management_maint_request_materials', $schema);

// MOISTURE FORM
$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'moisture_id'			=> $DBLayer->dt_int(),
		'check_status'			=> $DBLayer->dt_int('TINYINT(1)'),
		'form_id'				=> $DBLayer->dt_int(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('punch_list_management_maint_moisture_check_list', $schema);

// PAINTER - LOCATION / EQUIPMENTS / ITEMS
$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'location_name'			=> $DBLayer->dt_varchar(),
		'position'				=> $DBLayer->dt_int(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('punch_list_painter_locations', $schema);

$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'equipment_name'		=> $DBLayer->dt_varchar(),
		'location_id'			=> $DBLayer->dt_int(),
		'job_actions'			=> $DBLayer->dt_varchar(),
		'replaced_action'		=> $DBLayer->dt_int('TINYINT(1)'),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('punch_list_painter_equipments', $schema);

$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'part_number'			=> $DBLayer->dt_varchar(),
		'part_name'				=> $DBLayer->dt_varchar(),
		'part_cost'				=> $DBLayer->dt_varchar(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('punch_list_painter_parts', $schema);

// PAINTER CHECK LIST
$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'equipment_id'			=> $DBLayer->dt_int(),
		'form_id'				=> $DBLayer->dt_int(),
		'replaced'				=> $DBLayer->dt_int('TINYINT(1)'),
		'item_status'			=> $DBLayer->dt_int('TINYINT(1)'),//remove
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('punch_list_painter_check_list', $schema);

$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'location_id'			=> $DBLayer->dt_int(),
		'form_id'				=> $DBLayer->dt_int(),
		'comment'				=> $DBLayer->dt_text(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('punch_list_painter_check_list_comments', $schema);

$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'form_id'				=> $DBLayer->dt_int(),
		'part_description'		=> $DBLayer->dt_varchar(),
		'part_quantity'			=> $DBLayer->dt_varchar(),
		'cost_per'				=> $DBLayer->dt_varchar(),
		'cost_total'			=> $DBLayer->dt_varchar(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('punch_list_painter_materials', $schema);

