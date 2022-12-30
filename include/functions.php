<?php
/**
 * @copyright (C) 2020 SwiftProjectManager.Com, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftManager
 */

 // if empty return current url. Example: add_url_args(['user_id' => $user_id])
function add_url_args($args = [])
{
	$protocol = (empty($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) == 'off') ? 'http://' : 'https://';
	$port = (isset($_SERVER['SERVER_PORT']) && (($_SERVER['SERVER_PORT'] != '80' && $protocol == 'http://') || ($_SERVER['SERVER_PORT'] != '443' && $protocol == 'https://')) && strpos($_SERVER['HTTP_HOST'], ':') === false) ? ':'.$_SERVER['SERVER_PORT'] : '';
	$url = $protocol.$_SERVER['HTTP_HOST'].$port.$_SERVER['REQUEST_URI'];

	$url_components = parse_url($url);

	if (isset($url_components['query']) && isset($url_components['path']))
	{
		parse_str($url_components['query'], $params);
		$new_args = array_merge($params, $args);

		$output = $protocol.$_SERVER['HTTP_HOST'].$port.$url_components['path'];
	
		$first_arg = true;
		if (!empty($new_args))
		{
			foreach($new_args as $k => $v)
			{
				if ($first_arg)
				{
					$output .= '?'.$k.'='.$v;
					$first_arg = false;
				}
				else
					$output .='&'.$k.'='.$v;
			}
		}
	
		return $output;
	}
}

// Generate a hyperlink with parameters and anchor and a subsection such as a subpage
// $args = is array of args
function sublink($link, $sublink, $subarg, $args = null)
{
	global $forum_url;

	if ($sublink == $forum_url['page'] && $subarg == 1)
		return forum_link($link, $args);

	$gen_link = $link;
	if (!is_array($args) && $args !== null)
		$gen_link = str_replace('$1', $args, $link);
	else
	{
		for ($i = 0; isset($args[$i]); ++$i)
			$gen_link = str_replace('$'.($i + 1), $args[$i], $gen_link);
	}

	if (isset($forum_url['insertion_find']))
		$gen_link = BASE_URL.'/'.str_replace($forum_url['insertion_find'], str_replace('$1', str_replace('$1', $subarg, $sublink), $forum_url['insertion_replace']), $gen_link);
	else
		$gen_link = BASE_URL.'/'.$gen_link.str_replace('$1', $subarg, $sublink);

	return $gen_link;
}

// Getting $_GET params
function get_to_str()
{
	$output = [];
	if (!empty($_GET))
	{
		foreach($_GET as $key => $val)
		{
			$output[] = $key.'='.$val;
		}
	}

	return '&'.implode('&', $output);
}

// Return all code blocks that hook into $hook_id
function get_hook($hook_id)
{
	global $forum_hooks;

	return !defined('SPM_HOOKS_LOADED') && isset($forum_hooks[$hook_id]) ? implode("\n", $forum_hooks[$hook_id]) : false;
}

function swift_escape($str)
{
	if (function_exists('mb_ereg_replace'))
	{
		return mb_ereg_replace('[\x00\x0A\x0D\x1A\x22\x27\x5C]', '\\\0', $str);
	} else {
		return preg_replace('~[\x00\x0A\x0D\x1A\x22\x27\x5C]~u', '\\\$0', $str);
	}
}

// 0 is '=', 1 is '>'
function compare_dates($date1, $date2, $k = 0)
{
	// V 1
	//$date1 = ($date1 == '1000-01-01' || $date1 == '0000-00-00' || $date1 == '') ? 0 : strtotime($date1);
	//$date2 = ($date2 == '1000-01-01' || $date2 == '0000-00-00' || $date2 == '') ? 0 : strtotime($date2);

	// v 2
	$date1 = (strtotime($date1) > 0) ? strtotime($date1) : 0;
	$date2 = (strtotime($date2) > 0) ? strtotime($date2) : 0;

	if ($date1 > 0 && $date2 > 0)
	{
		if ($k == 1 && $date1 > $date2)
			return true;
		else if ($k == 0 && $date1 == $date2)
			return true;
	}

	return false;
}

function format_date($date, $format = 'Y-m-d\TH:i:s')
{
	// V1
	//$exceptions = ['0000-00-00', '0000-01-01', '1000-01-01'];
	//$output = !in_array($date) ? $date : '';

	// V2
	$output = (strtotime($date) > 0) ? $date : '';

	if ($format != '' && $output != '')
	{
		$DateTime = new DateTime($output);
		$output = $DateTime->format($format);
	}

	return $output;
}

// Used in Hooks of Apps
function check_app_access($access_info, $app_id = '')
{
	foreach($access_info as $cur_info)
	{
		if ($app_id != '' && $app_id == $cur_info['a_to'])
		{
			return true;
		}
	}
	return false;
}
function check_access($access_info, $key, $app_id = '')
{
	foreach($access_info as $cur_info)
	{
		if ($app_id != '')
		{
			if ($app_id == $cur_info['a_to'] && $cur_info['a_key'] == $key && $cur_info['a_value'] == 1)
				return true;
		}
		else if ($cur_info['a_key'] == $key && $cur_info['a_value'] == 1)
			return true;
	}
	return false;
}
function check_permission($permissions_info, $key, $app_id = '')
{
	foreach($permissions_info as $cur_info)
	{
		if ($app_id != '')
		{
			if ($app_id == $cur_info['p_to'] && $cur_info['p_key'] == $key && $cur_info['p_value'] == 1)
				return true;
		}
		else if ($cur_info['p_key'] == $key && $cur_info['p_value'] == 1)
			return true;
	}
	return false;
}
function check_notification($notifications_info, $key, $app_id = '')
{
	foreach($notifications_info as $cur_info)
	{
		if ($app_id != '')
		{
			if ($app_id == $cur_info['n_to'] && $cur_info['n_key'] == $key && $cur_info['n_value'] == 1)
				return true;
		}
		else if ($cur_info['n_key'] == $key && $cur_info['n_value'] == 1)
			return true;
	}
	return false;
}

// moved - in progress - sterted
function settings_get_access($access_info, $user_id, $key)
{
	$output = [];
	foreach($access_info as $cur_access)
	{
		if ($cur_access['a_uid'] == $user_id && $cur_access['a_key'] == $key)
			$output = $cur_access;
	}
	return $output;
}
// moved - in progress - sterted
function settings_get_group_access($access_info, $gid, $key)
{
	$output = [];
	foreach($access_info as $cur_access)
	{
		if ($cur_access['a_gid'] == $gid && $cur_access['a_key'] == $key)
			$output = $cur_access;
	}
	return $output;
}
function settings_get_permission($permissions_info, $user_id, $key)
{
	$output = [];
	foreach($permissions_info as $cur_permissions)
	{
		if ($cur_permissions['p_uid'] == $user_id && $cur_permissions['p_key'] == $key)
			$output = $cur_permissions;
	}
	return $output;
}
// moved - in progress - sterted
function get_cur_notification($notifications_info, $user_id, $key)
{
	$output = [];
	foreach($notifications_info as $cur_notifications)
	{
		if ($cur_notifications['n_uid'] == $user_id && $cur_notifications['n_key'] == $key)
			$output = $cur_notifications;
	}
	return $output;
}
// End SwiftSettings



// Autoloading classes of current application
function load_apps_classes($class_name)
{
	$path_to_file = getcwd().'/classes/'.$class_name.'.php';
	
	if (file_exists($path_to_file)) {
		require $path_to_file;
	}
}

