<?php 

if (!defined('APP_INSTALL')) die();

$schema = array(
	'FIELDS'	=> array(
		'a_time'			=> $DBLayer->dt_int(),
		'a_ip'				=> $DBLayer->dt_varchar('VARCHAR(39)'),
		'a_user_id'			=> $DBLayer->dt_int(),
		'a_user_agent'		=> $DBLayer->dt_varchar(),

		'a_url_from'		=> $DBLayer->dt_varchar(),
		'a_cur_url'			=> $DBLayer->dt_varchar(),
		'a_project_id'		=> $DBLayer->dt_varchar(),
		'a_message'			=> $DBLayer->dt_text(),
		'a_http_code'		=> $DBLayer->dt_varchar(),

		'a_type'			=> $DBLayer->dt_int('TINYINT(1)'),
	),
);
$DBLayer->create_table('swift_user_actions', $schema);

$schema = array(
	'FIELDS'	=> array(
		'ip'				=> $DBLayer->dt_varchar('VARCHAR(39)'),
		'user_id'			=> $DBLayer->dt_int(),
		'user_agent'		=> $DBLayer->dt_text(),


		'is_banned'			=> $DBLayer->dt_int('TINYINT(1)'),
	),
);
$DBLayer->create_table('swift_user_actions_ips', $schema);
