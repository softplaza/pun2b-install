<?php 

if (!defined('APP_INSTALL')) die();

$schema = array(
	'FIELDS'		=> array(
		'id'						=> $DBLayer->dt_serial(),
		'ter_number'				=> $DBLayer->dt_varchar(),
		'ter_description'			=> $DBLayer->dt_text(),
		'ter_status' 				=> $DBLayer->dt_int('TINYINT(1)'),
		'last_uid'					=> $DBLayer->dt_int(),
		'last_aid'					=> $DBLayer->dt_int()
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('swift_territories', $schema);

$schema = array(
	'FIELDS'		=> array(
		'id'						=> $DBLayer->dt_serial(),
		'territory_id'				=> $DBLayer->dt_int(),
		'user_id'					=> $DBLayer->dt_int(),
		'date_started'				=> $DBLayer->dt_date(),
		'date_completed'			=> $DBLayer->dt_date(),
		'status' 					=> $DBLayer->dt_int('TINYINT(1)')
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('swift_assignments', $schema);
