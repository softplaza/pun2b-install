<?php
/**
 * HOW TO USE IT
 * 
 * $User->checkAccess($app_id, $key);
 * $User->checkPermissions($app_id, $key);
 * $User->checkNotification($app_id, $key, $user_id);
 * 
 * 
 * 
 */


define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_wom', 100)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$SwiftSettings = new SwiftSettings;

// Settings START
// Set project ID
$SwiftSettings->setId('hca_wom');

// Set User access, permissions and notifications.
$SwiftSettings->addAccessOption(1, 'Quick Task Entry');
$SwiftSettings->addAccessOption(2, 'Edit WO');
$SwiftSettings->addAccessOption(3, 'Active WO');
$SwiftSettings->addAccessOption(4, 'Suggested WO');
//$SwiftSettings->addAccessOption(5, 'Work Order Templates');
//$SwiftSettings->addAccessOption(6, 'Recurring Work Orders');
$SwiftSettings->addAccessOption(7, 'WO Report');

$SwiftSettings->addAccessOption(11, 'To-Do List');// Active Tasks
$SwiftSettings->addAccessOption(12, 'Unassigned Tasks');
$SwiftSettings->addAccessOption(13, 'Completed Tasks');
$SwiftSettings->addAccessOption(14, 'Suggest New WO');
$SwiftSettings->addAccessOption(15, 'View Suggested WO');

$SwiftSettings->addAccessOption(90, 'WO Items');
$SwiftSettings->addAccessOption(100, 'Settings');

//$SwiftSettings->addPermissionOption(10, 'PermissionOption');
//$SwiftSettings->addNotifyOption(1, 'NotifyOption');
// Settings END
$SwiftSettings->addPermissionOption(10, 'Upload Images');


$SwiftSettings->addNotifyOption(1, 'Task created');
$SwiftSettings->addNotifyOption(2, 'Task accepted');
$SwiftSettings->addNotifyOption(3, 'Task completed');
$SwiftSettings->addNotifyOption(4, 'Task approved');
$SwiftSettings->addNotifyOption(5, 'Task closed');

$SwiftSettings->POST();

$Core->set_page_id('hca_wom_settings', 'hca_wom');
require SITE_ROOT.'header.php';

if ($User->is_admmod())
{
	$SwiftSettings->createRule();
}

$SwiftSettings->getGroupAccess();

$SwiftSettings->getUserAccess();

$SwiftSettings->getGroupPermissions();

$SwiftSettings->getUserPermissions();

//$SwiftSettings->getGroupNotifications();

$SwiftSettings->getUserNotifications();

$SwiftSettings->getJS();

require SITE_ROOT.'footer.php';
