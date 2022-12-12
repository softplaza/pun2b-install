<?php 

if (!defined('APP_INSTALL')) die();

$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'property_id'			=> $DBLayer->dt_int(),
		'unit_id'				=> $DBLayer->dt_int(),
		'wo_message'			=> $DBLayer->dt_varchar(),
		'priority'				=> $DBLayer->dt_int('TINYINT(1)'),
		'template_type'			=> $DBLayer->dt_int('TINYINT(1)'),
		'enter_permission'		=> $DBLayer->dt_int('TINYINT(1)'),
		'has_animal'			=> $DBLayer->dt_int('TINYINT(1)'),
		'wo_status'				=> $DBLayer->dt_int('TINYINT(1)'),//??
		'requested_by'			=> $DBLayer->dt_int(),
		'created_by'			=> $DBLayer->dt_int(),
		'dt_created'			=> $DBLayer->dt_datetime(),
		'closed_by'				=> $DBLayer->dt_int(),
		'dt_closed'				=> $DBLayer->dt_datetime(),
		'wo_closing_comment'	=> $DBLayer->dt_varchar(),
		'num_tasks'				=> $DBLayer->dt_int(),
		'last_task_id'			=> $DBLayer->dt_int(),
	),
	'PRIMARY KEY'	=> ['id']
);
$DBLayer->create_table('hca_wom_work_orders', $schema);

$schema = [
	'FIELDS'		=> [
		'id'					=> $DBLayer->dt_serial(),
		'work_order_id'			=> $DBLayer->dt_int(),
		'assigned_to'			=> $DBLayer->dt_int(),
		'time_created'			=> $DBLayer->dt_int(),

		'item_id'				=> $DBLayer->dt_int(),
		'task_action'			=> $DBLayer->dt_int('TINYINT(3)'),

		'dt_completed'			=> $DBLayer->dt_datetime(),
		'time_start'			=> $DBLayer->dt_time(),
		'time_end'				=> $DBLayer->dt_time(),
		'task_message'			=> $DBLayer->dt_varchar(),
		'tech_comment'			=> $DBLayer->dt_varchar(),
		'task_closing_comment'	=> $DBLayer->dt_varchar(),
		
		// 0 - on hold/inactive
		// 1 - assigned by manager
		// 2 - accepted by tech
		// 3 - completed by tech
		// 4 - closed by manager
		'task_status'			=> $DBLayer->dt_int('TINYINT(1)'),
		'parts_installed'		=> $DBLayer->dt_int('TINYINT(1)'),
		'completed'				=> $DBLayer->dt_int('TINYINT(1)'),
	],
	'PRIMARY KEY'	=> ['id']
];
$DBLayer->create_table('hca_wom_tasks', $schema);

$schema = [
	'FIELDS'		=> [
		'id'					=> $DBLayer->dt_serial(),
		'item_name'				=> $DBLayer->dt_varchar(),
		'item_type'				=> $DBLayer->dt_int(),
		'item_actions'			=> $DBLayer->dt_varchar(),
		'display_position'		=> $DBLayer->dt_int('TINYINT(3)'),
	],
	'PRIMARY KEY'	=> ['id']
];
$DBLayer->create_table('hca_wom_items', $schema);

$schema = [
	'FIELDS'		=> [
		'id'						=> $DBLayer->dt_serial(),
		'wo_id'						=> $DBLayer->dt_int(),
		'task_id'					=> $DBLayer->dt_int(),
		'submitted_by'				=> $DBLayer->dt_int(),
		'time_submitted'			=> $DBLayer->dt_int(),
		'message'					=> $DBLayer->dt_varchar(),
	],
	'PRIMARY KEY'	=> ['id']
];
$DBLayer->create_table('hca_wom_actions', $schema);
