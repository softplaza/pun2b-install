<?php 

if (!defined('APP_UNINSTALL')) die();

$DBLayer->drop_table('swift_projects');
