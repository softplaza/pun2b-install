<?php 

if (!defined('APP_INSTALL')) die();

if (!$DBLayer->table_exists('sm_user_actions'))
{
	$schema = array(
		'FIELDS'	=> array(
			'user_id'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
			'visit_time'		=> array(
				'datatype'		=> 'INT(10) UNSIGNED',
				'allow_null'	=> true
			),
			'ip'				=> array(
				'datatype'		=> 'VARCHAR(39)',
				'allow_null'	=> false,
				'default'		=> '0'
			),
			'url_from'			=> array(
				'datatype'		=> 'VARCHAR(255)',
				'allow_null'	=> true
			),
			'cur_url'			=> array(
				'datatype'		=> 'VARCHAR(255)',
				'allow_null'	=> true
			),
			'project_id'		=> array(
				'datatype'		=> 'VARCHAR(255)',
				'allow_null'	=> true
			),
			'message'			=> array(
				'datatype'		=> 'VARCHAR(255)',
				'allow_null'	=> true
			),
			'http_code'			=> array(
				'datatype'		=> 'VARCHAR(255)',
				'allow_null'	=> true
			),
		),
	);
	$DBLayer->create_table('sm_user_actions', $schema);
}

if (!$DBLayer->field_exists('sm_user_actions', 'message'))
	$DBLayer->add_field('sm_user_actions', 'message', 'VARCHAR(255)', true);

if (!$DBLayer->field_exists('sm_user_actions', 'project_id'))
	$DBLayer->add_field('sm_user_actions', 'project_id', 'VARCHAR(255)', true);

if (!$DBLayer->field_exists('sm_user_actions', 'url_from'))
	$DBLayer->add_field('sm_user_actions', 'url_from', 'VARCHAR(255)', true);

if (!$DBLayer->field_exists('sm_user_actions', 'http_code'))
	$DBLayer->add_field('sm_user_actions', 'http_code', 'VARCHAR(255)', true);
