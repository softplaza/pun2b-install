<?php 

if (!defined('APP_INSTALL')) die();

$schema = array(
	'FIELDS'		=> array(
		'id'						=> $DBLayer->dt_serial(),
		'property_id'				=> $DBLayer->dt_int(),
		'unit_id'					=> $DBLayer->dt_int(),
		'unit_number'				=> $DBLayer->dt_varchar(),
		'project_description'		=> $DBLayer->dt_text(),

		//'building_ids'				=> $DBLayer->dt_varchar(),
		//'building_numbers'			=> $DBLayer->dt_varchar(),
		//'unit_ids'					=> $DBLayer->dt_varchar(),
		//'unit_numbers'				=> $DBLayer->dt_varchar(),

		'vendor_id'					=> $DBLayer->dt_int(),
		'date_start'				=> $DBLayer->dt_date(),
		'date_end'					=> $DBLayer->dt_date(),
		'po_number'					=> $DBLayer->dt_varchar(),
		'vendor_comment'			=> $DBLayer->dt_text(),

		'created_by'				=> $DBLayer->dt_int(),
		'created_time'				=> $DBLayer->dt_int(),

		'date_completed'			=> $DBLayer->dt_date(),
		'project_manager_id'		=> $DBLayer->dt_int(),
		'status'					=> $DBLayer->dt_int('TINYINT(1)'),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_repipe_projects', $schema);

$schema = array(
	'FIELDS'		=> array(
		'id'						=> $DBLayer->dt_serial(),
		'project_id'				=> $DBLayer->dt_int(),
		'submitted_by'				=> $DBLayer->dt_int(),
		'date_submitted'			=> $DBLayer->dt_date(),
		'comment'					=> $DBLayer->dt_text(),
		'action_type'				=> $DBLayer->dt_int('TINYINT(1)'),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_repipe_actions', $schema);

$DBLayer->add_field('sm_vendors', 'hca_repipe', 'TINYINT(1)', false, '0');