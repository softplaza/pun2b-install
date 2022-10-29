<?php 

if (!defined('APP_UNINSTALL')) die();

config_remove(array(
	'o_swift_messenger_app',
    'o_swift_messenger_number',
	'o_swift_messenger_sid',
	'o_swift_messenger_token',
));
