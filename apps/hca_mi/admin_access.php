<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_mi', 21)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$SwiftSettings = new SwiftSettings;

// OPTIONS START
// Set project ID
$SwiftSettings->setId('hca_mi');

// Set access to the following pages
$SwiftSettings->addAccessOption(1, 'List of Projects');
$SwiftSettings->addAccessOption(2, 'View Report');
$SwiftSettings->addAccessOption(3, 'Messages of Property Manager');
$SwiftSettings->addAccessOption(11, 'Create new projects');
$SwiftSettings->addAccessOption(12, 'Edit projects');
$SwiftSettings->addAccessOption(13, 'Edit Invoice');
$SwiftSettings->addAccessOption(14, 'Upload Files');
$SwiftSettings->addAccessOption(15, 'Create Appendix-B');
$SwiftSettings->addAccessOption(16, 'Send project info to Email');
$SwiftSettings->addAccessOption(17, 'Change project status');
$SwiftSettings->addAccessOption(18, 'Remove projects');

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
