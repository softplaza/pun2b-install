<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_mi', 22)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$SwiftSettings = new SwiftSettings;

// Set project ID
$SwiftSettings->setId('hca_mi');

// OPTIONS START
$SwiftSettings->addPermissionOption(1, 'Create new project');
$SwiftSettings->addPermissionOption(2, 'Edit projects');
$SwiftSettings->addPermissionOption(3, 'Edit Invoice');
$SwiftSettings->addPermissionOption(4, 'Upload Files');
$SwiftSettings->addPermissionOption(5, 'Create Appendix-B');
$SwiftSettings->addPermissionOption(6, 'Send project to Email');
$SwiftSettings->addPermissionOption(7, 'Change project status');
$SwiftSettings->addPermissionOption(8, 'Remove projects');
// OPTIONS END

$SwiftSettings->POST();

$Core->set_page_id('hca_5840_admin_permissions', 'hca_5840');
require SITE_ROOT.'header.php';

if ($User->is_admmod())
	$SwiftSettings->createRule();

$SwiftSettings->getGroupPermissions();

$SwiftSettings->getUserPermissions();

$SwiftSettings->getJS();

require SITE_ROOT.'footer.php';
