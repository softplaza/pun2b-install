<?php 

if (!defined('APP_UNINSTALL')) die();

if ($DBLayer->table_exists('sm_sent_emails'))
	$DBLayer->drop_table('sm_sent_emails');
