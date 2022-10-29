<?php 

if (!defined('APP_INSTALL')) die();

$schema = array(
	'FIELDS'		=> array(
		'id'			=> array(
			'datatype'		=> 'SERIAL',
			'allow_null'	=> false
		),
		'property_id'	=> array(
			'datatype'		=> 'INT(10) UNSIGNED',
			'allow_null'	=> false,
			'default'		=> '0'
		),
		'property_name'		=> array(
			'datatype'		=> 'VARCHAR(100)',
			'allow_null'	=> false,
			'default'		=> '\'\''
		),
		'location'	=> array(
			'datatype'		=> 'VARCHAR(255)',
			'allow_null'	=> false,
			'default'		=> '\'\''
		),
		'project_desc'		=> array(
			'datatype'		=> 'TEXT',
			'allow_null'	=> false
		),
		'noticed_date'	=> array(
			'datatype'		=> 'INT(10) UNSIGNED',
			'allow_null'	=> false,
			'default'		=> '0'
		),
		'vendor_id'	=> array(
			'datatype'		=> 'INT(10) UNSIGNED',
			'allow_null'	=> false,
			'default'		=> '0'
		),
		'vendor'	=> array(
			'datatype'		=> 'VARCHAR(255)',
			'allow_null'	=> false,
			'default'		=> '\'\''
		),
		'po_number'	=> array(
			'datatype'		=> 'VARCHAR(100)',
			'allow_null'	=> false,
			'default'		=> '\'\''
		),
		'total_cost'		=> array(
			'datatype'		=> 'VARCHAR(100)',
			'allow_null'	=> false,
			'default'		=> '\'\''
		),
		'start_date'		=> array(
			'datatype'		=> 'INT(10) UNSIGNED',
			'allow_null'	=> false,
			'default'		=> '0'
		),
		'end_date'		=> array(
			'datatype'		=> 'INT(10) UNSIGNED',
			'allow_null'	=> false,
			'default'		=> '0'
		),
		'completion_date'		=> array(
			'datatype'		=> 'INT(10) UNSIGNED',
			'allow_null'	=> false,
			'default'		=> '0'
		),
		'remarks'		=> array(
			'datatype'		=> 'TEXT',
			'allow_null'	=> false
		),
		'job_status'	=> array(
			'datatype'		=> 'TINYINT(1)',
			'allow_null'	=> false,
			'default'		=> '0'
		),
		'email_status'	=> array(
			'datatype'		=> 'TINYINT(1)',
			'allow_null'	=> false,
			'default'		=> '0'
		),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_trees_projects', $schema);

$DBLayer->add_field('sm_vendors', 'hca_trees', 'TINYINT(1)', false, '1');

//$DBLayer->add_field('users', 'hca_trees_access', 'TINYINT(1)', false, '0');
$DBLayer->drop_field('users', 'hca_trees_access');
