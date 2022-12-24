<?php 

if (!defined('APP_INSTALL')) die();

$schema = array(
	'FIELDS'	=> array(
		'id'					=> $DBLayer->dt_serial(),
		'sent_time'			=> $DBLayer->dt_int(),
		'sent_from'			=> $DBLayer->dt_int(),
		'from_email'		=> $DBLayer->dt_varchar(),
		'sent_to'			=> $DBLayer->dt_varchar(),
		'reply_to'			=> $DBLayer->dt_varchar(),
		'subject'			=> $DBLayer->dt_varchar(),
		'message'			=> $DBLayer->dt_text(),
		'email_type'		=> $DBLayer->dt_varchar(),
		'response'			=> $DBLayer->dt_varchar()
	),
	'PRIMARY KEY'	=> ['id']
);
$DBLayer->create_table('swift_emails', $schema);
