<?php

if (!defined('SITE_ROOT'))
	exit('The constant SITE_ROOT must be defined and point to a valid System installation root directory.');

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
{
	define('SPM_REQUEST_AJAX', 1);
}

require SITE_ROOT.'include/constants.php';

// Record the start time (will be used to calculate the generation time for the page)
$microtime_start = empty($_SERVER['REQUEST_TIME_FLOAT']) ? microtime(true) : (float) $_SERVER['REQUEST_TIME_FLOAT'];//will removed...

// Load classes and files with Composer
require SITE_ROOT.'vendor/autoload.php';

// Autoloading of base classes
//spl_autoload_register('load_base_classes');

// Create basic objects
$Core = new Core;
$Cachinger = new Cachinger;
$Loader = Loader::singleton();

// Load UTF-8 functions
//require SITE_ROOT.'include/utf8/utf8.php';
//require SITE_ROOT.'include/utf8/ucwords.php';
//require SITE_ROOT.'include/utf8/trim.php';

// Reverse the effect of register_globals
unregister_globals();

// Ignore any user abort requests
ignore_user_abort(true);

if (file_exists(SITE_ROOT.'config.php'))
	include SITE_ROOT.'config.php';
else
	exit('The file \'config.php\' doesn\'t exist or is corrupt.<br />Please run <a href="'.SITE_ROOT.'admin/install.php">install.php</a> to install SwiftManager first.');

// Block prefetch requests
if (isset($_SERVER['HTTP_X_MOZ']) && $_SERVER['HTTP_X_MOZ'] == 'prefetch')
{
	header('HTTP/1.1 403 Prefetching Forbidden');

	// Send no-cache headers
	header('Expires: Thu, 21 Jul 1977 07:30:00 GMT');	// When yours truly first set eyes on this world! :)
	header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
	header('Cache-Control: post-check=0, pre-check=0', false);
	header('Pragma: no-cache');		// For HTTP/1.0 compability

	exit;
}

// Make sure PHP reports all errors except E_NOTICE. PunBB supports E_ALL, but a lot of scripts it may interact with, do not.
if (defined('SPM_DEBUG'))
	error_reporting(E_ALL);
else
	error_reporting(E_ALL ^ E_NOTICE);

// Detect UTF-8 support in PCRE
if ((version_compare(PHP_VERSION, '5.1.0', '>=') || (version_compare(PHP_VERSION, '5.0.0-dev', '<=') && version_compare(PHP_VERSION, '4.4.0', '>='))) && @/**/preg_match('/\p{L}/u', 'a') !== FALSE)
{
	define('SPM_SUPPORT_PCRE_UNICODE', 1);
}

// Force POSIX locale (to prevent functions such as strtolower() from messing up UTF-8 strings)
setlocale(LC_CTYPE, 'C');

// Load DB abstraction layer and connect
require SITE_ROOT.'include/dblayer/common_db.php';

// Start a transaction
$DBLayer->start_transaction();

// Create Config object and load cached config
$Config = new Config;

// If the request_uri is invalid try fix it
fix_request_uri();

if (!defined('BASE_URL'))
{
	// Make an educated guess regarding base_url
	$base_url_guess = ((!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off') ? 'https://' : 'http://').preg_replace('/:80$/', '', $_SERVER['HTTP_HOST']).str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
	if (substr($base_url_guess, -1) == '/')
		$base_url_guess = substr($base_url_guess, 0, -1);
	
	 define('BASE_URL', $base_url_guess);
}

// Load hooks
if (file_exists(SPM_CACHE_DIR.'cache_hooks.php'))
	include SPM_CACHE_DIR.'cache_hooks.php';

if (!defined('SPM_HOOKS_LOADED'))
{
	$Cachinger->gen_hooks();
	require SPM_CACHE_DIR.'cache_hooks.php';
}

$FlashMessenger = new FlashMessenger();
$Hooks = new Hooks();

// A good place to add common functions for your apps
$Hooks->get_hook('es_essentials');
$Hooks->get_hook('IncludeEssentials');

if (!defined('SPM_MAX_POSTSIZE_BYTES'))
	define('SPM_MAX_POSTSIZE_BYTES', 65535);

define('SPM_ESSENTIALS_LOADED', 1);
