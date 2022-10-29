<?php 

if (!defined('APP_UNINSTALL')) die();

//
$DBLayer->drop_table('punch_list_management_maint_locations');
$DBLayer->drop_table('punch_list_management_maint_equipments');
$DBLayer->drop_table('punch_list_management_maint_items');
$DBLayer->drop_table('punch_list_management_maint_properties');
$DBLayer->drop_table('punch_list_management_maint_moisture');

//
$DBLayer->drop_table('punch_list_management_maint_parts_group');
$DBLayer->drop_table('punch_list_management_maint_parts');

//
$DBLayer->drop_table('punch_list_management_maint_request_form');
$DBLayer->drop_table('punch_list_management_maint_request_items');
$DBLayer->drop_table('punch_list_management_maint_request_materials');
$DBLayer->drop_table('punch_list_management_maint_moisture_check_list');
