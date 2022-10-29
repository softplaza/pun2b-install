<?php 

if (!defined('APP_UNINSTALL')) die();

$DBLayer->drop_table('hca_cc_items');
$DBLayer->drop_table('hca_cc_tracks');
$DBLayer->drop_table('hca_cc_actions');

$DBLayer->drop_table('hca_cc_due_dates');//remove
$DBLayer->drop_table('hca_cc_due_months');
$DBLayer->drop_table('hca_cc_properties');
$DBLayer->drop_table('hca_cc_owners');
$DBLayer->drop_table('hca_cc_items_tracking');
$DBLayer->drop_table('hca_cc_projects');
