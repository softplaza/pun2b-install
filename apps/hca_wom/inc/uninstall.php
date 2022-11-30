<?php 

if (!defined('APP_UNINSTALL')) die();

$DBLayer->drop_table('hca_wom_work_orders');
$DBLayer->drop_table('hca_wom_tasks');
$DBLayer->drop_table('hca_wom_items');