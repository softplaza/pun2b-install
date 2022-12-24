<?php 

if (!defined('APP_INSTALL')) die();

$schema = array(
	'FIELDS'		=> array(
		'id'						=> $DBLayer->dt_serial(),
		'property_id'				=> $DBLayer->dt_int(),
		'unit_id'					=> $DBLayer->dt_int(),
		'unit_number'				=> $DBLayer->dt_varchar(),// to replace
		'location'					=> $DBLayer->dt_varchar(),
		'locations'					=> $DBLayer->dt_varchar(),
		// Inspection info
		'mois_report_date'			=> $DBLayer->dt_int(),
		'mois_performed_by'			=> $DBLayer->dt_varchar(),// to remove
		'performed_uid'				=> $DBLayer->dt_int(),
		'performed_uid2'			=> $DBLayer->dt_int(),
		'mois_inspection_date'		=> $DBLayer->dt_int(),
		'mois_source'				=> $DBLayer->dt_text(),// must be removed
		'symptoms'					=> $DBLayer->dt_text(),
		'action'					=> $DBLayer->dt_text(),
		// Services
		'services_vendor_id'		=> $DBLayer->dt_int(),
		'delivery_equip_date'		=> $DBLayer->dt_int(),
		'pickup_equip_date'			=> $DBLayer->dt_int(),
		'delivery_equip_comment'	=> $DBLayer->dt_text(),
		'afcc_date'					=> $DBLayer->dt_int(),
		'afcc_comment' 				=> $DBLayer->dt_text(),
		//Scope of Work/Asbestos
		'asb_vendor'				=> $DBLayer->dt_varchar(),//to replace
		'asb_vendor_id'				=> $DBLayer->dt_int(),
		'asb_po_number'				=> $DBLayer->dt_varchar(),
		'asb_test_date' 			=> $DBLayer->dt_int(),
		'asb_budget'				=> $DBLayer->dt_varchar(),
		'asb_total_amount'			=> $DBLayer->dt_varchar(),
		'asb_comment' 				=> $DBLayer->dt_text(),
		//Remediation Dates
		'rem_vendor'				=> $DBLayer->dt_varchar(),//to replace
		'rem_vendor_id'				=> $DBLayer->dt_int(),
		'rem_po_number'				=> $DBLayer->dt_varchar(),
		'rem_start_date'			=> $DBLayer->dt_int(),
		'rem_end_date'				=> $DBLayer->dt_int(),
		'rem_budget'				=> $DBLayer->dt_varchar(),
		'rem_total_amount'			=> $DBLayer->dt_varchar(),
		'rem_comment' 				=> $DBLayer->dt_text(),
		//Constructions Dates
		'cons_vendor'				=> $DBLayer->dt_varchar(),//to replace
		'cons_vendor_id'			=> $DBLayer->dt_int(),
		'cons_po_number'			=> $DBLayer->dt_varchar(),
		'cons_start_date'			=> $DBLayer->dt_int(),
		'cons_end_date'				=> $DBLayer->dt_int(),
		'cons_budget'				=> $DBLayer->dt_varchar(),
		'cons_total_amount'			=> $DBLayer->dt_varchar(),
		'cons_comment' 				=> $DBLayer->dt_text(),
		// Final info
		'total_cost'				=> $DBLayer->dt_int(),
		'moveout_date'				=> $DBLayer->dt_int(),
		'movein_date'				=> $DBLayer->dt_int(),
		'maintenance_date'			=> $DBLayer->dt_int(),
		'maintenance_comment'		=> $DBLayer->dt_text(),
		'final_performed_by'		=> $DBLayer->dt_varchar(),//to remove
		'final_performed_uid'		=> $DBLayer->dt_int(),
		'final_performed_date'		=> $DBLayer->dt_int(),
		'remarks'					=> $DBLayer->dt_text(),
		// 2022/08/09
		'chb_slab_leak' 			=> $DBLayer->dt_int('TINYINT(1)'),
		'chb_roof_leak' 			=> $DBLayer->dt_int('TINYINT(1)'),
		'chb_copper_leak' 			=> $DBLayer->dt_int('TINYINT(1)'),
		'chb_exterior_leak' 		=> $DBLayer->dt_int('TINYINT(1)'),
		// replaced on:
		'leak_type' 				=> $DBLayer->dt_int('TINYINT(2)'),
		'symptom_type' 				=> $DBLayer->dt_int('TINYINT(2)'),
		// project settings
		'job_status'				=> $DBLayer->dt_int('TINYINT(1)'),
		'email_status'				=> $DBLayer->dt_int('TINYINT(1)'),
		'over_price_notified' 		=> $DBLayer->dt_int('TINYINT(1)'),
		'move_out_notified' 		=> $DBLayer->dt_int('TINYINT(1)'),
		'appendixb' 				=> $DBLayer->dt_int('TINYINT(1)'),
		// additional info
		'link_hash'					=> $DBLayer->dt_varchar(),
		'time_created'				=> $DBLayer->dt_int(),
		'created_by'				=> $DBLayer->dt_int(),
		'time_updated'				=> $DBLayer->dt_int(),
		'updated_by'				=> $DBLayer->dt_int(),
	),
	'PRIMARY KEY'	=> ['id']
);
$DBLayer->create_table('hca_5840_projects', $schema);
$DBLayer->add_field('hca_5840_projects', 'time_created', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('hca_5840_projects', 'created_by', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('hca_5840_projects', 'time_updated', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('hca_5840_projects', 'updated_by', 'INT(10) UNSIGNED', false, '0');

$schema = array(
	'FIELDS'		=> array(
		'id'				=> $DBLayer->dt_serial(),
		'vendor_id'			=> $DBLayer->dt_int(),
		'group_id'			=> $DBLayer->dt_int('TINYINT(3)'),
		'enabled'			=> $DBLayer->dt_int('TINYINT(1)', false, '1'),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_5840_vendors_filter', $schema);

$schema = array(
	'FIELDS'		=> array(
		'id'						=> $DBLayer->dt_serial(),
		'project_id'				=> $DBLayer->dt_int(),
		'msg_for_manager'			=> $DBLayer->dt_text(),
		'mailed_time'				=> $DBLayer->dt_int(),
		'msg_from_manager'			=> $DBLayer->dt_text(),
		'submited_time'				=> $DBLayer->dt_int(),
		'submited_by'				=> $DBLayer->dt_varchar(),
		'link_hash'					=> $DBLayer->dt_varchar(),
		'completed_time' 			=> $DBLayer->dt_int(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_5840_forms', $schema);

$schema = [
	'FIELDS'		=> [
		'id'						=> $DBLayer->dt_serial(),
		'project_id'				=> $DBLayer->dt_int(),
		'submitted_by'				=> $DBLayer->dt_int(),
		'time_submitted'			=> $DBLayer->dt_int(),
		'message'					=> $DBLayer->dt_varchar(),
	],
	'PRIMARY KEY'	=> ['id']
];
$DBLayer->create_table('hca_mi_actions', $schema);

$DBLayer->add_field('sm_vendors', 'hca_5840', 'TINYINT(1)', false, '1');

config_add('o_hca_5840_mailing_list', '');
config_add('o_hca_5840_locations', '');

/*
config_remove(array(
	'o_hca_5840_mailing_fields',
));
*/
