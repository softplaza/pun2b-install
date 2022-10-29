<?php 

if (!defined('APP_UNINSTALL')) die();

if ($DBLayer->table_exists('sm_vendors'))
	$DBLayer->drop_table('sm_vendors');

if ($DBLayer->table_exists('sm_vendors_groups'))
	$DBLayer->drop_table('sm_vendors_groups');

if ($DBLayer->table_exists('sm_vendors_schedule'))
	$DBLayer->drop_table('sm_vendors_schedule');

if ($DBLayer->field_exists('users', 'sm_vendors_access'))
	$DBLayer->drop_field('users', 'sm_vendors_access');

if ($DBLayer->field_exists('users', 'sm_vendors_perms'))
	$DBLayer->drop_field('users', 'sm_vendors_perms');