// Add config value to config table
// Warning!
// This function dont refresh config cache - use "$Cachinger->clear()" if
// call this function outside install/uninstall extension manifest section
function config_add($name, $value)
{
	global $DBLayer, $Config;

	if (!$Config->key_exists($name))
	{
		$query = array(
			'INSERT'	=> 'conf_name, conf_value',
			'INTO'		=> 'config',
			'VALUES'	=> '\''.$name.'\', \''.$value.'\''
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	}
}

// Remove config value from config table
// Warning!
// This function dont refresh config cache - use "$Cachinger->clear()" if
// call this function outside install/uninstall extension manifest section
function config_remove($name)
{
	global $DBLayer;

	if (is_array($name) && count($name) > 0)
	{
		if (!function_exists('clean_conf_names'))
		{
			function clean_conf_names($n)
			{
				global $DBLayer;
				return '\''.$DBLayer->escape($n).'\'';
			}
		}

		$name = array_map('clean_conf_names', $name);

		$query = array(
			'DELETE'	=> 'config',
			'WHERE'		=> 'conf_name IN ('.implode(',', $name).')',
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	}
	else if (!empty($name))
	{
		$query = array(
			'DELETE'	=> 'config',
			'WHERE'		=> 'conf_name=\''.$DBLayer->escape($name).'\''
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	}
}

// Generates a salted, SHA-1 hash of $str
function spm_hash($str, $salt)
{
	return sha1($salt.sha1($str));
}

// A wrapper for PHP's number_format function
function gen_number_format($number, $decimals = 0)
{
	global $lang_common;
	
	$number = is_numeric($number) ? $number : 0;
	
	return number_format($number, $decimals, $lang_common['lang_decimal_point'], $lang_common['lang_thousands_sep']);
}

// Trim whitespace including non-breaking space
function swift_trim($str, $charlist = " \t\n\r\0\x0B\xC2\xA0")
{
	return utf8_trim($str, $charlist);
}

// Multi Sorting array by key
// Example: $new_array = array_msort($array, ['position' => SORT_ASC]);
function array_msort($array, $cols)
{
    $colarr = array();
    foreach ($cols as $col => $order) {
        $colarr[$col] = array();
        foreach ($array as $k => $row) { $colarr[$col]['_'.$k] = strtolower($row[$col]); }
    }
    $eval = 'array_multisort(';
    foreach ($cols as $col => $order) {
        $eval .= '$colarr[\''.$col.'\'],'.$order.',';
    }
    $eval = substr($eval,0,-1).');';
    eval($eval);
    $ret = array();
    foreach ($colarr as $col => $arr) {
        foreach ($arr as $k => $v) {
            $k = substr($k,1);
            if (!isset($ret[$k])) $ret[$k] = $array[$k];
            $ret[$k][$col] = $array[$k][$col];
        }
    }
    
    return $ret;
}

// Start PHP session
function check_session_start() {
	static $session_started = FALSE;

	// Check if session already started
	if ($session_started && session_id())
		return;

	session_cache_limiter(FALSE);

	// Check session id
	$session_id = NULL;
	if (isset($_COOKIE['PHPSESSID']))
		$session_id = $_COOKIE['PHPSESSID'];
	else if (isset($_GET['PHPSESSID']))
		$session_id = $_GET['PHPSESSID'];

	if (empty($session_id) || !preg_match('/^[a-z0-9\-,]{16,32}$/i', $session_id))
	{
		// Create new session id
		$session_id = random_key(32, FALSE, TRUE);
		session_id($session_id);
	}

	if (!isset($_SESSION))
	{
		session_start();
	}

	if (!isset($_SESSION['initiated']))
	{
		session_regenerate_id();
		$_SESSION['initiated'] = TRUE;
	}

	$session_started = TRUE;
}

// Delete every .php file in the forum's cache directory
function clear_cache($file = '')
{
	$d = dir(SPM_CACHE_DIR);
	if ($file != '')
	{
		if (file_exists(SPM_CACHE_DIR.$file))
			@unlink(SPM_CACHE_DIR.$file);
	}
	else if ($d)
	{
		while (($entry = $d->read()) !== false)
		{
			if (substr($entry, strlen($entry)-4) == '.php')
				@unlink(SPM_CACHE_DIR.$entry);
		}
		$d->close();
	}
}

// Convert \r\n and \r to \n
function convert_line_breaks($str)
{
	return str_replace(array("\r\n", "\r"), "\n", $str);
}

// Encodes the contents of $str so that they are safe to output on an (X)HTML page
function html_encode($str)
{
	return is_string($str) ? htmlspecialchars($str, ENT_QUOTES, 'UTF-8') : '';
}

// Generates a salted, SHA-1 hash of $str
function gen_hash($str, $salt)
{
	return sha1($salt.sha1($str));
}

// Return a list of all dirs. Set $path without slash /
function get_dir_list($path = '')
{
	$dirs = array();

	$d = dir($path);
	while (($entry = $d->read()) !== false)
	{
		if ($entry != '.' && $entry != '..' && is_dir($path.'/'.$entry))
			$dirs[] = $entry;
	}
	$d->close();

	return $dirs;
}

// Return a list of all dir's files
function get_dir_files($path, $allowed_ext)
{
	$files = [];
	
	if (file_exists($path))
	{
		$dir = scandir($path);
		foreach ($dir as $key => $file_name)
		{
			$file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
			if (!in_array($file_name, ['.', '..']) && $file_ext == $allowed_ext)
			{
				$files[] = $file_name;
			}
		}
	}
	
	return $files;
}

//  Inserts HTML line breaks before all newlines in a string
function line_break_to_br($text)
{
	return nl2br($text);
}

// Convert HTML to simple text
function html_to_text($text)
{
	$text = strip_tags($text, '<br>');
	$text = str_replace('<br>', "\n", $text);
	
	return $text;
}

// Trim whitespace including non-breaking space
function sm_trim($str, $charlist = " \t\n\r\0\x0B\xC2\xA0")
{
	return utf8_trim($str, $charlist);
}

// Validate an e-mail address
function is_valid_email($email)
{
	if (strlen($email) > 80)
		return false;

	return preg_match('/^(([^<>()[\]\\.,;:\s@"\']+(\.[^<>()[\]\\.,;:\s@"\']+)*)|("[^"\']+"))@((\[\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\])|(([a-zA-Z\d\-]+\.)+[a-zA-Z]{2,}))$/', $email);
}

// Check if $email is banned
function is_banned_email($email)
{
	global $DBLayer, $bans_info;

	foreach ($bans_info as $cur_ban)
	{
		if ($cur_ban['email'] != '' &&
			($email == $cur_ban['email'] ||
			(strpos($cur_ban['email'], '@') === false && stristr($email, '@'.$cur_ban['email']))))
			return true;
	}

	return false;
}

// This function was originally a part of the phpBB Group forum software phpBB2 (http://www.phpbb.com).
// They deserve all the credit for writing it. 
function server_parse($socket, $expected_response)
{
	global $Core;
	$server_response = '';
	$result = true;

	while (substr($server_response, 3, 1) != ' ')
	{
		if (!($server_response = fgets($socket, 256)))
		{
			$result = false;
			$Core->add_error('Couldn\'t get mail server response codes.<br />Please contact the site administrator.');
			break;
		}
	}

	if (!$result)
		return false;

	if (!(substr($server_response, 0, 3) == $expected_response))
	{
		$Core->add_error('Unable to send e-mail.<br />Please contact the site administrator with the following error message reported by the SMTP server: "'.$server_response.'"');
		return false;
	}
}

// Add config value to config table
// Warning!
// This function dont refresh config cache - use "clear_cache()" if
// call this function outside install/uninstall extension manifest section
function add_config($name, $value)
{
	global $DBLayer, $Config;

	if (!$Config->key_exists($name))
	{
		$query = array(
			'INSERT'	=> 'conf_name, conf_value',
			'INTO'		=> 'config',
			'VALUES'	=> '\''.$name.'\', \''.$value.'\''
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	}
}

// Remove config value from config table
// Warning!
// This function dont refresh config cache - use "clear_cache()" if
// call this function outside install/uninstall extension manifest section
function remove_config($name)
{
	global $DBLayer;

	if (is_array($name) && count($name) > 0)
	{
		if (!function_exists('clean_conf_names'))
		{
			function clean_conf_names($n)
			{
				global $DBLayer;
				return '\''.$DBLayer->escape($n).'\'';
			}
		}

		$name = array_map('clean_conf_names', $name);

		$query = array(
			'DELETE'	=> 'config',
			'WHERE'		=> 'conf_name in ('.implode(',', $name).')',
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	}
	else if (!empty($name))
	{
		$query = array(
			'DELETE'	=> 'config',
			'WHERE'		=> 'conf_name=\''.$DBLayer->escape($name).'\''
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	}
}

// Format a time string according to $date_format, $time_format, and timezones
define('SPM_FT_DATETIME', 0);
define('SPM_FT_DATE', 1);
define('SPM_FT_TIME', 2);

function format_time($timestamp, $type = SPM_FT_DATETIME, $date_format = null, $time_format = null, $no_text = true)
{
	global $User, $Config, $FormatDateTime, $lang_common;

//	if ($timestamp == '')
	if ($timestamp == '' || $timestamp == 0)
		return ($no_text ? '' : $lang_common['Never']);

	if ($date_format === null)
		$date_format = $Config->get('o_date_format') != '' ? $Config->get('o_date_format') : $FormatDateTime->get_date_format($User->get('date_format'));

	if ($time_format === null)
		$time_format = $Config->get('o_time_format') != '' ? $Config->get('o_time_format') : $FormatDateTime->get_time_format($User->get('time_format'));

//	$diff = ($User->get('timezone') + $User->get('dst')) * 3600;
	$dst = (date('I', $timestamp) == 1) ? 1 : 0;
	$diff = ($User->get('timezone') + $dst) * 3600;

	$timestamp += $diff;
	$now = time();

	$formatted_time = '';

	if ($type == SPM_FT_DATETIME || $type == SPM_FT_DATE)
	{
		$formatted_time = gmdate($date_format, $timestamp);

		if (!$no_text)
		{
			$base = gmdate('Y-m-d', $timestamp);
			$today = gmdate('Y-m-d', $now + $diff);
			$yesterday = gmdate('Y-m-d', $now + $diff - 86400);

			if ($base == $today)
				$formatted_time = $lang_common['Today'];
			else if ($base == $yesterday)
				$formatted_time = $lang_common['Yesterday'];
		}
	}

	if ($type == SPM_FT_DATETIME)
		$formatted_time .= ' ';

	if ($type == SPM_FT_DATETIME || $type == SPM_FT_TIME)
		$formatted_time .= gmdate($time_format, $timestamp);

	return $formatted_time;
}

// Extract allowed elements from $_POST['form']
function extract_elements($allowed_elements)
{
	$form = array();

	foreach ($_POST['form'] as $key => $value)
	{
		if (in_array($key, $allowed_elements))
			$form[$key] = $value;
	}

	return $form;
}

// Return current timestamp (with microseconds) as a float
function get_microtime()
{
	return microtime(true);
}

// Inserts $element into $input at $offset
// $offset can be either a numerical offset to insert at (eg: 0 inserts at the beginning of the array)
// or a string, which is the key that the new element should be inserted before
// $key is optional: it's used when inserting a new key/value pair into an associative array
// Use SQLite driver only
function array_insert(&$input, $offset, $element, $key = null)
{
	if ($key == null)
		$key = $offset;

	// Determine the proper offset if we're using a string
	if (!is_int($offset))
		$offset = array_search($offset, array_keys($input), true);

	// Out of bounds checks
	if ($offset > count($input))
		$offset = count($input);
	else if ($offset < 0)
		$offset = 0;

	$input = array_merge(array_slice($input, 0, $offset), array($key => $element), array_slice($input, $offset));
}

// Unset any variables instantiated as a result of register_globals being enabled
function unregister_globals()
{
	$register_globals = @ini_get('register_globals');
	if ($register_globals === '' || $register_globals === '0' || strtolower($register_globals) === 'off')
		return;

	// Prevent script.php?GLOBALS[foo]=bar
	if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS']))
		exit('I\'ll have a steak sandwich and... a steak sandwich.');

	// Variables that shouldn't be unset
	$no_unset = array('GLOBALS', '_GET', '_POST', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES');

	// Remove elements in $GLOBALS that are present in any of the superglobals
	$input = array_merge($_GET, $_POST, $_COOKIE, $_SERVER, $_ENV, $_FILES, isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array());
	foreach ($input as $k => $v)
		if (!in_array($k, $no_unset) && isset($GLOBALS[$k]))
		{
			unset($GLOBALS[$k]);
			unset($GLOBALS[$k]);	// Double unset to circumvent the zend_hash_del_key_or_index hole in PHP <4.4.3 and <5.1.4
		}
}

// Removes any "bad" characters (characters which mess with the display of a page, are invisible, etc) from user input
function remove_bad_characters()
{
	global $bad_utf8_chars;

	$bad_utf8_chars = array("\0", "\xc2\xad", "\xcc\xb7", "\xcc\xb8", "\xe1\x85\x9F", "\xe1\x85\xA0", "\xe2\x80\x80", "\xe2\x80\x81", "\xe2\x80\x82", "\xe2\x80\x83", "\xe2\x80\x84", "\xe2\x80\x85", "\xe2\x80\x86", "\xe2\x80\x87", "\xe2\x80\x88", "\xe2\x80\x89", "\xe2\x80\x8a", "\xe2\x80\x8b", "\xe2\x80\x8e", "\xe2\x80\x8f", "\xe2\x80\xaa", "\xe2\x80\xab", "\xe2\x80\xac", "\xe2\x80\xad", "\xe2\x80\xae", "\xe2\x80\xaf", "\xe2\x81\x9f", "\xe3\x80\x80", "\xe3\x85\xa4", "\xef\xbb\xbf", "\xef\xbe\xa0", "\xef\xbf\xb9", "\xef\xbf\xba", "\xef\xbf\xbb", "\xE2\x80\x8D");

	if (!function_exists('_remove_bad_characters'))
	{
	    function _remove_bad_characters($array)
	    {
	        global $bad_utf8_chars;
		    return is_array($array) ? array_map('_remove_bad_characters', $array) : str_replace($bad_utf8_chars, '', $array);
	    }
	}

	$_GET = _remove_bad_characters($_GET);
	$_POST = _remove_bad_characters($_POST);
	$_COOKIE = _remove_bad_characters($_COOKIE);
	$_REQUEST = _remove_bad_characters($_REQUEST);
}

// Fix the REQUEST_URI if we can, since both IIS6 and IIS7 break it
function fix_request_uri()
{
	if (defined('SPM_IGNORE_REQUEST_URI'))
		return;

	global $Config;

	if (!isset($_SERVER['REQUEST_URI']) || (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']) && strpos($_SERVER['REQUEST_URI'], '?') === false))
	{
		// Workaround for a bug in IIS7
		if (isset($_SERVER['HTTP_X_ORIGINAL_URL']))
			$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];

		// IIS6 also doesn't set REQUEST_URI, If we are using the default SEF URL scheme then we can work around it
		else if ($Config->get('o_sef') == 'Default')
		{
			$requested_page = str_replace(array('%26', '%3D', '%2F', '%3F'), array('&', '=', '/', '?'), rawurlencode($_SERVER['PHP_SELF']));
			$_SERVER['REQUEST_URI'] = $requested_page.(isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : '');
		}

		// Otherwise I am not aware of a work around...
		else
			error('The web server you are using is not correctly setting the REQUEST_URI variable.<br />This usually means you are using IIS6, or an unpatched IIS7. Please either disable SEF URLs, upgrade to IIS7 and install any available patches or try a different web server.');
	}
}

// Attempts to fetch the provided URL using any available means
function get_remote_file($url, $timeout, $head_only = false, $max_redirects = 10)
{
	$result = null;
	$parsed_url = parse_url($url);
	$allow_url_fopen = strtolower(@ini_get('allow_url_fopen'));

	// Quite unlikely that this will be allowed on a shared host, but it can't hurt
	if (function_exists('ini_set'))
		@ini_set('default_socket_timeout', $timeout);

	// If we have cURL, we might as well use it
	if (function_exists('curl_init'))
	{
		// Setup the transfer
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_NOBODY, $head_only);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_USERAGENT, 'SwiftManager');

		// Grab the page
		$content = @curl_exec($ch);
		$responce_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		// Process 301/302 redirect
		if ($content !== false && ($responce_code == '301' || $responce_code == '302') && $max_redirects > 0)
		{
			$headers = explode("\r\n", trim($content));
			foreach ($headers as $header)
				if (substr($header, 0, 10) == 'Location: ')
				{
					$responce = get_remote_file(substr($header, 10), $timeout, $head_only, $max_redirects - 1);
					if ($responce !== null)
						$responce['headers'] = array_merge($headers, $responce['headers']);
					return $responce;
				}
		}

		// Ignore everything except a 200 response code
		if ($content !== false && $responce_code == '200')
		{
			if ($head_only)
				$result['headers'] = explode("\r\n", str_replace("\r\n\r\n", "\r\n", trim($content)));
			else
			{
				preg_match('#HTTP/1.[01] 200 OK#', $content, $match, PREG_OFFSET_CAPTURE);
				$last_content = substr($content, $match[0][1]);
				$content_start = strpos($last_content, "\r\n\r\n");
				if ($content_start !== false)
				{
					$result['headers'] = explode("\r\n", str_replace("\r\n\r\n", "\r\n", substr($content, 0, $match[0][1] + $content_start)));
					$result['content'] = substr($last_content, $content_start + 4);
				}
			}
		}
	}
	// fsockopen() is the second best thing
	else if (function_exists('fsockopen'))
	{
		$remote = @fsockopen($parsed_url['host'], !empty($parsed_url['port']) ? intval($parsed_url['port']) : 80, $errno, $errstr, $timeout);
		if ($remote)
		{
			// Send a standard HTTP 1.0 request for the page
			fwrite($remote, ($head_only ? 'HEAD' : 'GET').' '.(!empty($parsed_url['path']) ? $parsed_url['path'] : '/').(!empty($parsed_url['query']) ? '?'.$parsed_url['query'] : '').' HTTP/1.0'."\r\n");
			fwrite($remote, 'Host: '.$parsed_url['host']."\r\n");
			fwrite($remote, 'User-Agent: SwiftManager'."\r\n");
			fwrite($remote, 'Connection: Close'."\r\n\r\n");

			stream_set_timeout($remote, $timeout);
			$stream_meta = stream_get_meta_data($remote);

			// Fetch the response 1024 bytes at a time and watch out for a timeout
			$content = false;
			while (!feof($remote) && !$stream_meta['timed_out'])
			{
				$content .= fgets($remote, 1024);
				$stream_meta = stream_get_meta_data($remote);
			}

			fclose($remote);

			// Process 301/302 redirect
			if ($content !== false && $max_redirects > 0 && preg_match('#^HTTP/1.[01] 30[12]#', $content))
			{
				$headers = explode("\r\n", trim($content));
				foreach ($headers as $header)
					if (substr($header, 0, 10) == 'Location: ')
					{
						$responce = get_remote_file(substr($header, 10), $timeout, $head_only, $max_redirects - 1);
						if ($responce !== null)
							$responce['headers'] = array_merge($headers, $responce['headers']);
						return $responce;
					}
			}

			// Ignore everything except a 200 response code
			if ($content !== false && preg_match('#^HTTP/1.[01] 200 OK#', $content))
			{
				if ($head_only)
					$result['headers'] = explode("\r\n", trim($content));
				else
				{
					$content_start = strpos($content, "\r\n\r\n");
					if ($content_start !== false)
					{
						$result['headers'] = explode("\r\n", substr($content, 0, $content_start));
						$result['content'] = substr($content, $content_start + 4);
					}
				}
			}
		}
	}
	// Last case scenario, we use file_get_contents provided allow_url_fopen is enabled (any non 200 response results in a failure)
	else if (in_array($allow_url_fopen, array('on', 'true', '1')))
	{
		// PHP5's version of file_get_contents() supports stream options
		if (version_compare(PHP_VERSION, '5.0.0', '>='))
		{
			// Setup a stream context
			$stream_context = stream_context_create(
				array(
					'http' => array(
						'method'		=> $head_only ? 'HEAD' : 'GET',
						'user_agent'	=> 'SwiftManager',
						'max_redirects'	=> $max_redirects + 1,	// PHP >=5.1.0 only
						'timeout'		=> $timeout	// PHP >=5.2.1 only
					)
				)
			);

			$content = @file_get_contents($url, false, $stream_context);
		}
		else
			$content = @file_get_contents($url);

		// Did we get anything?
		if ($content !== false)
		{
			// Gotta love the fact that $http_response_header just appears in the global scope (*cough* hack! *cough*)
			$result['headers'] = $http_response_header;
			if (!$head_only)
				$result['content'] = $content;
		}
	}

	return $result;
}

// Clean version string from trailing '.0's
function clean_version($version)
{
	return preg_replace('/(\.0)+(?!\.)|(\.0+$)/', '$2', $version);
}

// A wrapper for PHP's number_format function
function format_number($number, $decimals = 0)
{
	global $lang_common;
	
	$number = is_numeric($number) ? $number : 0;
	
	return number_format($number, $decimals, $lang_common['lang_decimal_point'], $lang_common['lang_thousands_sep']);
}

// Outputs markup to display a user's avatar
function generate_avatar_markup($user_id, $avatar_type, $avatar_width, $avatar_height, $username = NULL, $drop_cache = FALSE)
{
	global $Config;

	$avatar_markup = $avatar_filename = '';

	// Create avatar filename
	switch ($avatar_type)
	{
		case USER_AVATAR_GIF:
			$avatar_filename = $user_id.'.gif';
			break;

		case USER_AVATAR_JPG:
			$avatar_filename = $user_id.'.jpg';
			break;

		case USER_AVATAR_PNG:
			$avatar_filename = $user_id.'.png';
			break;

		case USER_AVATAR_NONE:
		default:
			break;
	}

	// Create markup
	if ($avatar_filename && $avatar_width > 0 && $avatar_height > 0)
	{
		$path = $Config->get('o_avatars_dir').'/'.$avatar_filename;

		//
		if ($drop_cache)
		{
			$path .= '?no_cache='.random_key(8, TRUE);
		}

		$alt_attr = '';
		if (is_string($username) && utf8_strlen($username) > 0) {
			$alt_attr = html_encode($username);
		}

		$avatar_markup = '<img src="'.BASE_URL.'/'.$path.'" width="'.$avatar_width.'" height="'.$avatar_height.'" alt="'.$alt_attr.'" />';
	}

	return $avatar_markup;
}

// Display executed queries (if enabled) for debug
function get_saved_queries()
{
	global $DBLayer, $microtime_start;

	// Get the queries so that we can print them out
	$saved_queries = $DBLayer->get_saved_queries();

	// Calculate script generation time
	$time_diff = get_microtime() - $microtime_start;
	$query_time_total = $time_percent_db = 0.0;

	if (count($saved_queries) > 0)
	{
		foreach ($saved_queries as $cur_query)
		{
			$query_time_total += $cur_query[1];
		}

		if ($query_time_total > 0 && $time_diff > 0)
		{
			$time_percent_db = ($query_time_total / $time_diff) * 100;
		}
	}
?>
<div class="accordion accordion-flush mb-3" id="accordionFlushExample">
	<div class="accordion-item">
		<h2 class="accordion-header" id="flush-headingOne">
			<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseOne" aria-expanded="true" aria-controls="flush-collapseOne">
				<p>Generated in <?php echo gen_number_format($time_diff, 3) ?> seconds (PHP: <?php echo gen_number_format(100 - $time_percent_db, 0) ?>%, DataBase: <?php echo gen_number_format($time_percent_db, 0) ?>%) with <?php echo gen_number_format($DBLayer->get_num_queries()) ?> queries.</p>
			</button>
		</h2>
		<div id="flush-collapseOne" class="accordion-collapse collapse" aria-labelledby="flush-headingOne" data-bs-parent="#accordionFlushExample">
			<div class="accordion-body">
				<div class="row">
					<div class="col-1 fw-bold">Time</div>
					<div class="col-auto fw-bold">Query</div>
				</div>
				<hr class="m-0">
<?php
	$query_time_total = 0.0;
	foreach ($saved_queries as $cur_query)
	{
		$query_time_total += $cur_query[1];
?>
				<div class="row">
					<div class="col-2"><?php echo (($cur_query[1] != 0) ? gen_number_format($cur_query[1], 5) : '&#160;') ?></div>
					<div class="col-10"><?php echo html_encode($cur_query[0]) ?></div>
				</div>
<?php
	}
?>
				<hr class="m-0">
				<div class="row">
					<div class="col-2"><?php echo gen_number_format($query_time_total, 5) ?></div>
					<div class="col-10">Total query time</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
}

// Verifies that the provided username is OK for insertion into the database
function validate_username($username, $exclude_id = null)
{
	global $lang_common, $lang_register, $lang_profile, $Config;

	$errors = array();

	// Convert multiple whitespace characters into one (to prevent people from registering with indistinguishable usernames)
	$username = preg_replace('#\s+#s', ' ', $username);

	// Validate username
	if (utf8_strlen($username) < 2)
		$errors[] = $lang_profile['Username too short'];
	else if (utf8_strlen($username) > 25)
		$errors[] = $lang_profile['Username too long'];
	else if (strtolower($username) == 'guest' || utf8_strtolower($username) == utf8_strtolower($lang_common['Guest']))
		$errors[] = $lang_profile['Username guest'];
	else if (preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', $username) || preg_match('/((([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}:[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){5}:([0-9A-Fa-f]{1,4}:)?[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){4}:([0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){3}:([0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){2}:([0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(([0-9A-Fa-f]{1,4}:){0,5}:((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(::([0-9A-Fa-f]{1,4}:){0,5}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|([0-9A-Fa-f]{1,4}::([0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})|(::([0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){1,7}:))/', $username))
		$errors[] = $lang_profile['Username IP'];
	else if ((strpos($username, '[') !== false || strpos($username, ']') !== false) && strpos($username, '\'') !== false && strpos($username, '"') !== false)
		$errors[] = $lang_profile['Username reserved chars'];
	else if (preg_match('/(?:\[\/?(?:b|u|i|h|colou?r|quote|code|img|url|email|list)\]|\[(?:code|quote|list)=)/i', $username))
		$errors[] = $lang_profile['Username BBCode'];

	// Check for username dupe
	$dupe = check_username_dupe($username, $exclude_id);
	if ($dupe !== false)
		$errors[] = sprintf($lang_profile['Username dupe'], html_encode($dupe));

	return $errors;
}

// Determines the correct title for $user
// $user must contain the elements 'username', 'title', 'posts', 'g_id' and 'g_user_title'
function get_title($user)
{
	global $DBLayer, $Config, $bans_info, $lang_common;
	static $ban_list;

	// If not already built in a previous call, build an array of lowercase banned usernames
	if (!isset($ban_list))
	{
		$ban_list = array();

		foreach ($bans_info as $cur_ban)
			$ban_list[] = utf8_strtolower($cur_ban['username']);
	}

	// If the user is banned
	if (in_array(utf8_strtolower($user['username']), $ban_list))
		$user_title = $lang_common['Banned'];
	// If the user group has a default user title
	else if ($user['g_user_title'] != '')
		$user_title = html_encode($user['g_user_title']);
	// If the user is a guest
	else if ($user['g_id'] == USER_GROUP_GUEST)
		$user_title = $lang_common['Guest'];
	else
	{
		// We assign the default
		if (!isset($user_title))
			$user_title = $lang_common['Member'];
	}

	return $user_title;
}

// Return a list of all styles installed
function get_style_packs()
{
	$styles = array();

	$d = dir(SITE_ROOT.'style');
	while (($entry = $d->read()) !== false)
	{
		if ($entry != '.' && $entry != '..' && is_dir(SITE_ROOT.'style/'.$entry) && file_exists(SITE_ROOT.'style/'.$entry.'/index.php'))
			$styles[] = $entry;
	}
	$d->close();

	return $styles;
}

// Return a list of all language packs installed
function get_language_packs()
{
	$languages = array();

	if ($handle = opendir(SITE_ROOT.'lang'))
	{
		while (false !== ($dirname = readdir($handle)))
		{
			$dirname = SITE_ROOT.'lang/'.$dirname;
			if (is_dir($dirname) && file_exists($dirname.'/common.php'))
				$languages[] = basename($dirname);
		}
		closedir($handle);
	}

	return $languages;
}

// Try to determine the correct remote IP-address
function get_remote_address()
{
	return $_SERVER['REMOTE_ADDR'];
}

// Replace to next one
function get_current_url($max_length = 0, $params = '')
{
	$protocol = (empty($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) == 'off') ? 'http://' : 'https://';
	$port = (isset($_SERVER['SERVER_PORT']) && (($_SERVER['SERVER_PORT'] != '80' && $protocol == 'http://') || ($_SERVER['SERVER_PORT'] != '443' && $protocol == 'https://')) && strpos($_SERVER['HTTP_HOST'], ':') === false) ? ':'.$_SERVER['SERVER_PORT'] : '';

	$url = $protocol.$_SERVER['HTTP_HOST'].$port.$_SERVER['REQUEST_URI'];
	
	if ($params != '' && (strpos($url, $params) === false))
		$url .= ((strpos($url, '?') === false) ? '?' : '&').$params;
	
	if (strlen($url) <= $max_length || $max_length == 0)
		return $url;

	// We can't find a short enough url
	return null;
}

// Try to determine the current URL. $params - add parametrs
function get_cur_url($params = '', $max_length = 0)
{
	$protocol = (empty($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) == 'off') ? 'http://' : 'https://';
	$port = (isset($_SERVER['SERVER_PORT']) && (($_SERVER['SERVER_PORT'] != '80' && $protocol == 'http://') || ($_SERVER['SERVER_PORT'] != '443' && $protocol == 'https://')) && strpos($_SERVER['HTTP_HOST'], ':') === false) ? ':'.$_SERVER['SERVER_PORT'] : '';

	$url = $protocol.$_SERVER['HTTP_HOST'].$port.$_SERVER['REQUEST_URI'];

	if ($params != '' && (strpos($url, $params) === false))
		$url .= ((strpos($url, '?') === false) ? '?' : '&').$params;
	
	if (strlen($url) <= $max_length || $max_length == 0)
		return $url;

	// We can't find a short enough url
	return null;
}

// Generate a random key of length $len
function random_key($len, $readable = false, $hash = false)
{
	$key = '';

	if ($hash)
		$key = substr(sha1(uniqid(rand(), true)), 0, $len);
	else if ($readable)
	{
		$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

		for ($i = 0; $i < $len; ++$i)
			$key .= substr($chars, (mt_rand() % strlen($chars)), 1);
	}
	else
		for ($i = 0; $i < $len; ++$i)
			$key .= chr(mt_rand(33, 126));

	return $key;
}

// Generates a valid CSRF token for use when submitting a form to $target_url
// $target_url should be an absolute URL and it should be exactly the URL that the user is going to
// Alternately, if the form token is going to be used in GET (which would mean the token is going to be
// a part of the URL itself), $target_url may be a plain string containing information related to the URL.
function generate_form_token($target_url = '')
{
	global $User;
	
	$target_url = ($target_url != '') ? $target_url : get_current_url();

	return sha1(str_replace('&amp;', '&', $target_url).$User->get('csrf_token'));
}

// Generates a salted, SHA-1 hash of $str
function generate_hash($str, $salt)
{
	return sha1($salt.sha1($str));
}

// Check whether the connecting user is banned (and delete any expired bans while we're at it)
function check_bans()
{
	global $DBLayer, $User, $Config, $Cachinger, $lang_common, $bans_info;

	// Admins aren't affected
	if (defined('USER_GROUP_ADMIN') && $User->is_admin() || !$bans_info)
		return;

	// Add a dot or a colon (depending on IPv4/IPv6) at the end of the IP address to prevent banned address
	// 192.168.0.5 from matching e.g. 192.168.0.50
	$user_ip = get_remote_address();
	$user_ip .= (strpos($user_ip, '.') !== false) ? '.' : ':';

	$bans_altered = false;
	$is_banned = false;

	foreach ($bans_info as $cur_ban)
	{
		// Has this ban expired?
		if ($cur_ban['expire'] != '' && $cur_ban['expire'] <= time())
		{
			$query = array(
				'DELETE'	=> 'bans',
				'WHERE'		=> 'id='.$cur_ban['id']
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);

			$bans_altered = true;
			continue;
		}

		if ($cur_ban['username'] != '' && utf8_strtolower($User->get('username')) == utf8_strtolower($cur_ban['username']))
			$is_banned = true;

		if ($cur_ban['email'] != '' && $User->get('email') == $cur_ban['email'])
			$is_banned = true;

		if ($cur_ban['ip'] != '')
		{
			$cur_ban_ips = explode(' ', $cur_ban['ip']);

			$num_ips = count($cur_ban_ips);
			for ($i = 0; $i < $num_ips; ++$i)
			{
				// Add the proper ending to the ban
				if (strpos($user_ip, '.') !== false)
					$cur_ban_ips[$i] = $cur_ban_ips[$i].'.';
				else
					$cur_ban_ips[$i] = $cur_ban_ips[$i].':';

				if (substr($user_ip, 0, strlen($cur_ban_ips[$i])) == $cur_ban_ips[$i])
				{
					$is_banned = true;
					break;
				}
			}
		}

		if ($is_banned)
		{
			$query = array(
				'DELETE'	=> 'online',
				'WHERE'		=> 'ident=\''.$DBLayer->escape($User->get('username')).'\''
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);

			message($lang_common['Ban message'].(($cur_ban['expire'] != '') ? ' '.sprintf($lang_common['Ban message 2'], format_time($cur_ban['expire'], 1, null, null, true)) : '').(($cur_ban['message'] != '') ? ' '.$lang_common['Ban message 3'].'</p><p><strong>'.html_encode($cur_ban['message']).'</strong></p>' : '</p>').'<p>'.sprintf($lang_common['Ban message 4'], '<a href="mailto:'.html_encode($Config->get('o_admin_email')).'">'.html_encode($Config->get('o_admin_email')).'</a>'));
		}
	}

	// If we removed any expired bans during our run-through, we need to regenerate the bans cache
	if ($bans_altered)
	{
		$Cachinger->gen_bans();
	}
}

// Adds a new user. The username must be passed through validate_username() first.
function add_user($user_info, &$new_uid)
{
	global $DBLayer, $User, $URL, $lang_common, $Config;

/*
	// Must the user verify the registration?
	if ($Config->get('o_regs_verify') == '1')
	{
		// Load the "welcome" template
		$mail_tpl = swift_trim(file_get_contents(SITE_ROOT.'lang/'.$User->get('language').'/mail_templates/welcome.tpl'));

		// The first row contains the subject
		$first_crlf = strpos($mail_tpl, "\n");
		$mail_subject = swift_trim(substr($mail_tpl, 8, $first_crlf-8));
		$mail_message = swift_trim(substr($mail_tpl, $first_crlf));

		$mail_subject = str_replace('<board_title>', $Config->get('o_board_title'), $mail_subject);
		$mail_message = str_replace('<base_url>', $Config->get('o_board_title'), $mail_message);
		$mail_message = str_replace('<username>', $user_info['username'], $mail_message);
		$mail_message = str_replace('<activation_url>', str_replace('&amp;', '&', $URL->link('change_password_key', array($new_uid, substr($user_info['activate_key'], 1, -1)))), $mail_message);
		$mail_message = str_replace('<board_mailer>', sprintf($lang_common['Forum mailer'], $Config->get('o_board_title')), $mail_message);

		$SwiftMailer = new SwiftMailer;
		$SwiftMailer->send($user_info['email'], $mail_subject, $mail_message);
	}
*/

}

// Delete a user and all information associated with it
function delete_user($user_id, $delete_posts = false)
{
	global $DBLayer, $db_type, $Config, $Cachinger;

	// First we need to get some data on the user
	$query = array(
		'SELECT'	=> 'u.username, u.group_id, g.g_moderator',
		'FROM'		=> 'users AS u',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'groups AS g',
				'ON'			=> 'g.g_id=u.group_id'
			)
		),
		'WHERE'		=> 'u.id='.$user_id
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$user = $DBLayer->fetch_assoc($result);

	// Remove him/her from the online list (if they happen to be logged in)
	$query = array(
		'DELETE'	=> 'online',
		'WHERE'		=> 'user_id='.$user_id
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	// Delete the user
	$query = array(
		'DELETE'	=> 'users',
		'WHERE'		=> 'id='.$user_id
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	// Delete user avatar
	delete_avatar($user_id);

	// If the user is a moderator or an administrator, we remove him/her from the moderator list in all forums
	// and regenerate the bans cache (in case he/she created any bans)
	if ($user['group_id'] == USER_GROUP_ADMIN || $user['g_moderator'] == '1')
	{
		// Regenerate the bans cache
		$Cachinger->gen_bans();
	}
}

// Check if a username is occupied
function check_username_dupe($username, $exclude_id = null)
{
	global $DBLayer;

	$query = array(
		'SELECT'	=> 'u.username',
		'FROM'		=> 'users AS u',
		'WHERE'		=> '(UPPER(username)=UPPER(\''.$DBLayer->escape($username).'\') OR UPPER(username)=UPPER(\''.$DBLayer->escape(preg_replace('/[^\w]/u', '', $username)).'\')) AND id>1'
	);

	if ($exclude_id)
		$query['WHERE'] .= ' AND id!='.$exclude_id;
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$dupe_name = $DBLayer->result($result);

	return (is_null($dupe_name) || $dupe_name === false) ? false : $dupe_name;
}

// Deletes any avatars owned by the specified user ID
function delete_avatar($user_id)
{
	global $DBLayer, $Config, $db_type;

	$filetypes = array('jpg', 'gif', 'png');

	// Delete user avatar from FS
	foreach ($filetypes as $cur_type)
	{
		$avatar = SITE_ROOT.$Config->get('o_avatars_dir').'/'.$user_id.'.'.$cur_type;
		if (file_exists($avatar))
		{
			@unlink($avatar);
		}
	}

	// Delete user avatar from DB
	$query = array(
		'UPDATE'	=> 'users',
		'SET'		=> 'avatar=\''.USER_AVATAR_NONE.'\', avatar_height=\'0\', avatar_width=\'0\'',
		'WHERE'		=> 'id='.$user_id
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);
}

// Display a form that the user can use to confirm that they want to undertake an action.
// Used when the CSRF token from the request does not match the token stored in the database.
function csrf_confirm_form()
{
	global $Core, $DBLayer, $URL, $PagesNavigator, $FlashMessenger, $Templator, $Loader, $User, $Config, $Hooks, $SwiftMenu, $page_param;
	global $lang_common, $microtime_start, $tpl_main;

	// If we've disabled the CSRF check for this page, we have nothing to do here.
	if (defined('SPM_DISABLE_CSRF_CONFIRM'))
		return;

	// User pressed the cancel button
	if (isset($_POST['confirm_cancel']))
		redirect(html_encode($_POST['prev_url']), $lang_common['Cancel redirect']);

	// A helper function for csrf_confirm_form. It takes a multi-dimensional array and returns it as a
	// single-dimensional array suitable for use in hidden fields.
	function _csrf_confirm_form($key, $values)
	{
		$fields = array();

		if (is_array($values))
		{
			foreach ($values as $cur_key => $cur_values)
				$fields = array_merge($fields, _csrf_confirm_form($key.'['.$cur_key.']', $cur_values));

			return $fields;
		}
		else
			$fields[$key] = $values;

		return $fields;
	}

	if (defined('SPM_REQUEST_AJAX'))
	{
		$json_data = array(
				'code'			=>	-3,
				'message'		=>	$lang_common['CSRF token mismatch'],
				'csrf_token'	=>	generate_form_token(get_current_url()),
				'prev_url'		=>	html_encode($User->get('prev_url')),
		);

		foreach ($_POST as $submitted_key => $submitted_val)
		{
			if ($submitted_key != 'csrf_token' && $submitted_key != 'prev_url')
			{
				$hidden_fields = _csrf_confirm_form($submitted_key, $submitted_val);
				foreach ($hidden_fields as $field_key => $field_val)
				{
					$json_data['post_data'][$field_key] = html_encode($field_val);
				}
			}
		}

		send_json($json_data);
	}

	$page_param['form_action'] = get_current_url();

	$page_param['hidden_fields'] = array(
		'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token($page_param['form_action']).'" />',
		'prev_url'		=> '<input type="hidden" name="prev_url" value="'.html_encode($User->get('prev_url')).'" />'
	);

	foreach ($_POST as $submitted_key => $submitted_val)
		if ($submitted_key != 'csrf_token' && $submitted_key != 'prev_url')
		{
			$hidden_fields = _csrf_confirm_form($submitted_key, $submitted_val);
			foreach ($hidden_fields as $field_key => $field_val)
				$page_param['hidden_fields'][$field_key] = '<input type="hidden" name="'.html_encode($field_key).'" value="'.html_encode($field_val).'" />';
		}

	define('PAGE_ID', 'dialogue');
	require SITE_ROOT.'header.php';

	// START SUBST - <!--page_content-->
	ob_start();

?>
<div class="main">
	<div class="main-subhead">
		<h6 class="hn"><span><?php echo $lang_common['Confirm action head'] ?></span></h6>
	</div>
	<div class="main-content main-frm">
		<div class="ct-box info-box">
			<p><?php echo $lang_common['CSRF token mismatch'] ?></p>
		</div>
		<form method="post" accept-charset="utf-8" action="<?php echo html_encode($page_param['form_action']) ?>">
			<div class="hidden">
				<?php echo implode("\n\t\t\t\t", $page_param['hidden_fields'])."\n" ?>
			</div>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" value="<?php echo $lang_common['Confirm'] ?>" /></span>
				<span class="cancel"><input type="submit" name="confirm_cancel" value="<?php echo $lang_common['Cancel'] ?>" /></span>
			</div>
		</form>
	</div>
</div>
<?php

	$tpl_temp = swift_trim(ob_get_contents());
	$tpl_main = str_replace('<!--page_content-->', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <!--page_content-->

	require SITE_ROOT.'footer.php';
}

// Display a message
function message($message, $link = '', $heading = '')
{
	global $User, $Hooks, $Core, $URL, $Templator, $PagesNavigator, $Loader, $DBLayer, $Config, $FlashMessenger, $SwiftMenu, $page_param, $lang_common, $microtime_start, $tpl_main;

	if (defined('SPM_REQUEST_AJAX'))
	{
		$json_data = array(
			'code'		=> -1,
			'message'	=> $message
		);
		send_json($json_data);
	}

	if (!defined('SPM_HEADER'))
	{
		define('PAGE_ID', 'message');
		require SITE_ROOT.'header.php';
	}

	$content = [];
	if ($message != '')
		$content[] = '<p>'.$message.'</p>';

	if ($User->is_guest() && $link == '')
		$content[] = '<p>If you are not logged in, <a class="badge bg-primary text-white" href="'.BASE_URL.'/login.php">LOG IN</a> with your username and password.</p>';

	//if (!empty($page_param['main_head_options']))
	//	echo "\n\t\t".'<p class="options">'.implode(' ', $page_param['main_head_options']).'</p>';

?>

	<div class="card">
		<div class="card-body">
			<div class="callout callout-danger mb-2">
				<h6 class="text-danger">Warning!</h6>
				<?php echo implode("\n", $content)  ?>
			</div>
		</div>
	</div>

<?php

	$Hooks->get_hook('IncludeFunctionsMessageOutputEnd');

	require SITE_ROOT.'footer.php';
}

// Display a message when board is in maintenance mode
function maintenance_message()
{
	global $Core, $URL, $Templator, $PagesNavigator, $Loader, $DBLayer, $Config, $User, $FlashMessenger, $SwiftMenu, $page_param;
	global $lang_common, $microtime_start, $tpl_main;

	// Deal with newlines, tabs and multiple spaces
	$pattern = array("\t\t", '  ', '  ');
	$replace = array('&#160; &#160; ', '&#160; ', ' &#160;');
	$message = str_replace($pattern, $replace, $Config->get('o_maintenance_message'));

	// Send the Content-type header in case the web server is setup to send something else
	header('Content-type: text/html; charset=utf-8');

	// Send a 503 HTTP response code to prevent search bots from indexing the maintenace message
	header('HTTP/1.1 503 Service Temporarily Unavailable');

	// Load the maintenance template
	if (file_exists(SITE_ROOT.'style/'.$User->get('style').'/maintenance.tpl'))
		$tpl_path = SITE_ROOT.'style/'.$User->get('style').'/maintenance.tpl';
	else
		$tpl_path = SITE_ROOT.'include/template/maintenance.tpl';

	$tpl_maint = swift_trim(file_get_contents($tpl_path));

	// START SUBST - <!-- forum_local -->
	$tpl_maint = str_replace('<!-- forum_local -->', 'xml:lang="'.$lang_common['lang_identifier'].'" lang="'.$lang_common['lang_identifier'].'" dir="'.$lang_common['lang_direction'].'"', $tpl_maint);
	// END SUBST - <!-- forum_local -->

	// START SUBST - <!--head_elements-->
	ob_start();

	require SITE_ROOT.'style/'.$User->get('style').'/index.php';
	echo $Loader->render_css();

	$tpl_temp = swift_trim(ob_get_contents());
	$tpl_maint = str_replace('<!--head_elements-->', $tpl_temp, $tpl_maint);
	ob_end_clean();
	// END SUBST - <!--head_elements-->


	// START SUBST - <!-- forum_maint_main -->
	ob_start();

?>
	<div class="main-subhead">
		<h1 class="hn"><span><?php echo $lang_common['Maintenance mode'] ?></span></h1>
	</div>
	<div class="main-content main-message">
		<div class="ct-box user-box">
			<?php echo $message."\n" ?>
		</div>
	</div>
<?php

	$tpl_temp = "\t".swift_trim(ob_get_contents());
	$tpl_maint = str_replace('<!-- forum_maint_main -->', $tpl_temp, $tpl_maint);
	ob_end_clean();
	// END SUBST - <!-- forum_maint_main -->

	// End the transaction
	$DBLayer->end_transaction();

	// Close the db connection (and free up any result data)
	$DBLayer->close();

	exit($tpl_maint);
}

// Display $message and redirect user to $destination_url
function redirect($destination_url = '', $message = '')
{
	global $Hooks, $FlashMessenger;

	http_response_code(301);

	$Hooks->get_hook('fn_redirect_start');

	if ($destination_url == '')
		$destination_url = get_current_url();

	// Prefix with base_url (unless it's there already)
	if (strpos($destination_url, 'http://') !== 0 && strpos($destination_url, 'https://') !== 0 && strpos($destination_url, '/') !== 0)
		$destination_url = BASE_URL.'/'.$destination_url;

	// Do a little spring cleaning
	$destination_url = preg_replace('/([\r\n])|(%0[ad])|(;[\s]*data[\s]*:)/i', '', $destination_url);

	if (defined('SPM_REQUEST_AJAX'))
	{
		$json_data = array(
			'code'		=> -2,
			'message'	=> $message,
			'destination_url' => $destination_url
		);
		send_json($json_data);
	}

	if (!$FlashMessenger->get_message()) {
		$FlashMessenger->add_info($message);
	}

	$Hooks->get_hook('fn_redirect_pre_redirect');

	header('Location: '.str_replace('&amp;', '&', $destination_url));

	exit();
}

// Display a simple error message
function error()
{
	global $Config, $URL;

	if (!headers_sent())
	{
		// if no HTTP responce code is set we send 503
		if (!defined('SPM_HTTP_RESPONSE_CODE_SET'))
		{
			http_response_code(503);
			header('HTTP/1.1 503 Service Temporarily Unavailable');
		}
		
		header('Content-type: text/html; charset=utf-8');
	}

	$num_args = func_num_args();
	if ($num_args == 3){
		$message = func_get_arg(0);
		$file = func_get_arg(1);
		$line = func_get_arg(2);
	}
	else if ($num_args == 2){
		$file = func_get_arg(0);
		$line = func_get_arg(1);
	}
	else if ($num_args == 1)
		$message = func_get_arg(0);

	// Set a default error messages string if the script failed before $common_lang loaded
	$lang_common = [];
	$lang_common['Forum error header'] = 'Sorry! The page could not be loaded.';
	$lang_common['Forum error description'] = 'This is probably a temporary error. Just refresh the page and retry. If problem continues, please check back in 5-10 minutes.';
	$lang_common['error_location'] = 'The error occurred on line %1$s in %2$s';
	$lang_common['error_db_reported'] = 'Database reported:';
	$lang_common['error_db_query'] = 'Failed query:';
	
	// Empty all output buffers and stop buffering
	if (ob_get_contents()){
		while (@ob_end_clean());
	}
	
	// "Restart" output buffering if we are using ob_gzhandler (since the gzip header is already sent)
//	if (!empty($Config->get('o_gzip')) && extension_loaded('zlib') && !empty($_SERVER['HTTP_ACCEPT_ENCODING']) && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false || strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate') !== false))
//		ob_start('ob_gzhandler');

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="viewport" content="width=device-width, user-scalable=no">
	<title>Error</title>
	<style>
body{padding: 0;margin: 0;font-family: system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans","Liberation Sans",sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji";}
p {padding:2px;margin:0;}
.navbar{position: fixed;top:0;width: -webkit-fill-available;background-color: #212529;margin-bottom: 15px;display: flex;justify-content: space-between;}
.navbar ul {list-style: none;}
.nav-left{display: flex;flex-direction: column;padding-left: 20px;}
.nav-right{float: right;padding-right: 20px;}
.navbar ul li a{color: white;font-weight: bold;text-decoration: none;}
.alert{border-radius: 0.25rem;color: #842029;background-color: #f8d7da;border-color: #f5c2c7;padding: 7px 12px;margin: 65px 10px;}
.title{font-weight:bold;color: #6a1a21;}
.content{font-size: 14px;}
.error_line{}
	</style>
</head>
<body>
<?php
	$error_content = $email_content = [];
	if (isset($message)){
		$error_content[] = '<p>'.$message.'</p>';
		$email_content[] = $message;
	}else{
		$error_content[] = '<p><strong>Please report this issue to website administrator.</strong></p>';
	}
	if ($num_args > 1){
		if (defined('SPM_DEBUG')){
			$db_error = isset($GLOBALS['DBLayer']) ? $GLOBALS['DBLayer']->error() : array();
			if (!empty($db_error['error_msg']))
			{
				$error_content[] = '<p><strong>'.html_encode($lang_common['error_db_reported']).'</strong> '.html_encode($db_error['error_msg']).(($db_error['error_no']) ? ' (Errno: '.$db_error['error_no'].')' : '').'.</p>';
				$email_content[] = html_encode($lang_common['error_db_reported']);
				$email_content[] = html_encode($db_error['error_msg']).(($db_error['error_no']) ? ' (Errno: '.$db_error['error_no'].')' : '');

				if ($db_error['error_sql'] != ''){
					$error_content[] = '<p><strong>'.html_encode($lang_common['error_db_query']).'</strong> <code>'.html_encode($db_error['error_sql']).'</code></p>';
					$email_content[] = html_encode($lang_common['error_db_query']);
					$email_content[] = html_encode($db_error['error_sql']);
				}
			}
			if (isset($file) && isset($line)){
				$file = str_replace(realpath(SITE_ROOT), '', $file);
				$error_content[] = '<p class="error_line">'.html_encode(sprintf($lang_common['error_location'], $line, $file)).'</p>';
				$email_content[] = html_encode(sprintf($lang_common['error_location'], $line, $file));
			}
		}
	}
	$email_output = implode("%0D%0A", $email_content);
	$email_output = strip_tags($email_output, '<br />');
	$email_output = strip_tags($email_output, '<em>');
?>
	<div class="navbar">
		<ul class="nav-left">
			<li><a href="<?=BASE_URL?>">Home Page</a></li>
		</ul>
		<ul class="nav-right">
			<li><a href="mailto:dmitry@hcares.com?subject=Website Error&amp;body=Hello, I got this error.%0D%0A%0D%0A<?=$email_output;?>">Report this issue</a></li>
		</ul>
	</div>
	<div class="alert">
		<p class="title">Sorry! The page could not be loaded.</p>
		<p class="content"><?php echo implode("\n", $error_content) ?></p>
	</div>
</body>
</html>
<?php

	// If a database connection was established (before this error) we close it
	if (isset($GLOBALS['forum_db']))
		$GLOBALS['forum_db']->close();

	exit;
}

// Display Database error message
function db_error($file, $line)
{
	global $Config, $URL;

	if (!headers_sent())
	{
		// if no HTTP responce code is set we send 503
		if (!defined('SPM_HTTP_RESPONSE_CODE_SET'))
		{
			http_response_code(503);
			header('HTTP/1.1 503 Service Temporarily Unavailable');
		}
		
		header('Content-type: text/html; charset=utf-8');
	}

	// Set a default error messages string if the script failed before $common_lang loaded
	$lang_common = [];
	$lang_common['Forum error header'] = 'Sorry! The page could not be loaded.';
	$lang_common['Forum error description'] = 'This is probably a temporary error. Just refresh the page and retry. If problem continues, please check back in 5-10 minutes.';
	$lang_common['error_location'] = 'The error occurred on line %1$s in %2$s';
	$lang_common['error_db_reported'] = 'Database reported:';
	$lang_common['error_db_query'] = 'Failed query:';
	
	// Empty all output buffers and stop buffering
	if (ob_get_contents()){
		while (@ob_end_clean());
	}
	
	// "Restart" output buffering if we are using ob_gzhandler (since the gzip header is already sent)
//	if (!empty($Config->get('o_gzip')) && extension_loaded('zlib') && !empty($_SERVER['HTTP_ACCEPT_ENCODING']) && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false || strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate') !== false))
//		ob_start('ob_gzhandler');

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="viewport" content="width=device-width, user-scalable=no">
	<title>Error</title>
	<style>
body{padding: 0;margin: 0;font-family: system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans","Liberation Sans",sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji";}
p {padding:2px;margin:0;}
.navbar{position: fixed;top:0;width: -webkit-fill-available;background-color: #212529;margin-bottom: 15px;display: flex;justify-content: space-between;}
.navbar ul {list-style: none;}
.nav-left{display: flex;flex-direction: column;padding-left: 20px;}
.nav-right{float: right;padding-right: 20px;}
.navbar ul li a{color: white;font-weight: bold;text-decoration: none;}
.alert{border-radius: 0.25rem;color: #842029;background-color: #f8d7da;border-color: #f5c2c7;padding: 7px 12px;margin: 65px 10px;}
.title{font-weight:bold;color: #6a1a21;}
.content{font-size: 14px;}
	</style>
</head>
<body>
<?php

	$error_content = $email_content = [];
	$error_content[] = '<p><strong>Please report this issue to website administrator.</strong></p>';
	
	//if (defined('SPM_DEBUG'))
	//{
		$db_error = isset($GLOBALS['DBLayer']) ? $GLOBALS['DBLayer']->error() : array();
		if (!empty($db_error['error_msg']))
		{
			$error_content[] = '<p><strong>'.html_encode($lang_common['error_db_reported']).'</strong> '.html_encode($db_error['error_msg']).(($db_error['error_no']) ? ' (Errno: '.$db_error['error_no'].')' : '').'.</p>';
			$email_content[] = html_encode($lang_common['error_db_reported']);
			$email_content[] = html_encode($db_error['error_msg']).(($db_error['error_no']) ? ' (Errno: '.$db_error['error_no'].')' : '');
		}

		if ($db_error['error_sql'] != ''){
			$error_content[] = '<p><strong>'.html_encode($lang_common['error_db_query']).'</strong> <code>'.html_encode($db_error['error_sql']).'</code></p>';
			$email_content[] = html_encode($lang_common['error_db_query']);
			$email_content[] = html_encode($db_error['error_sql']);
		}

		if (isset($file) && isset($line)){
			$file = str_replace(realpath(SITE_ROOT), '', $file);
			$error_content[] = '<p class="error_line">'.html_encode(sprintf($lang_common['error_location'], $line, $file)).'</p>';
			$email_content[] = html_encode(sprintf($lang_common['error_location'], $line, $file));
		}
	//}

	$email_output = implode("%0D%0A", $email_content);
	$email_output = strip_tags($email_output, '<br />');
	$email_output = strip_tags($email_output, '<em>');
?>
	<div class="navbar">
		<ul class="nav-left">
			<li><a href="<?=BASE_URL?>">Home Page</a></li>
		</ul>
		<ul class="nav-right">
			<li><a href="mailto:dmitry@hcares.com?subject=Website Error&amp;body=Hello, I got this error.%0D%0A%0D%0A<?=$email_output;?>">Report this issue</a></li>
		</ul>
	</div>
	<div class="alert">
		<p class="title">Sorry! The page could not be loaded.</p>
		<p class="content"><?php echo implode("\n", $error_content) ?></p>
	</div>
</body>
</html>
<?php

	// If a database connection was established (before this error) we close it
	if (isset($GLOBALS['forum_db']))
		$GLOBALS['forum_db']->close();

	exit;
}

function send_json($params)
{
	header('Content-type: application/json; charset=utf-8');
	if (!function_exists('json_encode'))
	{
		function json_encode($data)
		{
			switch ($type = gettype($data))
			{
				case 'NULL':
					return 'null';
				case 'boolean':
					return ($data ? 'true' : 'false');
				case 'integer':
				case 'double':
				case 'float':
					return $data;
				case 'string':
					return '"' . addslashes($data) . '"';
				case 'object':
					$data = get_object_vars($data);
				case 'array':
					$output_index_count = 0;
					$output_indexed = array();
					$output_assoc = array();
					foreach ($data as $key => $value)
					{
						$output_indexed[] = json_encode($value);
						$output_assoc[] = json_encode($key) . ':' . json_encode($value);
						if ($output_index_count !== NULL && $output_index_count++ !== $key)
						{
							$output_index_count = NULL;
						}
					}
					if ($output_index_count !== NULL) {
						return '[' . implode(',', $output_indexed) . ']';
					} else {
						return '{' . implode(',', $output_assoc) . '}';
					}
				default:
					return ''; // Not supported
			}
		}
	}
	echo json_encode($params);
	die;
}

// Display array 
function print_dump($array)
{
	echo '<pre>';
	print_r($array);
	echo '</pre>';
}

