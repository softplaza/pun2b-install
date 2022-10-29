<?php 

if (!defined('APP_UNINSTALL')) die();

$DBLayer->drop_table('hca_trees_projects');

$DBLayer->drop_field('users', 'hca_trees_access');
$DBLayer->drop_field('sm_vendors', 'hca_trees');