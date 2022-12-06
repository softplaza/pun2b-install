<?php

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
$SwiftSettings->addAccessOption(3, 'Work Orders');
$SwiftSettings->addAccessOption(4, 'To-Do List');
$SwiftSettings->addAccessOption(90, 'WO Items');
$SwiftSettings->addAccessOption(100, 'Settings');

//$SwiftSettings->addPermissionOption(10, 'PermissionOption');
//$SwiftSettings->addNotifyOption(1, 'NotifyOption');
// Settings END

$SwiftSettings->addNotifyOption(1, 'Task created');
$SwiftSettings->addNotifyOption(2, 'Task accepted');
$SwiftSettings->addNotifyOption(3, 'Task completed');
$SwiftSettings->addNotifyOption(4, 'Task approved');
$SwiftSettings->addNotifyOption(5, 'Task closed');

$SwiftSettings->POST();

$Core->set_page_id('hca_fs_settings', 'hca_fs');
require SITE_ROOT.'header.php';

if ($User->is_admmod())
{
	$SwiftSettings->createRule();
}

$SwiftSettings->getGroupAccess();

$SwiftSettings->getUserAccess();

$SwiftSettings->getGroupNotifications();

$SwiftSettings->getUserNotifications();

$SwiftSettings->getJS();

require SITE_ROOT.'footer.php';