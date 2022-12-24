<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_mi', 23))
	message($lang_common['No permission']);

$SwiftSettings = new SwiftSettings;

// OPTIONS START
// Set project ID
$SwiftSettings->setId('hca_mi');

// Set User or Group notifications
$SwiftSettings->addNotifyOption(1, 'Budget over $5000'); //set in manage_project.php
//$SwiftSettings->addNotifyOption(2, 'Project was created');
//$SwiftSettings->addNotifyOption(3, 'Project was completed');
//$SwiftSettings->addNotifyOption(4, 'Project was removed');
$SwiftSettings->addNotifyOption(5, 'Move Out Date Changed'); //set in manage_project.php

// OPTIONS END

$SwiftSettings->POST();

$Core->set_page_id('hca_mi_admin_notifications', 'hca_mi');
require SITE_ROOT.'header.php';

if ($User->is_admmod())
	$SwiftSettings->createRule();

$SwiftSettings->getGroupNotifications();

$SwiftSettings->getUserNotifications();

$SwiftSettings->getJS();

require SITE_ROOT.'footer.php';
