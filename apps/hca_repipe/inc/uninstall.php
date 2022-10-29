<?php 

if (!defined('APP_UNINSTALL')) die();

$DBLayer->drop_table('hca_repipe_projects');
$DBLayer->drop_table('hca_repipe_actions');
