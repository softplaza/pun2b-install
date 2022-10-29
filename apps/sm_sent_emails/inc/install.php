<?php 

if (!defined('APP_INSTALL')) die();

$schema = array(
	'FIELDS'	=> array(
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
);
$DBLayer->create_table('sm_sent_emails', $schema);

$DBLayer->add_field('sm_sent_emails', 'response', 'VARCHAR(255)', false, '');
$DBLayer->add_field('sm_sent_emails', 'from_email', 'VARCHAR(255)', false, '');
