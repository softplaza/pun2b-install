<?php

define('SITE_ROOT', '../../');
require_once SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message('You do not have permission.');

if(isset($_SESSION['access_token']))
	unset($_SESSION['access_token']);
	
$FlashMessenger->add_info('You are logged out.');
redirect($URL->link('profile_settings', $User->get('id')), '.');

