<?php 

if (!defined('APP_UNINSTALL')) die();

$DBLayer->drop_table('hca_sb721_projects');
$DBLayer->drop_table('hca_sb721_vendors');
$DBLayer->drop_table('hca_sb721_checklist');