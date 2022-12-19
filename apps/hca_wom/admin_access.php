<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_wom', 50))
	message($lang_common['No permission']);

$SwiftSettings = new SwiftSettings;

// OPTIONS START
// Set project ID
$SwiftSettings->setId('hca_wom');

// Set access to the following pages
$SwiftSettings->addAccessOption(1, 'Work Orders');
$SwiftSettings->addAccessOption(2, 'New WO');
$SwiftSettings->addAccessOption(6, 'View WO');
$SwiftSettings->addAccessOption(3, 'Suggested WO');
$SwiftSettings->addAccessOption(4, 'Suggest New WO');
$SwiftSettings->addAccessOption(5, 'To-DO List');
$SwiftSettings->addAccessOption(7, 'Update WO');
$SwiftSettings->addAccessOption(8, 'Approve suggested');
$SwiftSettings->addAccessOption(9, 'Reject suggested');
$SwiftSettings->addAccessOption(10, 'WO Report');

$SwiftSettings->addAccessOption(50, 'Access');
//$SwiftSettings->addAccessOption(51, 'Permissions');
//$SwiftSettings->addAccessOption(52, 'Notifications');
$SwiftSettings->addAccessOption(53, 'Settings');
$SwiftSettings->addAccessOption(54, 'Work Order Templates');
$SwiftSettings->addAccessOption(55, 'Work Order Items');

// OPTIONS END

$SwiftSettings->POST();

$Core->set_page_id('hca_wom_admin_access', 'hca_wom');
require SITE_ROOT.'header.php';

if ($User->is_admmod())
	$SwiftSettings->createRule();

$SwiftSettings->getGroupAccess();

$SwiftSettings->getUserAccess();

$SwiftSettings->getJS();

require SITE_ROOT.'footer.php';
