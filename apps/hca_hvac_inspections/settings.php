<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_hvac_inspections', 20)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$SwiftSettings = new SwiftSettings;

// Settings START
// Set project ID
$SwiftSettings->setId('hca_hvac_inspections');

// Set User access, permissions and notifications.
$SwiftSettings->addAccessOption(20, 'Settings');
$SwiftSettings->addAccessOption(1, 'CheckList');
$SwiftSettings->addAccessOption(2, 'Work Order');
$SwiftSettings->addAccessOption(4, 'List of Inspections');
$SwiftSettings->addAccessOption(5, 'Smoke Alarms & CO Test');
$SwiftSettings->addAccessOption(6, 'Summary Report');
$SwiftSettings->addAccessOption(8, 'P.O. Numbers');
$SwiftSettings->addAccessOption(9, 'Add/Edit items in Itemslist');
$SwiftSettings->addAccessOption(10, 'Add/Edit filter sizes');
$SwiftSettings->addAccessOption(11, 'Edit CheckList');
$SwiftSettings->addAccessOption(12, 'Edit Work Order');
$SwiftSettings->addAccessOption(13, 'Delete CheckList');
$SwiftSettings->addAccessOption(15, 'Reassign projects');
$SwiftSettings->addAccessOption(17, 'View list of actions');
$SwiftSettings->addAccessOption(18, 'Upload images');
$SwiftSettings->addAccessOption(19, 'Delete images');

//$SwiftSettings->addPermissionOption(10, 'Delete images');
$SwiftSettings->addNotifyOption(1, 'Appendix-B created');
// Settings END

$SwiftSettings->POST();

$Core->set_page_id('hca_hvac_inspections_settings', 'hca_hvac_inspections');
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
