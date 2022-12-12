<?php 

if (!defined('APP_UNINSTALL')) die();

$DBLayer->drop_table('sm_calendar_events');
$DBLayer->drop_table('sm_calendar_projects');
$DBLayer->drop_table('sm_calendar_vendors');

$DBLayer->drop_field('users', 'sm_calendar_access');
$DBLayer->drop_field('users', 'sm_calendar_project_id');

$DBLayer->drop_field('users', 'sm_calendar_outlook_email');
$DBLayer->drop_field('users', 'sm_calendar_outlook_viewer');
$DBLayer->drop_field('sm_special_projects_events', 'sent_to_outlook');

$DBLayer->drop_field('users', 'sm_calendar_google_client_id');
$DBLayer->drop_field('users', 'sm_calendar_google_client_secret');
$DBLayer->drop_field('sm_special_projects_events', 'sent_to_google');

config_remove(array(
	'o_sm_calendar_mailing_list',
	'o_sm_calendar_vendors',
));
