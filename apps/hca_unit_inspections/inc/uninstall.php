<?php 

if (!defined('APP_UNINSTALL')) die();

$DBLayer->drop_table('hca_unit_inspections_items');
$DBLayer->drop_table('hca_unit_inspections_checklist');
$DBLayer->drop_table('hca_unit_inspections_checklist_items');
$DBLayer->drop_table('hca_unit_inspections_actions');



