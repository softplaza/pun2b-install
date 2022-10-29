<?php

// Wrapper for PHP's mail()
function send_email($to, $subject, $message, $reply_to_email = '', $reply_to_name = '', $type = 'text/plain', $attached_file = ''){}

// This function was originally a part of the phpBB Group forum software phpBB2 (http://www.phpbb.com).
// They deserve all the credit for writing it. I made small modifications for it to suit PunBB and it's coding standards.
function smtp_mail($to, $subject, $message, $headers = ''){}

// Trim whitespace including non-breaking space
function spm_trim($str, $charlist = " \t\n\r\0\x0B\xC2\xA0")
{
	return utf8_trim($str, $charlist);
}


function sm_html_parser($str)
{
	$str = preg_replace ("/^(.+)\n/m", "<p>$1</p>\n", $str);
	$str = preg_replace('@((https?://)?([-\w]+\.[-\w\.]+)+\w(:\d+)?(/([-\w/_\.]*(\?\S+)?)?)*)@', '<a href="$1">$1</a>', $str);

	return $str;
}

function sm_is_today($date)
{
	$is_today = false;
	$time_now = time();
	$next_day = $date + 86400;
	
	if ($date != 0 && $date != '')
	{
		if ($time_now > $date && $time_now < $next_day)
			$is_today = true;
	}
	
	return $is_today;
}

function gen_link($key, $args = null)
{
	global $URL;
	$URL->link($key, $args = null);
}

function forum_microtime()
{
	return microtime(true);
}

// Encodes the contents of $str so that they are safe to output on an (X)HTML page
function forum_htmlencode($str)
{
	return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Add config value to config table
// Warning!
// This function dont refresh config cache - use "$Cachinger->clear()" if
// call this function outside install/uninstall extension manifest section
function forum_config_add($name, $value)
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
function forum_config_remove($name)
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

// Trim whitespace including non-breaking space
function forum_trim($str, $charlist = " \t\n\r\0\x0B\xC2\xA0")
{
	return utf8_trim($str, $charlist);
}

// A wrapper for PHP's number_format function
function forum_number_format($number, $decimals = 0)
{
	global $lang_common;
	
	$number = is_numeric($number) ? $number : 0;
	
	return number_format($number, $decimals, $lang_common['lang_decimal_point'], $lang_common['lang_thousands_sep']);
}

// Generate a hyperlink with parameters and anchor
function forum_link($link, $args = null)
{
	global $Config;

	$gen_link = $link;
	if ($args === null)
		$gen_link = BASE_URL.'/'.$link;
	else if (!is_array($args))
		$gen_link = BASE_URL.'/'.str_replace('$1', $args, $link);
	else
	{
		for ($i = 0; isset($args[$i]); ++$i)
			$gen_link = str_replace('$'.($i + 1), $args[$i], $gen_link);
		$gen_link = BASE_URL.'/'.$gen_link;
	}

	return $gen_link;
}

// Generates a salted, SHA-1 hash of $str
function forum_hash($str, $salt)
{
	return sha1($salt.sha1($str));
}

// Delete every .php file in the forum's cache directory
function forum_clear_cache($file = '')
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

