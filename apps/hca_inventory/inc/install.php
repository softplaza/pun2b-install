<?php 

if (!defined('APP_INSTALL')) die();

$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'cat_name'				=> $DBLayer->dt_varchar(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_inventory_categories', $schema);

$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'item_number'			=> $DBLayer->dt_varchar(),
		'item_name'				=> $DBLayer->dt_varchar(),
		'pid'					=> $DBLayer->dt_int(),
		'pick_up_location'		=> $DBLayer->dt_varchar(),
		'cid'					=> $DBLayer->dt_int(),// category
		'total_quantity'		=> $DBLayer->dt_int(),
		'uid'					=> $DBLayer->dt_int(),// user_id
		'last_record_id'		=> $DBLayer->dt_int(),// last record id
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_inventory_equipments', $schema);

$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'equipment_id'			=> $DBLayer->dt_int(),
		'property_id'			=> $DBLayer->dt_int(),
		'sign_out_to'			=> $DBLayer->dt_int(),//user_id
		'sign_out_date'			=> $DBLayer->dt_date(),
		'sign_back_in_date'		=> $DBLayer->dt_date(),
		'sign_out_time'			=> $DBLayer->dt_time(),
		'sign_back_in_time'		=> $DBLayer->dt_time(),
		'returned'				=> $DBLayer->dt_int('TINYINT(1)'),
		'quantity'				=> $DBLayer->dt_int(),
		'comments'				=> $DBLayer->dt_text(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('hca_inventory_records', $schema);

$DBLayer->add_field('hca_inventory_records', 'returned', 'TINYINT(1)', false, '0');
$DBLayer->add_field('hca_inventory_equipments', 'pid', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('hca_inventory_equipments', 'cid', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('hca_inventory_equipments', 'pick_up_location', 'VARCHAR(255)', false);
$DBLayer->add_field('hca_inventory_equipments', 'total_quantity', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('hca_inventory_records', 'comments', 'TEXT', '');

$DBLayer->add_field('hca_inventory_equipments', 'uid', 'INT(10) UNSIGNED', false, '0');
$DBLayer->add_field('hca_inventory_equipments', 'last_record_id', 'INT(10) UNSIGNED', false, '0');
