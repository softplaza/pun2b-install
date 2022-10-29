<?php 

if (!defined('APP_INSTALL')) die();

$schema = array(
	'FIELDS'		=> array(
		'id'						=> $DBLayer->dt_serial(),
		'property_id'				=> $DBLayer->dt_int(),
		//'property_name'				=> $DBLayer->dt_varchar(), //removed
		'unit_number'				=> $DBLayer->dt_varchar(),
		'location'					=> $DBLayer->dt_varchar(),
		'locations'					=> $DBLayer->dt_varchar(),
		'mois_report_date'			=> $DBLayer->dt_int(),
		'mois_performed_by'			=> $DBLayer->dt_varchar(),
		'performed_uid'				=> $DBLayer->dt_int(),
		'mois_inspection_date'		=> $DBLayer->dt_int(),

		'mois_source'				=> $DBLayer->dt_text(),// must be removed
		'symptoms'					=> $DBLayer->dt_text(),
		'action'					=> $DBLayer->dt_text(),

		'delivery_equip_date'		=> $DBLayer->dt_int(),
		'pickup_equip_date'			=> $DBLayer->dt_int(),
		'delivery_equip_comment'	=> $DBLayer->dt_text(),
		'afcc_date'					=> $DBLayer->dt_int(),
		'afcc_comment' 				=> $DBLayer->dt_text(),
		//Scope of Work/Asbestos
		'asb_vendor'				=> $DBLayer->dt_varchar(),
		'asb_po_number'				=> $DBLayer->dt_varchar(),
		'asb_test_date' 			=> $DBLayer->dt_int(),
		'asb_budget'				=> $DBLayer->dt_varchar(),
		'asb_total_amount'			=> $DBLayer->dt_varchar(),
		'asb_comment' 				=> $DBLayer->dt_text(),
		//Remediation Dates
		'rem_vendor'				=> $DBLayer->dt_varchar(),
		'rem_po_number'				=> $DBLayer->dt_varchar(),
		'rem_start_date'			=> $DBLayer->dt_int(),
		'rem_end_date'				=> $DBLayer->dt_int(),
		'rem_budget'				=> $DBLayer->dt_varchar(),
		'rem_total_amount'			=> $DBLayer->dt_varchar(),
		'rem_comment' 				=> $DBLayer->dt_text(),
		//Constructions Dates
		'cons_vendor'				=> $DBLayer->dt_varchar(),
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
		'final_performed_by'		=> $DBLayer->dt_varchar(),
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
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_5840_projects', $schema);
$DBLayer->add_field('hca_5840_projects', 'symptom_type', 'TINYINT(2)', false, '0');
$DBLayer->drop_field('hca_5840_projects', 'property_name');

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
$schema = array(
	'FIELDS'		=> array(
		'id'						=> $DBLayer->dt_serial(),
		'project_id'				=> $DBLayer->dt_int(),
		'reported_time'				=> $DBLayer->dt_int(),
		'property_name'				=> $DBLayer->dt_varchar(),
		'unit_number'				=> $DBLayer->dt_varchar(),
		'inspector_name'			=> $DBLayer->dt_varchar(),
		'mois_type_clear' 			=> $DBLayer->dt_int('TINYINT(1)'),
		'mois_type_clear_init'		=> $DBLayer->dt_varchar(),
		'mois_type_clear_desc'		=> $DBLayer->dt_varchar(),
		'mois_type_grey'			=> $DBLayer->dt_int('TINYINT(1)'),
		'mois_type_grey_init'		=> $DBLayer->dt_varchar(),
		'mois_type_grey_desc'		=> $DBLayer->dt_varchar(),
		'mois_type_black'			=> $DBLayer->dt_int('TINYINT(1)'),
		'mois_type_black_init'		=> $DBLayer->dt_varchar(),
		'mois_type_black_desc'		=> $DBLayer->dt_varchar(),
		'disc_bldg_attics' 			=> $DBLayer->dt_int('TINYINT(1)'),
		'disc_bldg_attics_init'		=> $DBLayer->dt_varchar(),
		'disc_bldg_attics_desc'		=> $DBLayer->dt_varchar(),
		'disc_bldg_ceilings' 		=> $DBLayer->dt_int('TINYINT(1)'),
		'disc_bldg_ceilings_init'	=> $DBLayer->dt_varchar(),
		'disc_bldg_ceilings_desc'	=> $DBLayer->dt_varchar(),
		'disc_bldg_walls' 			=> $DBLayer->dt_int('TINYINT(1)'),
		'disc_bldg_walls_init'		=> $DBLayer->dt_varchar(),
		'disc_bldg_walls_desc'		=> $DBLayer->dt_varchar(),
		'disc_bldg_windows' 		=> $DBLayer->dt_int('TINYINT(1)'),
		'disc_bldg_windows_init'	=> $DBLayer->dt_varchar(),
		'disc_bldg_windows_desc'	=> $DBLayer->dt_varchar(),
		'disc_bldg_floors' 			=> $DBLayer->dt_int('TINYINT(1)'),
		'disc_bldg_floors_init'		=> $DBLayer->dt_varchar(),
		'disc_bldg_floors_desc'		=> $DBLayer->dt_varchar(),
		'disc_utilit_toilets' 		=> $DBLayer->dt_int('TINYINT(1)'),
		'disc_utilit_toilets_init'	=> $DBLayer->dt_varchar(),
		'disc_utilit_toilets_desc'	=> $DBLayer->dt_varchar(),
		'disc_utilit_washers' 		=> $DBLayer->dt_int('TINYINT(1)'),
		'disc_utilit_washers_init'	=> $DBLayer->dt_varchar(),
		'disc_utilit_washers_desc'	=> $DBLayer->dt_varchar(),
		'disc_utilit_heaters' 		=> $DBLayer->dt_int('TINYINT(1)'),
		'disc_utilit_heaters_init'	=> $DBLayer->dt_varchar(),
		'disc_utilit_heaters_desc'	=> $DBLayer->dt_varchar(),
		'disc_utilit_furnace' 		=> $DBLayer->dt_int('TINYINT(1)'),
		'disc_utilit_furnace_init'	=> $DBLayer->dt_varchar(),
		'disc_utilit_furnace_desc'	=> $DBLayer->dt_varchar(),
		'disc_utilit_sinks' 		=> $DBLayer->dt_int('TINYINT(1)'),
		'disc_utilit_sinks_init'	=> $DBLayer->dt_varchar(),
		'disc_utilit_sinks_desc'	=> $DBLayer->dt_varchar(),
		'disc_utilit_potable' 		=> $DBLayer->dt_int('TINYINT(1)'),
		'disc_utilit_potable_init'	=> $DBLayer->dt_varchar(),
		'disc_utilit_potable_desc'	=> $DBLayer->dt_varchar(),
		'disc_utilit_drain' 		=> $DBLayer->dt_int('TINYINT(1)'),
		'disc_utilit_drain_init'	=> $DBLayer->dt_varchar(),
		'disc_utilit_drain_desc'	=> $DBLayer->dt_varchar(),
		'disc_utilit_hvac' 			=> $DBLayer->dt_int('TINYINT(1)'),
		'disc_utilit_hvac_init'		=> $DBLayer->dt_varchar(),
		'disc_utilit_hvac_desc'		=> $DBLayer->dt_varchar(),
		'location'					=> $DBLayer->dt_varchar(),
		'square_footages'			=> $DBLayer->dt_varchar(),
		'wood_results'				=> $DBLayer->dt_varchar(),
		'concrete_results'			=> $DBLayer->dt_varchar(),
		'location2'					=> $DBLayer->dt_varchar(),
		'square_footages2'			=> $DBLayer->dt_varchar(),
		'wood_results2'				=> $DBLayer->dt_varchar(),
		'concrete_results2'			=> $DBLayer->dt_varchar(),
		'location3'					=> $DBLayer->dt_varchar(),
		'square_footages3'			=> $DBLayer->dt_varchar(),
		'wood_results3'				=> $DBLayer->dt_varchar(),
		'concrete_results3'			=> $DBLayer->dt_varchar(),
		'location4'					=> $DBLayer->dt_varchar(),
		'square_footages4'			=> $DBLayer->dt_varchar(),
		'wood_results4'				=> $DBLayer->dt_varchar(),
		'concrete_results4'			=> $DBLayer->dt_varchar(),
		//Additional fields
		'square_footages1'			=> $DBLayer->dt_varchar(),
		'wood_results1'				=> $DBLayer->dt_varchar(),
		'concrete_results1'			=> $DBLayer->dt_varchar(),
		'location2'					=> $DBLayer->dt_varchar(),
		'square_footages2'			=> $DBLayer->dt_varchar(),
		'wood_results2'				=> $DBLayer->dt_varchar(),
		'concrete_results2'			=> $DBLayer->dt_varchar(),
		'location3'					=> $DBLayer->dt_varchar(),
		'square_footages3'			=> $DBLayer->dt_varchar(),
		'wood_results3'				=> $DBLayer->dt_varchar(),
		'concrete_results3'			=> $DBLayer->dt_varchar(),
		'location4'					=> $DBLayer->dt_varchar(),
		'square_footages4'			=> $DBLayer->dt_varchar(),
		'wood_results4'				=> $DBLayer->dt_varchar(),
		'concrete_results4'			=> $DBLayer->dt_varchar(),
		'action'					=> $DBLayer->dt_text(),
		'email_status' 				=> $DBLayer->dt_int('TINYINT(1)'),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_5840_appendixb', $schema);

$DBLayer->add_field('users', 'hca_5840_access', 'TINYINT(1)', false, '0');
$DBLayer->add_field('sm_vendors', 'hca_5840', 'TINYINT(1)', false, '1');

config_add('o_hca_5840_mailing_list', '');
config_add('o_hca_5840_mailing_fields', '');
config_add('o_hca_5840_locations', '');
