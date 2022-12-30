<?php
/**
 * @copyright (C) 2020 SwiftProjectManager.Com
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Hooks
 */

class Hooks
{
	private $apps_path = SITE_ROOT.'apps';
	var $apps_info = [];
	private $hooks_used = [];
	private $hooks_unused = [];
	private $hooks_all = [];

	function __construct()
	{
		global $Core, $Cachinger;

		// Load hooks if cache file exists
		if (file_exists(SPM_CACHE_DIR.'apps_info.php'))
			include SPM_CACHE_DIR.'apps_info.php';

		if (!defined('CACHE_APPS_INFO_LOADED'))
		{
			$Cachinger->gen_apps();
			require SPM_CACHE_DIR.'apps_info.php';
		}
		
		if (!empty($apps_info))
		{
			foreach($apps_info as $app_id => $app_info)
			{
				if ($app_info['disabled'] == '0')
				{
					require $this->apps_path.'/'.$app_id.'/inc/hooks.php';
					$this->apps_info[$app_id] = $app_info;
				}
			}
		}

		if (!isset($apps_info))
			$Core->add_error('Cannot get apps info from cache. File: '.__FILE__.', line: '.__LINE__);
	}

	function get_hook($hook)
	{
		if (!empty($this->apps_info))
		{
			$output = [];
			foreach($this->apps_info as $app_id => $app_info)
			{
				$function = $app_id.'_'.$hook;

				$args = func_get_args();
				//foreach($func_args as $key => &$value)
				//	$args[$key] =& $value;

				if (function_exists($function)) {
					$this->hooks_used[] = $function;
					$output[] = call_user_func($function, $args);
					//$output[] = call_user_func_array($function, $args);
				}
				else
					$this->hooks_unused[] = $function;

				$this->hooks_all[] = $function;
			}

			return $output;
		}
	}

	function get_app($id) {
		return isset($this->apps_info[$id]) ? $this->apps_info[$id] : [];
	}

	function hooks_used() {
		return $this->hooks_used;
	}

	function hooks_all() {
		return $this->hooks_all;
	}

	//$Hooks->hooks_used();

}


/* USED RESERVED HOOK NAMES */
/*

class_email_fn_send_end
co_modify_url_scheme
hd_menu_elements
ft_end
fn_redirect_start
fn_send_email_end
li_login_pre_redirect


*/