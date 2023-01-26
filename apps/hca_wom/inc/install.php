<?php 

if (!defined('APP_INSTALL')) die();

$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'property_id'			=> $DBLayer->dt_int(),
		'unit_id'				=> $DBLayer->dt_int(),
		'wo_message'			=> $DBLayer->dt_varchar(),
		'wo_requested_date'		=> $DBLayer->dt_datetime(),
		'priority'				=> $DBLayer->dt_int('TINYINT(1)'),
		'template_type'			=> $DBLayer->dt_int('TINYINT(1)'),
		'enter_permission'		=> $DBLayer->dt_int('TINYINT(1)'),
		'has_animal'			=> $DBLayer->dt_int('TINYINT(1)'),
		'wo_status'				=> $DBLayer->dt_int('TINYINT(1)'),// 3 - closed

		'requested_by'			=> $DBLayer->dt_int(),
		'created_by'			=> $DBLayer->dt_int(),
		'dt_created'			=> $DBLayer->dt_datetime(),
		'closed_by'				=> $DBLayer->dt_int(),
		'dt_closed'				=> $DBLayer->dt_datetime(),
		'dt_completed'			=> $DBLayer->dt_datetime(), // use closed
		'wo_closing_comment'	=> $DBLayer->dt_varchar(),
		'num_tasks'				=> $DBLayer->dt_int(),
		'last_task_id'			=> $DBLayer->dt_int(),
		'request_type'			=> $DBLayer->dt_int('TINYINT(1)'),
		'template_id'			=> $DBLayer->dt_int(),
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
		'dt_created'			=> $DBLayer->dt_datetime(),

		'item_id'				=> $DBLayer->dt_int(),
		'task_action'			=> $DBLayer->dt_int('TINYINT(3)'),

		'dt_completed'			=> $DBLayer->dt_datetime(),
		'time_start'			=> $DBLayer->dt_time(),
		'time_end'				=> $DBLayer->dt_time(),
		'task_message'			=> $DBLayer->dt_varchar(),
		'tech_comment'			=> $DBLayer->dt_varchar(),
		'task_closing_comment'	=> $DBLayer->dt_varchar(),
		'task_init_created'		=> $DBLayer->dt_varchar(),
		'task_init_closed'		=> $DBLayer->dt_varchar(),

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
$DBLayer->add_field('hca_wom_tasks', 'dt_created', 'DATETIME', false, '1000-01-01 00:00:00');

$query = [
	'SELECT'	=> 't.*',
	'FROM'		=> 'hca_wom_tasks AS t',
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$hca_wom_tasks = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$hca_wom_tasks[] = $row;
}

foreach($hca_wom_tasks as $cur_info)
{
	$task_data = [
		'dt_created' => date('Y-m-d H:i:s', $cur_info['time_created'])
	];
	$DBLayer->update('hca_wom_tasks', $task_data, $cur_info['id']);
}



// Management
$schema = [
	'FIELDS'		=> [
		'id'					=> $DBLayer->dt_serial(),
		'type_name'				=> $DBLayer->dt_varchar(),
	],
	'PRIMARY KEY'	=> ['id']
];
$DBLayer->create_table('hca_wom_types', $schema);

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
		'id'					=> $DBLayer->dt_serial(),
		'problem_name'			=> $DBLayer->dt_varchar(),
	],
	'PRIMARY KEY'	=> ['id']
];
$DBLayer->create_table('hca_wom_problems', $schema);

$schema = [
	'FIELDS'		=> [
		'id'					=> $DBLayer->dt_serial(),
		'property_id'			=> $DBLayer->dt_int(),
		'template_type'			=> $DBLayer->dt_int('TINYINT(1)'),
		'tpl_name'				=> $DBLayer->dt_varchar(),
		'priority'				=> $DBLayer->dt_int('TINYINT(1)'),
		'enter_permission'		=> $DBLayer->dt_int('TINYINT(1)'),
		'has_animal'			=> $DBLayer->dt_int('TINYINT(1)'),
		'wo_message'			=> $DBLayer->dt_varchar(),
		'created'				=> $DBLayer->dt_int(),
		'created_by'			=> $DBLayer->dt_int(),
		'updated'				=> $DBLayer->dt_int(),
		'updated_by'			=> $DBLayer->dt_int(),
	],
	'PRIMARY KEY'	=> ['id']
];
$DBLayer->create_table('hca_wom_tpl_wo', $schema);

$schema = [
	'FIELDS'		=> [
		'id'					=> $DBLayer->dt_serial(),
		'tpl_id'				=> $DBLayer->dt_int(),
		'item_id'				=> $DBLayer->dt_int(),
		'task_action'			=> $DBLayer->dt_int('TINYINT(3)'),
		'assigned_to'			=> $DBLayer->dt_int(),
		'task_message'			=> $DBLayer->dt_varchar(),
	],
	'PRIMARY KEY'	=> ['id']
];
$DBLayer->create_table('hca_wom_tpl_tasks', $schema);
$DBLayer->add_field('hca_wom_tpl_tasks', 'assigned_to', 'INT(10) UNSIGNED', false, '0');

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

$DBLayer->add_field('sm_property_db', 'default_maint', 'INT(10) UNSIGNED', false, '0');

config_add('o_hca_wom_notify_technician', '0');
config_add('o_hca_wom_notify_managers', '0');

config_add('o_hca_wom_notify_inhouse_from_manager', '0');
config_add('o_hca_wom_notify_managers_from_inhouse', '0');
