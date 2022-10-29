<?php

if (!defined('SITE_ROOT'))
	exit('The constant SITE_ROOT must be defined and point to a valid SwiftManager installation root directory.');

if (!defined('SPM_ESSENTIALS_LOADED'))
	require SITE_ROOT.'include/essentials.php';

// List of included base classes
$Lang = new Lang;
$FormatDateTime = new FormatDateTime;

// Turn off magic_quotes_runtime
if (version_compare(PHP_VERSION, '7.4.0', '<') && get_magic_quotes_runtime())
	@ini_set('magic_quotes_runtime', false);

// Strip slashes from GET/POST/COOKIE (if magic_quotes_gpc is enabled)
if (version_compare(PHP_VERSION, '7.4.0', '<') && get_magic_quotes_gpc())
{
	function stripslashes_array($array)
	{
		return is_array($array) ? array_map('stripslashes_array', $array) : stripslashes($array);
	}

	$_GET = stripslashes_array($_GET);
	$_POST = stripslashes_array($_POST);
	$_COOKIE = stripslashes_array($_COOKIE);
}

// Strip out "bad" UTF-8 characters
remove_bad_characters();

// If a cookie name is not specified in config.php, we use the default (forum_cookie)
if (empty($cookie_name))
	$cookie_name = 'forum_cookie';

// Enable output buffering
if (!defined('SPM_DISABLE_BUFFERING'))
{
	// For some very odd reason, "Norton Internet Security" unsets this
	$_SERVER['HTTP_ACCEPT_ENCODING'] = isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '';

	// Should we use gzip output compression?
	if ($Config->get('o_gzip') && extension_loaded('zlib') && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false || strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate') !== false))
		ob_start('ob_gzhandler');
	else
		ob_start();
}

// Define standard date/time formats
$forum_time_formats = array($Config->get('o_time_format'), 'H:i:s', 'H:i', 'g:i:s a', 'g:i a');
$forum_date_formats = array($Config->get('o_date_format'), 'Y-m-d', 'Y-d-m', 'd-m-Y', 'm-d-Y', 'M j Y', 'jS M Y');
$FormatDateTime->set_time_formats();
$FormatDateTime->set_date_formats();

// Create page_param array
$page_param = array();

$PagesNavigator = new PagesNavigator;

// Login and fetch user info
$User = new User;

// Attempt to load the common language file
if (file_exists(SITE_ROOT.'lang/'.$User->get('language').'/common.php'))
	include SITE_ROOT.'lang/'.$User->get('language').'/common.php';
else
	error('There is no valid language pack \''.html_encode($User->get('language')).'\' installed.<br />Please reinstall a language of that name.');

// Setup the URL rewriting scheme
$URL = new URL;
$Templator = new Templator;
$SwiftMenu = new SwiftMenu;

// A good place to modify the URL scheme
$Hooks->get_hook('co_modify_url_scheme');

if (isset($forum_url))
	$URL->add_urls($forum_url);
if (isset($url_scheme))
	$URL->add_urls($url_scheme);

// Check if we are to display a maintenance message
if ($Config->get('o_maintenance') && $User->get('g_id') > USER_GROUP_ADMIN && !defined('SPM_TURN_OFF_MAINT'))
	maintenance_message();

// Load cached bans
if (file_exists(SPM_CACHE_DIR.'bans_info.php'))
	include SPM_CACHE_DIR.'bans_info.php';

if (!defined('CACHE_BANS_INFO_LOADED'))
{
	$Cachinger->gen_bans();
	require SPM_CACHE_DIR.'bans_info.php';
}

// Check if current user is banned
check_bans();

// Update online list
$User->update_online();

// Check to see if we logged in without a cookie being set
if ($User->is_guest() && isset($_GET['login']))
	message($lang_common['No cookie']);

// If we're an administrator or moderator, make sure the CSRF token in $_POST is valid (token in post.php is dealt with in post.php)
if (!empty($_POST) && (isset($_POST['confirm_cancel']) || (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== generate_form_token(get_current_url()))) && !defined('SPM_SKIP_CSRF_CONFIRM'))
	csrf_confirm_form();

// Autoload classes
spl_autoload_register('load_apps_classes');

// Set parameters for access and validation within functions
$SwiftMailer = new SwiftMailer;

$Hooks->get_hook('IncludeCommon');
Hook::doAction('IncludeCommon');
