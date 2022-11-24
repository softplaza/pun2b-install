<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_wom', 20)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$SwiftSettings = new SwiftSettings;

// Settings START
// Set project ID
$SwiftSettings->setId('hca_wom');

// Set User access, permissions and notifications.
$SwiftSettings->addAccessOption(1, 'Create WO');
$SwiftSettings->addAccessOption(2, 'Edit WO');
$SwiftSettings->addAccessOption(3, 'List of WO');
$SwiftSettings->addAccessOption(20, 'Settings');

//$SwiftSettings->addPermissionOption(10, 'PermissionOption');
//$SwiftSettings->addNotifyOption(1, 'NotifyOption');
// Settings END

$SwiftSettings->POST();

$Core->set_page_id('hca_fs_settings', 'hca_fs');
require SITE_ROOT.'header.php';

if ($User->is_admmod())
{
	$SwiftSettings->createRule();
}

$SwiftSettings->getUserAccess();

$SwiftSettings->getGroupAccess();

$SwiftSettings->getUserNotifications();

$SwiftSettings->getJS();

require SITE_ROOT.'footer.php';