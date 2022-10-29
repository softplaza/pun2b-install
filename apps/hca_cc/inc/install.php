<?php 

if (!defined('APP_INSTALL')) die();

$schema = array(
	'FIELDS'		=> array(
		'id'						=> $DBLayer->dt_serial(),
		'item_name'					=> $DBLayer->dt_varchar(),
		'item_desc'					=> $DBLayer->dt_text(),
		'frequency'					=> $DBLayer->dt_int('TINYINT(2)'),
		'department'				=> $DBLayer->dt_int(),

		'action_owner'				=> $DBLayer->dt_int(),
		'property_id'				=> $DBLayer->dt_int(),
		'required_by'				=> $DBLayer->dt_int(),
		//'date_start'				=> $DBLayer->dt_date(),// start point
		
		'months_due'				=> $DBLayer->dt_varchar(),

		'date_last_completed'		=> $DBLayer->dt_date(),
		'last_completed_by'			=> $DBLayer->dt_int(),

		'date_completed'			=> $DBLayer->dt_date(),
		'completed_by'				=> $DBLayer->dt_int(),

		'date_due'					=> $DBLayer->dt_date(),// automatically setup

		'time_updated'				=> $DBLayer->dt_int(),
		'updated_by'				=> $DBLayer->dt_int(),
		//'notes'						=> $DBLayer->dt_text(),

		'last_tracking_id'			=> $DBLayer->dt_int(), // replace on
		'last_track_id'				=> $DBLayer->dt_int(), //<----

		'last_notified'				=> $DBLayer->dt_int(),
		//'last_project_id'			=> $DBLayer->dt_int(),
		//created_by
		//updated_by
		'item_type'					=> $DBLayer->dt_int('TINYINT(1)'),
		'status'					=> $DBLayer->dt_int('TINYINT(1)'),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_cc_items', $schema);

$schema = array(
	'FIELDS'		=> array(
		'id'						=> $DBLayer->dt_serial(),
		'item_id'					=> $DBLayer->dt_int(),
		'track_id'					=> $DBLayer->dt_int(),
		'time_updated'				=> $DBLayer->dt_int(),
		'updated_by'				=> $DBLayer->dt_int(),
		'notes'						=> $DBLayer->dt_text(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_cc_actions', $schema);

// replace on field in item table
$schema = array(
	'FIELDS'		=> array(
		'id'						=> $DBLayer->dt_serial(),
		'item_id'					=> $DBLayer->dt_int(),
		'property_id'				=> $DBLayer->dt_int(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_cc_properties', $schema);

$schema = array(
	'FIELDS'		=> array(
		'id'						=> $DBLayer->dt_serial(),
		'item_id'					=> $DBLayer->dt_int(),
		'user_id'					=> $DBLayer->dt_int(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_cc_owners', $schema);

$schema = array(
	'FIELDS'		=> array(
		'id'						=> $DBLayer->dt_serial(),
		'item_id'					=> $DBLayer->dt_int(),

		'date_last_completed'		=> $DBLayer->dt_date(),
		'last_completed_by'			=> $DBLayer->dt_int(),

		'date_completed'			=> $DBLayer->dt_date(),
		'completed_by'				=> $DBLayer->dt_int(),

		'time_updated'				=> $DBLayer->dt_int(),
		'updated_by'				=> $DBLayer->dt_int(),

		'notes'						=> $DBLayer->dt_text(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_cc_items_tracking', $schema);

// ?????
$schema = array(
	'FIELDS'		=> array(
		'id'						=> $DBLayer->dt_serial(),
		'item_id'					=> $DBLayer->dt_int(),
		'start_date'				=> $DBLayer->dt_int(),
		'end_date'					=> $DBLayer->dt_int(),

		//'notes'						=> $DBLayer->dt_text(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_cc_tracks', $schema);
