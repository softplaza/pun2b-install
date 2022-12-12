<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_mi', 23)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$SwiftSettings = new SwiftSettings;

// OPTIONS START
// Set project ID
$SwiftSettings->setId('hca_mi');

// Set User or Group notifications
$SwiftSettings->addNotifyOption(1, 'Budget over $5000');
$SwiftSettings->addNotifyOption(2, 'Project was created');
$SwiftSettings->addNotifyOption(3, 'Project was completed');
$SwiftSettings->addNotifyOption(4, 'Project was removed');
// OPTIONS END

$SwiftSettings->POST();

$Core->set_page_id('hca_5840_admin_notifications', 'hca_5840');
require SITE_ROOT.'header.php';

if ($User->is_admmod())
	$SwiftSettings->createRule();

$SwiftSettings->getGroupNotifications();

$SwiftSettings->getUserNotifications();

$SwiftSettings->getJS();

require SITE_ROOT.'footer.php';
