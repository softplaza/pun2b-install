<?php 

if (!defined('APP_UNINSTALL')) die();

$DBLayer->drop_table('hca_5840_projects');
$DBLayer->drop_table('hca_5840_vendors_filter');
$DBLayer->drop_table('hca_5840_forms');

$DBLayer->drop_table('hca_5840_appendixb');
$DBLayer->drop_field('sm_vendors', 'hca_5840');

$DBLayer->drop_field('users', 'hca_5840_access');

config_remove(array(
	'o_hca_5840_mailing_list',
	'o_hca_5840_mailing_fields',
	'o_hca_5840_locations',
));
