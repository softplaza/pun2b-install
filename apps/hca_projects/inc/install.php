<?php 

if (!defined('APP_INSTALL')) die();

$schema = array(
	'FIELDS'		=> array(
		'id'						=> $DBLayer->dt_serial(),
		'property_id'				=> $DBLayer->dt_int(),
		'unit_number'				=> $DBLayer->dt_varchar(),
		'location'					=> $DBLayer->dt_varchar(),
		'symptoms'					=> $DBLayer->dt_text(),
		'major_repairs'				=> $DBLayer->dt_text(),
		'cosmetic_repairs'			=> $DBLayer->dt_text(),
		'performed_by'				=> $DBLayer->dt_int(),
		'performed_date'			=> $DBLayer->dt_date(),
		'project_status' 			=> $DBLayer->dt_int('TINYINT(1)'),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_projects', $schema);


