<?php 

if (!defined('APP_UNINSTALL')) die();

if ($DBLayer->table_exists('sm_messenger_topics'))
	$DBLayer->drop_table('sm_messenger_topics');

if ($DBLayer->table_exists('sm_messenger_posts'))
	$DBLayer->drop_table('sm_messenger_posts');

if ($DBLayer->table_exists('sm_messenger_users'))
	$DBLayer->drop_table('sm_messenger_users');
