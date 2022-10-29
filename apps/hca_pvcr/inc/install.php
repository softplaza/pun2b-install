<?php 

if (!defined('APP_INSTALL')) die();

$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'property_id'			=> $DBLayer->dt_int(),
		'unit_number'			=> $DBLayer->dt_varchar(),
		'unit_size'				=> $DBLayer->dt_varchar(),
		'move_out_date'			=> $DBLayer->dt_int(),
		'move_out_comment'		=> $DBLayer->dt_text(),
		'pre_walk_name'			=> $DBLayer->dt_varchar(),
		'pre_walk_date'			=> $DBLayer->dt_int(),
		'pre_walk_comment'		=> $DBLayer->dt_text(),
		'maint_fs_req_id'		=> $DBLayer->dt_int(),
		'paint_inhouse'			=> $DBLayer->dt_int('TINYINT(1)'),
		'paint_fs_req_id'		=> $DBLayer->dt_int(),
		'crpt_urine_scan'		=> $DBLayer->dt_int('TINYINT(1)'),//????
		'urine_scan'			=> $DBLayer->dt_int('TINYINT(1)'),//????
		'crpt_replaced'			=> $DBLayer->dt_int('TINYINT(1)'),
		'crpt_repair'			=> $DBLayer->dt_int('TINYINT(1)'),
		'vinyl_replaced'		=> $DBLayer->dt_int('TINYINT(1)'),
		'refinish_check'		=> $DBLayer->dt_int('TINYINT(1)'),
		'walk'					=> $DBLayer->dt_varchar(),
		'walk_date'				=> $DBLayer->dt_int(),
		'walk_comment'			=> $DBLayer->dt_text(),
		'move_in_date'			=> $DBLayer->dt_int(),
		'move_in_comment'		=> $DBLayer->dt_text(),
		'remarks'				=> $DBLayer->dt_text(),
		'submited_by'			=> $DBLayer->dt_varchar(),
		'submited_by_uid'		=> $DBLayer->dt_int(),
		'submited_date'			=> $DBLayer->dt_int(),
		'status'				=> $DBLayer->dt_int('TINYINT(1)'),
		'turn_over_id'			=> $DBLayer->dt_int(),
		'fs_req_id'				=> $DBLayer->dt_int(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_pvcr_projects', $schema);

$schema = array(
	'FIELDS'		=> array(
		'id'				=> $DBLayer->dt_serial(),
		'project_id'		=> $DBLayer->dt_int(),
		'project_name'		=> $DBLayer->dt_varchar(),
		'vendor_id'			=> $DBLayer->dt_int(),
		'vendor_group_id'	=> $DBLayer->dt_int(),
		'po_number'			=> $DBLayer->dt_varchar(),
		'start_date'		=> $DBLayer->dt_int(),
		'shift'				=> $DBLayer->dt_int('TINYINT(1)'),
		'remarks'			=> $DBLayer->dt_text(),
		'in_house'			=> $DBLayer->dt_int('TINYINT(1)'),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_pvcr_vendors', $schema);

$DBLayer->add_field('users', 'hca_pvcr_access', 'TINYINT(1)', false, '0');
$DBLayer->add_field('users', 'hca_pvcr_perms', 'VARCHAR(255)', false, '');
$DBLayer->add_field('users', 'hca_pvcr_notify', 'VARCHAR(255)', false, '');
