<?php 

if (!defined('APP_INSTALL')) die();

$schema = array(
	'FIELDS'		=> array(
		'id'						=> $DBLayer->dt_serial(),
		'property_id'				=> $DBLayer->dt_int(),
		'unit_number'				=> $DBLayer->dt_varchar(),
		'locations'					=> $DBLayer->dt_varchar(),
		'project_number'			=> $DBLayer->dt_varchar(),//??
		'project_description'		=> $DBLayer->dt_text(),
		//'date_bid_start'			=> $DBLayer->dt_date(),
		//'date_active_start'		=> $DBLayer->dt_date(),
		//'date_complete_start'		=> $DBLayer->dt_date(),
		//'date_performed'			=> $DBLayer->dt_date(),
		'date_preinspection_start'	=> $DBLayer->dt_date(),
		'date_preinspection_end'	=> $DBLayer->dt_date(),
		'performed_by'				=> $DBLayer->dt_int(),
		'date_city_inspection_start' => $DBLayer->dt_date(),
		'date_city_inspection_end'	=> $DBLayer->dt_date(),		
		'city_engineer'				=> $DBLayer->dt_varchar(),
		'symptoms'					=> $DBLayer->dt_text(),
		'action'					=> $DBLayer->dt_text(),
		'total_cost'				=> $DBLayer->dt_varchar(),
		'project_status' 			=> $DBLayer->dt_int('TINYINT(1)'),
		'created'					=> $DBLayer->dt_int(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_sb721_projects', $schema);

$schema = array(
	'FIELDS'		=> array(
		'id'						=> $DBLayer->dt_serial(),
		'project_id'				=> $DBLayer->dt_int(),
		'vendor_id'					=> $DBLayer->dt_int(),
		'date_bid'					=> $DBLayer->dt_date(),
		'date_start_job'			=> $DBLayer->dt_date(),
		'date_end_job'				=> $DBLayer->dt_date(),
		'comment'					=> $DBLayer->dt_text(),
		'cost'						=> $DBLayer->dt_varchar(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_sb721_vendors', $schema);

$schema = array(
	'FIELDS'		=> array(
		'id'						=> $DBLayer->dt_serial(),
		'project_id'				=> $DBLayer->dt_int(),
		'supports_check' 			=> $DBLayer->dt_int('TINYINT(1)'),
		'supports_text'				=> $DBLayer->dt_text(),
		'railings_check' 			=> $DBLayer->dt_int('TINYINT(1)'),
		'railings_text'				=> $DBLayer->dt_text(),
		'balconies_check' 			=> $DBLayer->dt_int('TINYINT(1)'),
		'balconies_text'			=> $DBLayer->dt_text(),
		'decks_check' 				=> $DBLayer->dt_int('TINYINT(1)'),
		'decks_text'				=> $DBLayer->dt_text(),
		'porches_check' 			=> $DBLayer->dt_int('TINYINT(1)'),
		'porches_text'				=> $DBLayer->dt_text(),
		'stairways_check' 			=> $DBLayer->dt_int('TINYINT(1)'),
		'stairways_text'			=> $DBLayer->dt_text(),
		'walkways_check' 			=> $DBLayer->dt_int('TINYINT(1)'),
		'walkways_text'				=> $DBLayer->dt_text(),
		'fascia_check' 				=> $DBLayer->dt_int('TINYINT(1)'),
		'fascia_text'				=> $DBLayer->dt_text(),
		'stucco_check' 				=> $DBLayer->dt_int('TINYINT(1)'),
		'stucco_text'				=> $DBLayer->dt_text(),
		'flashings_check' 			=> $DBLayer->dt_int('TINYINT(1)'),
		'flashings_text'			=> $DBLayer->dt_text(),
		'membranes_check' 			=> $DBLayer->dt_int('TINYINT(1)'),
		'membranes_text'			=> $DBLayer->dt_text(),
		'coatings_check' 			=> $DBLayer->dt_int('TINYINT(1)'),
		'coatings_text'				=> $DBLayer->dt_text(),
		'sealants_check' 			=> $DBLayer->dt_int('TINYINT(1)'),
		'sealants_text'				=> $DBLayer->dt_text(),
		'date_submited'				=> $DBLayer->dt_date(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_sb721_checklist', $schema);

$DBLayer->add_field('sm_vendors', 'hca_sb721', 'TINYINT(1)', false, '1');

$DBLayer->add_field('hca_sb721_vendors', 'date_bid', 'DATE', false, '1000-01-01');
$DBLayer->add_field('hca_sb721_projects', 'date_preinspection_start', 'DATE', false, '1000-01-01');
$DBLayer->add_field('hca_sb721_projects', 'date_preinspection_end', 'DATE', false, '1000-01-01');
$DBLayer->add_field('hca_sb721_projects', 'date_city_inspection_start', 'DATE', false, '1000-01-01');
$DBLayer->add_field('hca_sb721_projects', 'date_city_inspection_end', 'DATE', false, '1000-01-01');

$DBLayer->add_field('hca_sb721_projects', 'city_engineer', 'VARCHAR(255)', false, '');
$DBLayer->add_field('hca_sb721_projects', 'created', 'INT(10) UNSIGNED', false, '0');
