<?php 

if (!defined('APP_INSTALL')) die();

if (!$DBLayer->table_exists('sm_calendar_events'))
{
	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'subject'			=> array(
				'datatype'		=> 'VARCHAR(255)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'message'			=> array(
				'datatype'		=> 'TEXT',
				'allow_null'	=> false
			),
			'date'				=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'time'				=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'posted'			=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'poster_id'			=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'updated'			=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'project_name'		=> array(
				'datatype'		=> 'VARCHAR(255)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'project_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'date_id'			=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			
			//TO REMOVING
			'poster_name'		=> array(//remove
				'datatype'		=> 'VARCHAR(100)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'updated_by'		=> array(//remove
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'vendor_id'	=> array(//remove
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'vendor_name'		=> array(//remove
				'datatype'		=> 'VARCHAR(100)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'date_time'			=> $DBLayer->dt_datetime()
		),
		'PRIMARY KEY'	=> array('id')
	);
	$DBLayer->create_table('sm_calendar_events', $schema);
}

if (!$DBLayer->table_exists('sm_calendar_dates'))
{
	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'year_month_day'	=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'poster_id'			=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'last_time_miled'	=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'num_events'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
		),
		'PRIMARY KEY'	=> array('id')
	);
	$DBLayer->create_table('sm_calendar_dates', $schema);
}

if (!$DBLayer->table_exists('sm_calendar_projects'))
{
	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'project_title'		=> array(
				'datatype'		=> 'VARCHAR(255)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'project_desc'		=> array(
				'datatype'		=> 'TEXT',
				'allow_null'	=> false
			),
			'property_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'property_name'	=> array(
				'datatype'		=> 'VARCHAR(100)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'building_number'	=> array(
				'datatype'		=> 'VARCHAR(100)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'unit_number'		=> array(
				'datatype'		=> 'VARCHAR(100)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'project_manager_id'	=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'project_manager_name'	=> array(
				'datatype'		=> 'VARCHAR(100)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'start_date'			=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'end_date'			=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'created'			=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'created_by'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'updated'			=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'updated_by'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'status'		=> array(
				'datatype'		=> 'TINYINT(1)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'remarks'	=> array(
				'datatype'		=> 'TEXT',
				'allow_null'	=> false
			),
		),
		'PRIMARY KEY'	=> array('id')
	);
	$DBLayer->create_table('sm_calendar_projects', $schema);
}

if (!$DBLayer->table_exists('sm_calendar_vendors'))
{
	$schema = array(
		'FIELDS'		=> array(
			'id'			=> array(
				'datatype'		=> 'SERIAL',
				'allow_null'	=> false
			),
			'payee_id'		=> array(
				'datatype'		=> 'VARCHAR(100)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'vendor_name'		=> array(
				'datatype'		=> 'VARCHAR(100)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'phone_number'		=> array(
				'datatype'		=> 'VARCHAR(100)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'service'		=> array(
				'datatype'		=> 'VARCHAR(100)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
			'css'		=> array(
				'datatype'		=> 'VARCHAR(255)',
				'allow_null'	=> false,
				'default'		=> '\'\''
			),
		),
		'PRIMARY KEY'	=> array('id')
	);
	$DBLayer->create_table('sm_calendar_vendors', $schema);
}

$DBLayer->add_field('users', 'sm_calendar_access', 'TINYINT(1)', false, '0');
$DBLayer->add_field('users', 'sm_calendar_project_id', 'VARCHAR(255)', false, '');
$DBLayer->add_field('users', 'sm_calendar_outlook_email', 'VARCHAR(255)', false, '');
$DBLayer->add_field('users', 'sm_calendar_outlook_viewer', 'VARCHAR(255)', false, '');
$DBLayer->add_field('sm_special_projects_events', 'sent_to_outlook', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('users', 'sm_calendar_google_client_id', 'VARCHAR(255)', false);
$DBLayer->add_field('users', 'sm_calendar_google_client_secret', 'VARCHAR(255)', true);

// sm_special_projects
$DBLayer->add_field('sm_special_projects_events', 'sent_to_google', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('sm_calendar_events', 'project_name', 'VARCHAR(255)', false, 'sm_calendar');
$DBLayer->add_field('sm_calendar_events', 'date_id', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('sm_calendar_events', 'date_time', 'DATETIME', true);

config_add('o_sm_calendar_mailing_list', '');
