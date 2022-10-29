<?php 

if (!defined('APP_INSTALL')) die();

$schema = array(
	'FIELDS'		=> array(
		'id'						=> $DBLayer->dt_serial(),
		'user_id'					=> $DBLayer->dt_int(),
		'datetime_created'			=> $DBLayer->dt_datetime(),
		'datetime_scheduled'		=> $DBLayer->dt_datetime(),
		'message'					=> $DBLayer->dt_text(),
		'event_type'				=> $DBLayer->dt_int('TINYINT(1)'),
		'event_status'				=> $DBLayer->dt_int('TINYINT(1)'),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('swift_events', $schema);
