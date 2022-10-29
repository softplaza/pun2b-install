<?php if (!defined('APP_UNINSTALL')) die();

$DBLayer->drop_table('hca_pvcr_projects');
$DBLayer->drop_table('hca_pvcr_vendors');

$DBLayer->drop_field('users', 'hca_pvcr_access');
$DBLayer->drop_field('users', 'hca_pvcr_perms');
$DBLayer->drop_field('users', 'hca_pvcr_notify');

