<?php 

if (!defined('APP_INSTALL')) die();

$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'item_number'			=> $DBLayer->dt_varchar(),
		'item_name'				=> $DBLayer->dt_varchar(),
		'item_description'		=> $DBLayer->dt_text(),
		'quantity_total'		=> $DBLayer->dt_int(),
		'limit_min'				=> $DBLayer->dt_int(),
		'limit_max'				=> $DBLayer->dt_int(),
		'updated_date'			=> $DBLayer->dt_date(),
		'updated_by'			=> $DBLayer->dt_int(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('swift_inventory_management_items', $schema);

$schema = array(
	'FIELDS'		=> array(
		'id'					=> $DBLayer->dt_serial(),
		'item_id'				=> $DBLayer->dt_int(),
		'property_id'			=> $DBLayer->dt_int(),
		'wh_quantity'			=> $DBLayer->dt_int(),
		'wh_limit_min'			=> $DBLayer->dt_int(),
		'wh_limit_max'			=> $DBLayer->dt_int(),
		'wh_updated_date'		=> $DBLayer->dt_date(),
		'wh_updated_by'			=> $DBLayer->dt_int(),
	),
	'PRIMARY KEY'	=> array('id')
);
$DBLayer->create_table('swift_inventory_management_warehouse', $schema);

$schema = array(
	'FIELDS'		=> array(
		'warehouse_id'			=> $DBLayer->dt_int(),
		'a_quantity'			=> $DBLayer->dt_int(),
		'a_action'				=> $DBLayer->dt_int('TINYINT(1)'), // 0 - new, 1 - plus, 2 - minus
		'a_submitted_date'		=> $DBLayer->dt_date(),
		'a_submitted_by'		=> $DBLayer->dt_int(),
	),
);
$DBLayer->create_table('swift_inventory_management_actions', $schema);
