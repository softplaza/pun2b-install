<?php 

if (!defined('APP_INSTALL')) die();

$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'property_id'			=> $DBLayer->dt_int(),
		'unit_id'				=> $DBLayer->dt_int(),
		//'assigned_to'			=> $DBLayer->dt_int(),
		//'date_requested'		=> $DBLayer->dt_date(),
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
	),
	'PRIMARY KEY'	=> ['id']
);
$DBLayer->create_table('hca_wom_work_orders', $schema);
$DBLayer->add_field('hca_wom_work_orders', 'num_tasks', 'INT(10) UNSIGNED', false, '0');

$DBLayer->add_field('hca_wom_work_orders', 'created_by', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('hca_wom_work_orders', 'closed_by', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('hca_wom_work_orders', 'dt_closed', 'DATETIME', false, '1000-01-01 00:00:00');
$DBLayer->add_field('hca_wom_work_orders', 'wo_closing_comment', 'VARCHAR(255)', false, '');

$schema = [
	'FIELDS'		=> [
		'id'					=> $DBLayer->dt_serial(),
		'work_order_id'			=> $DBLayer->dt_int(),
		'assigned_to'			=> $DBLayer->dt_int(),
		'time_created'			=> $DBLayer->dt_int(),

		'task_type'				=> $DBLayer->dt_int('TINYINT(3)'),
		'task_item'				=> $DBLayer->dt_int(),
		'task_action'			=> $DBLayer->dt_int('TINYINT(3)'),

		'dt_completed'			=> $DBLayer->dt_datetime(),
		'time_start'			=> $DBLayer->dt_time(),
		'time_end'				=> $DBLayer->dt_time(),
		'task_message'			=> $DBLayer->dt_varchar(),
		'tech_comment'			=> $DBLayer->dt_varchar(),

		// 0 - on hold/inactive
		// 1 - assigned by manager
		// 2 - accepted by tech
		// 3 - completed by tech
		// 4 - confirmed by manager
		'task_status'			=> $DBLayer->dt_int('TINYINT(1)'),

		'parts_installed'		=> $DBLayer->dt_int('TINYINT(1)'),
		'completed'				=> $DBLayer->dt_int('TINYINT(1)'),
	],
	'PRIMARY KEY'	=> ['id']
];
$DBLayer->create_table('hca_wom_tasks', $schema);

$DBLayer->add_field('hca_wom_tasks', 'time_created', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('hca_wom_tasks', 'tech_comment', 'TEXT', false, '');
$DBLayer->add_field('hca_wom_tasks', 'parts_installed', 'TINYINT(1)', false, '0');
$DBLayer->add_field('hca_wom_tasks', 'completed', 'TINYINT(1)', false, '0');
$DBLayer->add_field('hca_wom_tasks', 'dt_completed', 'DATETIME', false, '1000-01-01 00:00:00');

$DBLayer->add_field('hca_wom_tasks', 'task_closing_comment', 'VARCHAR(255)', false, '');

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
		'submitted_by'				=> $DBLayer->dt_int(),
		'time_submitted'			=> $DBLayer->dt_int(),
		'action'					=> $DBLayer->dt_varchar(),
	],
	'PRIMARY KEY'	=> ['id']
];
$DBLayer->create_table('hca_wom_actions', $schema);
