<?php 

if (!defined('APP_UNINSTALL')) die();

$DBLayer->drop_table('hca_inventory_categories');
$DBLayer->drop_table('hca_inventory_equipments');
$DBLayer->drop_table('hca_inventory_records');
