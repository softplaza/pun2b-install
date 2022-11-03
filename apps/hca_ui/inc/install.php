<?php 

if (!defined('APP_INSTALL')) die();

$schema = array(
	'FIELDS'		=> array(
		'id'						=> $DBLayer->dt_serial(),
		'item_name'					=> $DBLayer->dt_varchar(),
		'location_id'				=> $DBLayer->dt_int(),
		'equipment_id'				=> $DBLayer->dt_int(),
		'element_id'				=> $DBLayer->dt_int(),
		'display_position'			=> $DBLayer->dt_int('TINYINT(3)'),
		'problems'					=> $DBLayer->dt_varchar(),
		'summary_report'			=> $DBLayer->dt_int('TINYINT(1)'),
		'display_in_checklist'		=> $DBLayer->dt_int('TINYINT(1)', false, '1'),
		'req_appendixb'				=> $DBLayer->dt_int('TINYINT(1)'),//??
		'part_number'				=> $DBLayer->dt_varchar(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_ui_items', $schema);
$DBLayer->add_field('hca_ui_items', 'part_number', 'VARCHAR(255)', false, '');

$schema = array(
	'FIELDS'		=> array(
		'id'						=> $DBLayer->dt_serial(),
		'property_id'				=> $DBLayer->dt_int(),
		'unit_id'					=> $DBLayer->dt_int(),
		'status'					=> $DBLayer->dt_int('TINYINT(1)'),
		'owned_by'					=> $DBLayer->dt_int(),
		//'created'					=> $DBLayer->dt_int(),

		'date_inspected'			=> $DBLayer->dt_date(),
		'inspected_by'				=> $DBLayer->dt_int(),
		'time_inspection_start'		=> $DBLayer->dt_time(),
		'time_inspection_end'		=> $DBLayer->dt_time(),
		'inspection_comment'		=> $DBLayer->dt_text(),
		'inspection_completed'		=> $DBLayer->dt_int('TINYINT(1)', false, '1'),

		// remove
		//'time_completion_start'	=> $DBLayer->dt_time(),// remove
		//'time_completion_end'		=> $DBLayer->dt_time(),// remove
		//'date_completed'			=> $DBLayer->dt_date(),// remove and use time start and End as date

		'started_by'				=> $DBLayer->dt_int(),
		'datetime_completion_start'	=> $DBLayer->dt_datetime(),//?
		'datetime_completion_end'	=> $DBLayer->dt_datetime(),

		'completed_by'				=> $DBLayer->dt_int(),
		'work_order_comment'		=> $DBLayer->dt_text(),
		'work_order_completed'		=> $DBLayer->dt_int('TINYINT(1)', false, '1'),

		'updated_by'				=> $DBLayer->dt_int(),
		'updated_time'				=> $DBLayer->dt_int(),

		//'num_bags'					=> $DBLayer->dt_int(), // remove
		'num_problem'				=> $DBLayer->dt_int(),
		'num_pending'				=> $DBLayer->dt_int(),
		'num_repaired'				=> $DBLayer->dt_int(),
		'num_replaced'				=> $DBLayer->dt_int(),
		'num_reset'					=> $DBLayer->dt_int(),
		'num_tied'					=> $DBLayer->dt_int(),

		'appendixb'					=> $DBLayer->dt_int('TINYINT(1)'),

		'inspection_type'			=> $DBLayer->dt_int('TINYINT(1)'),// to remove 	0 => 'Water Audit', 1 => 'Flapper Replacement',
		'type_audit'				=> $DBLayer->dt_int('TINYINT(1)'),
		'type_flapper'				=> $DBLayer->dt_int('TINYINT(1)'),

		'completed'					=> $DBLayer->dt_int('TINYINT(1)'),// to remove
		//'k_comment'				=> $DBLayer->dt_text(),//remove
		//'gb_comment'				=> $DBLayer->dt_text(),
		//'mb_comment'				=> $DBLayer->dt_text(),
		//'hb_comment'				=> $DBLayer->dt_text()
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_ui_checklist', $schema);
$DBLayer->add_field('hca_ui_checklist', 'type_audit', 'TINYINT(1)', false, '0');
$DBLayer->add_field('hca_ui_checklist', 'type_flapper', 'TINYINT(1)', false, '0');
$DBLayer->add_field('hca_ui_checklist', 'num_tied', 'INT(10) UNSIGNED', false, '0');

$schema = array(
	'FIELDS'		=> array(
		'id'						=> $DBLayer->dt_serial(),
		'checklist_id'				=> $DBLayer->dt_int(),
		'item_id'					=> $DBLayer->dt_int(),
		'problem_id'				=> $DBLayer->dt_int('TINYINT(3)'),
		'problem_ids'				=> $DBLayer->dt_varchar(),
		'job_type'					=> $DBLayer->dt_int('TINYINT(1)'),
		'comment'					=> $DBLayer->dt_varchar(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_ui_checklist_items', $schema);

$schema = array(
	'FIELDS'		=> array(
		'id'						=> $DBLayer->dt_serial(),
		'property_id'				=> $DBLayer->dt_int(),
		'building_id'				=> $DBLayer->dt_int(),
		'building_number'			=> $DBLayer->dt_varchar(),
		'pressure_current'			=> $DBLayer->dt_varchar(),
		'pressure_adjusted'			=> $DBLayer->dt_varchar(),
		'date_completed'			=> $DBLayer->dt_date(),
		'completed_by'				=> $DBLayer->dt_int(),
		'status'					=> $DBLayer->dt_int('TINYINT(1)'),
		'comment'					=> $DBLayer->dt_text()
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_ui_water_pressure', $schema);

$schema = array(
	'FIELDS'		=> array(
		'id'						=> $DBLayer->dt_serial(),
		'checklist_id'				=> $DBLayer->dt_int(),
		'submitted_by'				=> $DBLayer->dt_int(),
		'time_submitted'			=> $DBLayer->dt_int(),
		'action'					=> $DBLayer->dt_varchar(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_ui_actions', $schema);

