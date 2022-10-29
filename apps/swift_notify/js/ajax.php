<?php

if (!defined('SITE_ROOT') )
	define('SITE_ROOT', '../../../');

require SITE_ROOT.'include/common.php';
require SITE_ROOT.'apps/swift_notify/class/SwiftNotify.php';

if ($User->is_guest())
	message('No permission');

$SwiftNotify = new SwiftNotify;

$Hooks->get_hook('swift_notify_ajax');

echo json_encode($SwiftNotify->getInfo());

// End the transaction
$DBLayer->end_transaction();
// Close the db connection (and free up any result data)
$DBLayer->close();

exit();