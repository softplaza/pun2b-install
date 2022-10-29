<?php 

if (!defined('APP_UNINSTALL')) die();

$DBLayer->drop_table('hca_vcr_projects');
$DBLayer->drop_table('hca_vcr_invoices');
$DBLayer->drop_table('hca_vcr_vendors');

$DBLayer->drop_field('users', 'hca_vcr_access');
$DBLayer->drop_field('users', 'hca_vcr_perms');
$DBLayer->drop_field('users', 'hca_vcr_notify');
$DBLayer->drop_field('users', 'hca_vcr_groups');
$DBLayer->drop_field('users', 'hca_vcr_group');
$DBLayer->drop_field('sm_vendors', 'hca_vcr');

config_remove(array(
	'o_hca_vcr_unit_sizes',
	'o_hca_vcr_default_carpet_vendor',
	'o_hca_vcr_default_vinyl_vendor',
	'o_hca_vcr_default_urine_vendor',
	'o_hca_vcr_default_pest_vendor',
	'o_hca_vcr_default_cleaning_vendor',
	'o_hca_vcr_default_painter_vendor',
	'o_hca_vcr_default_refinish_vendor',
	'o_hca_vcr_email_text_vendor_schedule',
	'o_hca_vcr_complete_expired_days'
));
