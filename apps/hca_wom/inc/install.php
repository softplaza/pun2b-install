<?php 

if (!defined('APP_INSTALL')) die();

$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'property_id'			=> $DBLayer->dt_int(),
		'unit_id'				=> $DBLayer->dt_int(),
		'assigned_to'			=> $DBLayer->dt_int(),
		'date_requested'		=> $DBLayer->dt_date(),
		'wo_message'			=> $DBLayer->dt_text(),

		'priority'				=> $DBLayer->dt_int('TINYINT(1)'),
		'template_type'			=> $DBLayer->dt_int('TINYINT(1)'),
		'enter_permission'		=> $DBLayer->dt_int('TINYINT(1)'),
		'has_animal'			=> $DBLayer->dt_int('TINYINT(1)'),

		// 1 - assigned by manager
		// 2 - accepted by tech
		// 3 - completed by tech
		// 4 - confirmed by manager
		'wo_status'				=> $DBLayer->dt_int('TINYINT(1)'),
		
		'requested_by'			=> $DBLayer->dt_int(),
		'dt_created'			=> $DBLayer->dt_datetime(),
		'dt_accepted'			=> $DBLayer->dt_datetime(),
		'dt_completed'			=> $DBLayer->dt_datetime(),
	),
	'PRIMARY KEY'	=> ['id']
);
$DBLayer->create_table('hca_wom_work_orders', $schema);

$schema = [
	'FIELDS'		=> [
		'id'					=> $DBLayer->dt_serial(),
		'work_order_id'			=> $DBLayer->dt_int(),
		'unit_id'				=> $DBLayer->dt_int(),
		'task_message'			=> $DBLayer->dt_text(),
		'task_status'			=> $DBLayer->dt_int('TINYINT(1)'),
	],
	'PRIMARY KEY'	=> ['id']
];
$DBLayer->create_table('hca_wom_tasks', $schema);
