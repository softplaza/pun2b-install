<?php 

if (!defined('APP_UNINSTALL')) die();

$DBLayer->drop_table('sm_errors_reports');
