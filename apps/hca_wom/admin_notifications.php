<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_wom', 52))
	message($lang_common['No permission']);

$SwiftSettings = new SwiftSettings;

// OPTIONS START
// Set project ID
$SwiftSettings->setId('hca_wom');

// Set User or Group notifications
$SwiftSettings->addNotifyOption(1, 'Task assigned');
$SwiftSettings->addNotifyOption(2, 'Task completed');
// OPTIONS END

$SwiftSettings->POST();

$Core->set_page_id('hca_wom_admin_notifications', 'hca_wom');
require SITE_ROOT.'header.php';

if ($User->is_admmod())
	$SwiftSettings->createRule();

$SwiftSettings->getGroupNotifications();

$SwiftSettings->getUserNotifications();

$SwiftSettings->getJS();

require SITE_ROOT.'footer.php';
