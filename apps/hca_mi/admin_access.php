<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_mi', 21))
	message($lang_common['No permission']);

$SwiftSettings = new SwiftSettings;

// OPTIONS START
// Set project ID
$SwiftSettings->setId('hca_mi');

// Set access to the following pages
$SwiftSettings->addAccessOption(1, 'Current Project');
$SwiftSettings->addAccessOption(2, 'Project Files');
$SwiftSettings->addAccessOption(3, 'Project Invoice');
$SwiftSettings->addAccessOption(4, 'List of Projects');
$SwiftSettings->addAccessOption(5, 'Report');
$SwiftSettings->addAccessOption(6, 'Property Report');
$SwiftSettings->addAccessOption(7, 'Messages');

$SwiftSettings->addAccessOption(20, 'Settings');
$SwiftSettings->addAccessOption(21, 'Access');
$SwiftSettings->addAccessOption(22, 'Permissions');
$SwiftSettings->addAccessOption(23, 'Notifications');
$SwiftSettings->addAccessOption(24, 'Vendor List');
// OPTIONS END

$SwiftSettings->POST();

$Core->set_page_id('hca_5840_admin_access', 'hca_5840');
require SITE_ROOT.'header.php';

if ($User->is_admmod())
	$SwiftSettings->createRule();

$SwiftSettings->getGroupAccess();

$SwiftSettings->getUserAccess();

$SwiftSettings->getJS();

require SITE_ROOT.'footer.php';
