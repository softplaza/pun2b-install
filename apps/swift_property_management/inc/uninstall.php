<?php 

if (!defined('APP_UNINSTALL')) die();

$DBLayer->drop_table('sm_property_db');
$DBLayer->drop_table('sm_property_buildings');
$DBLayer->drop_table('sm_property_units');
$DBLayer->drop_table('sm_property_locations');
$DBLayer->drop_table('sm_property_departments');
$DBLayer->drop_table('sm_property_job_categories');
$DBLayer->drop_table('sm_property_unit_sizes');
$DBLayer->drop_table('sm_property_maps');

$DBLayer->drop_field('users', 'sm_pm_property_id');
$DBLayer->drop_field('users', 'property_access');
$DBLayer->drop_field('groups', 'g_sm_property_mngr');

config_remove(array(
	'o_sm_pm_unit_sizes',
));
