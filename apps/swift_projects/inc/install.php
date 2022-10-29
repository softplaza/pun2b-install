<?php 

if (!defined('APP_INSTALL')) die();

$schema = array(
	'FIELDS'		=> array(
		'id'						=> $DBLayer->dt_serial(),
		'project_desc'				=> $DBLayer->dt_varchar(),
		'requested_work'			=> $DBLayer->dt_text(),
		'completed_work'			=> $DBLayer->dt_text(),
		'task_type' 				=> $DBLayer->dt_int('TINYINT(1)'),
		'project_status' 			=> $DBLayer->dt_int('TINYINT(1)'),
		'urgency'					=> $DBLayer->dt_int('TINYINT(1)'),

		'requested_by'				=> $DBLayer->dt_varchar(),
		'requested_date'			=> $DBLayer->dt_int(),
		'start_date'				=> $DBLayer->dt_int(),
		'end_date'					=> $DBLayer->dt_int(),
		'performed_by'				=> $DBLayer->dt_int(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('swift_projects', $schema);
