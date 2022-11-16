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
		'job_actions'				=> $DBLayer->dt_varchar(),
		'summary_report'			=> $DBLayer->dt_int('TINYINT(1)'),
		'display_in_checklist'		=> $DBLayer->dt_int('TINYINT(1)', false, '1'),
		'req_appendixb'				=> $DBLayer->dt_int('TINYINT(1)'),
		'item_type'					=> $DBLayer->dt_int('TINYINT(1)'),
		'item_inspection_type'		=> $DBLayer->dt_int('TINYINT(1)', false, '1'),
		'comment_required'			=> $DBLayer->dt_int('TINYINT(1)'),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_hvac_inspections_items', $schema);
$DBLayer->add_field('hca_hvac_inspections_items', 'item_type', 'TINYINT(1)', false, '0');
$DBLayer->add_field('hca_hvac_inspections_items', 'job_actions', 'VARCHAR(255)', false, '');
$DBLayer->add_field('hca_hvac_inspections_items', 'item_inspection_type', 'TINYINT(1)', false, '1');
$DBLayer->add_field('hca_hvac_inspections_items', 'comment_required', 'TINYINT(1)', false, '0');

$schema = array(
	'FIELDS'		=> array(
		'id'						=> $DBLayer->dt_serial(),
		'filter_size'				=> $DBLayer->dt_varchar(),
		'property_id'				=> $DBLayer->dt_int(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_hvac_inspections_filters', $schema);

$schema = array(
	'FIELDS'		=> array(
		'id'						=> $DBLayer->dt_serial(),
		'property_id'				=> $DBLayer->dt_int(),
		'unit_id'					=> $DBLayer->dt_int(),
		'status'					=> $DBLayer->dt_int('TINYINT(1)'),
		'owned_by'					=> $DBLayer->dt_int(),

		'datetime_inspection_start'	=> $DBLayer->dt_datetime(), // DATETIME format
		'datetime_inspection_end'	=> $DBLayer->dt_datetime(),
		'inspection_comment'		=> $DBLayer->dt_text(),
		'inspection_completed'		=> $DBLayer->dt_int('TINYINT(1)'), // Inspection status
		'inspected_by'				=> $DBLayer->dt_int(),

		'started_by'				=> $DBLayer->dt_int(),
		'datetime_completion_start'	=> $DBLayer->dt_datetime(),
		'datetime_completion_end'	=> $DBLayer->dt_datetime(),
		'work_order_comment'		=> $DBLayer->dt_text(),
		'work_order_completed'		=> $DBLayer->dt_int('TINYINT(1)'), // Work Order status// 1 - created // 2 - completed
		'completed_by'				=> $DBLayer->dt_int(),

		'updated_by'				=> $DBLayer->dt_int(),
		'updated_time'				=> $DBLayer->dt_int(),

		'num_problem'				=> $DBLayer->dt_int(),
		'num_pending'				=> $DBLayer->dt_int(),
		
		'num_repaired'				=> $DBLayer->dt_int(),
		'num_replaced'				=> $DBLayer->dt_int(),
		'num_reset'					=> $DBLayer->dt_int(),

		'filter_size_id'			=> $DBLayer->dt_int(),
		'appendixb'					=> $DBLayer->dt_int('TINYINT(1)'),
		'ch_inspection_type'		=> $DBLayer->dt_int('TINYINT(1)', false, '1'), // Type of inspection
		//'are_inside'				=> $DBLayer->dt_int('TINYINT(1)'),
		'completed'					=> $DBLayer->dt_int('TINYINT(1)'), // to remove
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_hvac_inspections_checklist', $schema);

$DBLayer->add_field('hca_hvac_inspections_checklist', 'filter_size_id', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('hca_hvac_inspections_checklist', 'ch_inspection_type', 'TINYINT(1)', false, '1');

//$DBLayer->add_field('hca_hvac_inspections_checklist', 'are_inside', 'TINYINT(1)', false, '0');
$DBLayer->drop_field('hca_hvac_inspections_checklist', 'are_inside');

$schema = array(
	'FIELDS'		=> array(
		'id'						=> $DBLayer->dt_serial(),
		'checklist_id'				=> $DBLayer->dt_int(),
		'item_id'					=> $DBLayer->dt_int(),
		'problem_id'				=> $DBLayer->dt_int('TINYINT(3)'),
		'problem_ids'				=> $DBLayer->dt_varchar(),
		'check_type'				=> $DBLayer->dt_int('TINYINT(1)'),// YES or NO
		'job_type'					=> $DBLayer->dt_int('TINYINT(1)'),
		'comment'					=> $DBLayer->dt_text(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_hvac_inspections_checklist_items', $schema);
$DBLayer->add_field('hca_hvac_inspections_checklist_items', 'check_type', 'TINYINT(1)', false, '0');

// ACTIONS 
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
$DBLayer->create_table('hca_hvac_inspections_actions', $schema);

