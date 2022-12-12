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
		'mois_report_date'			=> $DBLayer->dt_int(),

		'mois_performed_by'			=> $DBLayer->dt_varchar(),// to remove
		'performed_uid'				=> $DBLayer->dt_int(),
		'performed_uid2'			=> $DBLayer->dt_int(),

		'mois_inspection_date'		=> $DBLayer->dt_int(),

		'mois_source'				=> $DBLayer->dt_text(),// must be removed
		'symptoms'					=> $DBLayer->dt_text(),
		'action'					=> $DBLayer->dt_text(),

		// Carpet/Vinyl Sections
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

		'total_cost'				=> $DBLayer->dt_int(),
		'moveout_date'				=> $DBLayer->dt_int(),
		'movein_date'				=> $DBLayer->dt_int(),
		'maintenance_date'			=> $DBLayer->dt_int(),
		'maintenance_comment'		=> $DBLayer->dt_text(),

		'final_performed_by'		=> $DBLayer->dt_varchar(),//to remove
		'final_performed_uid'		=> $DBLayer->dt_int(),

		'final_performed_date'		=> $DBLayer->dt_int(),
		'remarks'					=> $DBLayer->dt_text(),
		'job_status'				=> $DBLayer->dt_int('TINYINT(1)'),
		'email_status'				=> $DBLayer->dt_int('TINYINT(1)'),
		'over_price_notified' 		=> $DBLayer->dt_int('TINYINT(1)'),
		'move_out_notified' 		=> $DBLayer->dt_int('TINYINT(1)'),
		'appendixb' 				=> $DBLayer->dt_int('TINYINT(1)'),
		'link_hash'					=> $DBLayer->dt_varchar(),

		// 2022/08/09
		'chb_slab_leak' 			=> $DBLayer->dt_int('TINYINT(1)'),
		'chb_roof_leak' 			=> $DBLayer->dt_int('TINYINT(1)'),
		'chb_copper_leak' 			=> $DBLayer->dt_int('TINYINT(1)'),
		'chb_exterior_leak' 		=> $DBLayer->dt_int('TINYINT(1)'),
		// replaced on:
		'leak_type' 				=> $DBLayer->dt_int('TINYINT(2)'),
		'symptom_type' 				=> $DBLayer->dt_int('TINYINT(2)'),
	),
	'PRIMARY KEY'	=> ['id']
);
$DBLayer->create_table('hca_5840_projects', $schema);

$DBLayer->add_field('hca_5840_projects', 'performed_uid2', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('hca_5840_projects', 'unit_id', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('hca_5840_projects', 'services_vendor_id', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('hca_5840_projects', 'asb_vendor_id', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('hca_5840_projects', 'rem_vendor_id', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('hca_5840_projects', 'cons_vendor_id', 'INT(10) UNSIGNED', false, '0');



$sm_property_units = $DBLayer->select_all('sm_property_units');
$sm_vendors = $DBLayer->select_all('sm_vendors');
$hca_5840_projects = $DBLayer->select_all('hca_5840_projects');

foreach($hca_5840_projects as $cur_project)
{
	$db_data = [];
	$asb_vendor_id = $rem_vendor_id = $cons_vendor_id = $unit_id = 0;
	foreach($sm_vendors as $cur_vendor)
	{
		if ($asb_vendor_id == 0 && $cur_vendor['vendor_name'] == $cur_project['asb_vendor'])
			$db_data['asb_vendor_id'] = $cur_vendor['id'];

		if ($rem_vendor_id == 0 && $cur_vendor['vendor_name'] == $cur_project['rem_vendor'])
			$db_data['rem_vendor_id'] = $cur_vendor['id'];

		if ($cons_vendor_id == 0 && $cur_vendor['vendor_name'] == $cur_project['cons_vendor'])
			$db_data['cons_vendor_id'] = $cur_vendor['id'];
	}

	foreach($sm_property_units as $cur_unit)
	{
		if ($unit_id == 0 && $cur_unit['unit_number'] == $cur_project['unit_number'] && $cur_unit['property_id'] == $cur_project['property_id'])
		{
			$db_data['unit_id'] = $cur_unit['id'];
			break;
		}
	}

	if (!empty($db_data))
		$DBLayer->update('hca_5840_projects', $db_data, $cur_project['id']);
}



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

// need to be remove
$DBLayer->drop_table('hca_5840_appendixb');
$DBLayer->drop_field('users', 'hca_5840_access');

$DBLayer->add_field('sm_vendors', 'hca_5840', 'TINYINT(1)', false, '1');

config_add('o_hca_5840_mailing_list', '');
//config_add('o_hca_5840_mailing_fields', '');
config_add('o_hca_5840_locations', '');

config_remove(array(
	'o_hca_5840_mailing_fields',
));
