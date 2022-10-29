<?php 

if (!defined('APP_UNINSTALL')) die();

$DBLayer->drop_table('swift_inventory_management_items');
$DBLayer->drop_table('swift_inventory_management_warehouse');
$DBLayer->drop_table('swift_inventory_management_actions');
