<?php 

if (!defined('APP_INSTALL')) die();

$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'created_by'			=> $DBLayer->dt_int(),
		'property_id'			=> $DBLayer->dt_int(),
		'property_name'			=> $DBLayer->dt_varchar(),
		'location'				=> $DBLayer->dt_varchar(),
		'project_desc'			=> $DBLayer->dt_text(),
		'noticed_date'			=> $DBLayer->dt_int(),
		'vendor_id'				=> $DBLayer->dt_int(),
		'vendor'				=> $DBLayer->dt_varchar(),
		'po_number'				=> $DBLayer->dt_varchar(),
		'total_cost'			=> $DBLayer->dt_varchar(),
		'start_date'			=> $DBLayer->dt_int(),
		'end_date'				=> $DBLayer->dt_int(),
		'completion_date'		=> $DBLayer->dt_int(),
		'remarks'				=> $DBLayer->dt_text(),
		'job_status'			=> $DBLayer->dt_int('TINYINT(1)'),
		'email_status'			=> $DBLayer->dt_int('TINYINT(1)'),
		'completed_by'			=> $DBLayer->dt_int(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_trees_projects', $schema);
$DBLayer->add_field('hca_trees_projects', 'created_by', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('hca_trees_projects', 'completed_by', 'INT(10) UNSIGNED', false, '0');

$DBLayer->add_field('sm_vendors', 'hca_trees', 'TINYINT(1)', false, '1');

//$DBLayer->add_field('users', 'hca_trees_access', 'TINYINT(1)', false, '0');
$DBLayer->drop_field('users', 'hca_trees_access');
