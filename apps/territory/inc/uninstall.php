<?php 

if (!defined('APP_UNINSTALL')) die();

$DBLayer->drop_table('swift_territories');
$DBLayer->drop_table('swift_assignments');
