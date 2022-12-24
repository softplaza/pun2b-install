<?php

//$User->checkPermissions(app_id, key);

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_wom', 51))
	message($lang_common['No permission']);

$SwiftSettings = new SwiftSettings;

// Set project ID
$SwiftSettings->setId('hca_wom');

// OPTIONS START
/*
$SwiftSettings->addPermissionOption(1, 'Update WO');
$SwiftSettings->addPermissionOption(8, 'Approve suggested');
$SwiftSettings->addPermissionOption(9, 'Reject suggested');
*/
// OPTIONS END

$SwiftSettings->POST();

$Core->set_page_id('hca_wom_admin_permissions', 'hca_fs');
require SITE_ROOT.'header.php';

if ($User->is_admmod())
	$SwiftSettings->createRule();

$SwiftSettings->getGroupPermissions();

$SwiftSettings->getUserPermissions();

$SwiftSettings->getJS();

require SITE_ROOT.'footer.php';
