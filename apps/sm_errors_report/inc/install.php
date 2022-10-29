<?php 

if (!defined('APP_INSTALL')) die();

if (!$DBLayer->table_exists('sm_errors_reports'))
{
	$schema = array(
		'FIELDS'	=> array(
			'user_id'			=> $DBLayer->dt_int(),
			'user_ip'			=> $DBLayer->dt_varchar(),
			'error_time'		=> $DBLayer->dt_int(),
			'error_type'		=> $DBLayer->dt_varchar(),
			'url_from'			=> $DBLayer->dt_varchar(),
			'cur_url'			=> $DBLayer->dt_varchar(),
			'project_id'		=> $DBLayer->dt_varchar(),
			'message'			=> $DBLayer->dt_text(),
			'email_status' 		=> $DBLayer->dt_int('TINYINT(1)'),
		),
	);
	$DBLayer->create_table('sm_errors_reports', $schema);
}
