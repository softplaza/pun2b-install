<?php 

if (!defined('APP_UNINSTALL')) die();

$DBLayer->drop_table('hca_ui_items');
$DBLayer->drop_table('hca_ui_checklist');
$DBLayer->drop_table('hca_ui_checklist_items');
$DBLayer->drop_table('hca_ui_water_pressure');
$DBLayer->drop_table('hca_ui_actions');



