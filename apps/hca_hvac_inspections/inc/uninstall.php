<?php 

if (!defined('APP_UNINSTALL')) die();

$DBLayer->drop_table('hca_hvac_inspections_items');
$DBLayer->drop_table('hca_hvac_inspections_filter_sizes');
$DBLayer->drop_table('hca_hvac_inspections_filters');
$DBLayer->drop_table('hca_hvac_inspections_checklist');
$DBLayer->drop_table('hca_hvac_inspections_checklist_items');
$DBLayer->drop_table('hca_hvac_inspections_actions');



