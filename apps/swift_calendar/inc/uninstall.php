<?php 

if (!defined('APP_UNINSTALL')) die();

if ($DBLayer->table_exists('sm_calendar_events'))
	$DBLayer->drop_table('sm_calendar_events');

if ($DBLayer->table_exists('sm_calendar_projects'))
	$DBLayer->drop_table('sm_calendar_projects');

if ($DBLayer->table_exists('sm_calendar_vendors'))
	$DBLayer->drop_table('sm_calendar_vendors');

if ($DBLayer->field_exists('users', 'sm_calendar_access'))
	$DBLayer->drop_field('users', 'sm_calendar_access');
if ($DBLayer->field_exists('users', 'sm_calendar_project_id'))
	$DBLayer->drop_field('users', 'sm_calendar_project_id');


if ($DBLayer->field_exists('users', 'sm_calendar_outlook_email'))
	$DBLayer->drop_field('users', 'sm_calendar_outlook_email');
if ($DBLayer->field_exists('users', 'sm_calendar_outlook_viewer'))
	$DBLayer->drop_field('users', 'sm_calendar_outlook_viewer');
if ($DBLayer->field_exists('sm_special_projects_events', 'sent_to_outlook'))
	$DBLayer->drop_field('sm_special_projects_events', 'sent_to_outlook');


if ($DBLayer->field_exists('users', 'sm_calendar_google_client_id'))
	$DBLayer->drop_field('users', 'sm_calendar_google_client_id');
if ($DBLayer->field_exists('users', 'sm_calendar_google_client_secret'))
	$DBLayer->drop_field('users', 'sm_calendar_google_client_secret');
if ($DBLayer->field_exists('sm_special_projects_events', 'sent_to_google'))
	$DBLayer->drop_field('sm_special_projects_events', 'sent_to_google');

config_remove(array(
	'o_sm_calendar_mailing_list',
	'o_sm_calendar_vendors',
));
