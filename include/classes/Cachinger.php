<?php
/**
 * @copyright (C) 2020 SwiftManager.Org, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Cachinger
**/

class Cachinger
{
	function __construct()
	{
		// If the cache directory is not specified, we use the default setting
		if (!defined('SPM_CACHE_DIR'))
			define('SPM_CACHE_DIR', SITE_ROOT.'cache/');
	}

	// Safe create or write of cache files
	// Use LOCK
	function write_file($file, $content)
	{
		$file_path = SPM_CACHE_DIR.$file;
		
		// Open
		$handle = (file_exists($file_path)) ? @fopen($file, 'r+b') : false; // @ - file may not exist
		if (!$handle)
		{
			$handle = fopen($file, 'wb');
			if (!$handle)
			{
				return false;
			}
		}

		// Lock
		flock($handle, LOCK_EX);
		ftruncate($handle, 0);

		// Write
		if (fwrite($handle, $content) === false)
		{
			// Unlock and close
			flock($handle, LOCK_UN);
			fclose($handle);

			return false;
		}

		// Unlock and close
		flock($handle, LOCK_UN);
		fclose($handle);

		// Force opcache to recompile this script
		if (function_exists('opcache_invalidate')) {
			opcache_invalidate($file, true);
		}

		return true;
	}

	// Generate the config cache PHP script
	function gen_config()
	{
		global $DBLayer;

		// Get the forum config from the DB
		$query = array(
			'SELECT'	=> 'c.*',
			'FROM'		=> 'config AS c'
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);

		$output = array();
		while ($cur_config_item = $DBLayer->fetch_assoc($result))
			$output[$cur_config_item['conf_name']] = $cur_config_item['conf_value'];

		// Output config as PHP code
		if (!$this->write_file(SPM_CACHE_DIR.'config_info.php', '<?php'."\n\n".'define(\'CACHE_CONFIG_INFO_LOADED\', 1);'."\n\n".'$config_info = '.var_export($output, true).';'."\n\n".'?>'))
		{
			error('Unable to write configuration cache file to cache directory.<br />Please make sure PHP has write access to the directory \'cache\'.', __FILE__, __LINE__);
		}
	}

	// Generate the hooks cache of all installed Apps
	function gen_apps()
	{
		global $DBLayer;

		$this->clear('apps_info.php');

		$query = array(
			'SELECT'	=> 'a.*',
			'FROM'		=> 'applications AS a'
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$output = [];
		while ($row = $DBLayer->fetch_assoc($result))
			$output[$row['id']] = [
				'id'			=> $row['id'],
				'title'			=> $row['title'],
				'disabled'		=> $row['disabled'],
				'url'			=> BASE_URL.'/apps/'.$row['id'].'/',
				'path'			=> SITE_ROOT.'apps/'.$row['id'].'/'
			];

		// Output hooks as PHP code
		if (!$this->write_file(SPM_CACHE_DIR.'apps_info.php', '<?php'."\n\n".'define(\'CACHE_APPS_INFO_LOADED\', 1);'."\n\n".'$apps_info = '.var_export($output, true).';'."\n\n".'?>'))
		{
			error('Unable to write hooks cache file to cache directory.<br />Please make sure PHP has write access to the directory \'cache\'.', __FILE__, __LINE__);
		}
	}

	// Generate the bans cache PHP script
	function gen_bans()
	{
		global $DBLayer;

		// Get the ban list from the DB
		$query = array(
			'SELECT'	=> 'b.*, u.username AS ban_creator_username',
			'FROM'		=> 'bans AS b',
			'JOINS'		=> array(
				array(
					'LEFT JOIN'		=> 'users AS u',
					'ON'			=> 'u.id=b.ban_creator'
				)
			),
			'ORDER BY'	=> 'b.id'
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);

		$output = array();
		while ($cur_ban = $DBLayer->fetch_assoc($result))
			$output[] = $cur_ban;

		// Output ban list as PHP code
		if (!$this->write_file(SPM_CACHE_DIR.'bans_info.php', '<?php'."\n\n".'define(\'CACHE_BANS_INFO_LOADED\', 1);'."\n\n".'$bans_info = '.var_export($output, true).';'."\n\n".'?>'))
		{
			error('Unable to write bans cache file to cache directory.<br />Please make sure PHP has write access to the directory \'cache\'.', __FILE__, __LINE__);
		}
	}

	// Generate the ranks cache PHP script
	function gen_ranks()
	{
		global $DBLayer;

		// Get the rank list from the DB
		$query = array(
			'SELECT'	=> 'r.*',
			'FROM'		=> 'ranks AS r',
			'ORDER BY'	=> 'r.min_posts'
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);

		$output = array();
		while ($cur_rank = $DBLayer->fetch_assoc($result))
			$output[] = $cur_rank;

		// Output ranks list as PHP code
		if (!$this->write_file(SPM_CACHE_DIR.'ranks_info.php', '<?php'."\n\n".'define(\'CACHE_RANKS_INFO_LOADED\', 1);'."\n\n".'$ranks_info = '.var_export($output, true).';'."\n\n".'?>'))
		{
			error('Unable to write ranks cache file to cache directory.<br />Please make sure PHP has write access to the directory \'cache\'.', __FILE__, __LINE__);
		}
	}

	// Generate the hooks cache PHP script
	function gen_hooks()
	{
		global $DBLayer, $Config;

		// Get the hooks from the DB
		$query = array(
			'SELECT'	=> 'eh.id, eh.code, eh.extension_id, e.dependencies',
			'FROM'		=> 'extension_hooks AS eh',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'extensions AS e',
					'ON'			=> 'e.id=eh.extension_id'
				)
			),
			'WHERE'		=> 'e.disabled=0',
			'ORDER BY'	=> 'eh.priority, eh.installed'
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);

		$output = array();
		while ($cur_hook = $DBLayer->fetch_assoc($result))
		{
			$load_ext_info = '$GLOBALS[\'ext_info_stack\'][] = array('."\n".
				'\'id\'				=> \''.$cur_hook['extension_id'].'\','."\n".
				'\'path\'			=> SITE_ROOT.\'extensions/'.$cur_hook['extension_id'].'\','."\n".
				'\'url\'			=> BASE_URL.\'/extensions/'.$cur_hook['extension_id'].'\','."\n".
				'\'dependencies\'	=> array ('."\n";

			$dependencies = explode('|', substr($cur_hook['dependencies'], 1, -1));
			foreach ($dependencies as $cur_dependency)
			{
				// This happens if there are no dependencies because explode ends up returning an array with one empty element
				if (empty($cur_dependency))
					continue;

				$load_ext_info .= '\''.$cur_dependency.'\'	=> array('."\n".
					'\'id\'				=> \''.$cur_dependency.'\','."\n".
					'\'path\'			=> SITE_ROOT.\'extensions/'.$cur_dependency.'\','."\n".
					'\'url\'			=> BASE_URL.\'/extensions/'.$cur_dependency.'\'),'."\n";
			}

			$load_ext_info .= ')'."\n".');'."\n".'$ext_info = $GLOBALS[\'ext_info_stack\'][count($GLOBALS[\'ext_info_stack\']) - 1];';
			$unload_ext_info = 'array_pop($GLOBALS[\'ext_info_stack\']);'."\n".'$ext_info = empty($GLOBALS[\'ext_info_stack\']) ? array() : $GLOBALS[\'ext_info_stack\'][count($GLOBALS[\'ext_info_stack\']) - 1];';

			$output[$cur_hook['id']][] = $load_ext_info."\n\n".$cur_hook['code']."\n\n".$unload_ext_info."\n";
		}

		// Output hooks as PHP code
		if (!$this->write_file(SPM_CACHE_DIR.'cache_hooks.php', '<?php'."\n\n".'define(\'SPM_HOOKS_LOADED\', 1);'."\n\n".'$forum_hooks = '.var_export($output, true).';'."\n\n".'?>'))
		{
			error('Unable to write hooks cache file to cache directory.<br />Please make sure PHP has write access to the directory \'cache\'.', __FILE__, __LINE__);
		}
	}

	function generate_ext_versions_cache($inst_exts, $repository_urls, $repository_url_by_extension)
	{
		$ext_last_versions = array();
		$repository_extensions = array();
	
		foreach (array_unique(array_merge($repository_urls, $repository_url_by_extension)) as $url)
		{
			// Get repository timestamp
			$remote_file = get_remote_file($url.'/timestamp', 2);
			$repository_timestamp = empty($remote_file['content']) ? '' : swift_trim($remote_file['content']);
			unset($remote_file);
			if (!is_numeric($repository_timestamp))
				continue;
	
			if (!isset($repository_extensions[$url]['timestamp']))
				$repository_extensions[$url]['timestamp'] = $repository_timestamp;
	
			if ($repository_extensions[$url]['timestamp'] <= $repository_timestamp)
			{
				foreach ($inst_exts as $ext)
				{
					
					if ((0 === strpos($ext['id'], 'pun_') AND SEARCH_MAX_WORD != $url) OR
							((FALSE === strpos($ext['id'], 'pun_') AND !isset($ext['repo_url'])) OR (isset($ext['repo_url']) AND $ext['repo_url'] != $url)))
						continue;
					
					$remote_file = get_remote_file($url.'/'.$ext['id'].'/lastversion', 2);
					$version = empty($remote_file['content']) ? '' : swift_trim($remote_file['content']);
					unset($remote_file);
					if (empty($version) || !preg_match('~^[0-9a-zA-Z\. +-]+$~u', $version))
						continue;
	
					$repository_extensions[$url]['extension_versions'][$ext['id']] = $version;
	
					// If key with current extension exist in array, compare it with version in repository
					if (!isset($ext_last_versions[$ext['id']]) || (version_compare($ext_last_versions[$ext['id']]['version'], $version, '<')))
					{
						$ext_last_versions[$ext['id']] = array('version' => $version, 'repo_url' => $url);
	
						$remote_file = get_remote_file($url.'/'.$ext['id'].'/lastchanges', 2);
						$last_changes = empty($remote_file['content']) ? '' : swift_trim($remote_file['content']);
						unset($remote_file);
						if (!empty($last_changes))
							$ext_last_versions[$ext['id']]['changes'] = $last_changes;
					}
				}
	
				// Write timestamp to cache
				$repository_extensions[$url]['timestamp'] = $repository_timestamp;
			}
		}
	
		if (array_keys($ext_last_versions) != array_keys($inst_exts))
			foreach ($inst_exts as $ext)
				if (!in_array($ext['id'], array_keys($ext_last_versions)))
					$ext_last_versions[$ext['id']] = array('version' => $ext['version'], 'repo_url' => '', 'changes' => '');

		// Output config as PHP code
		if (!$this->write_file(SPM_CACHE_DIR.'cache_ext_version_notifications.php', '<?php'."\n\n".'if (!defined(\'SPM_EXT_VERSIONS_LOADED\')) define(\'SPM_EXT_VERSIONS_LOADED\', 1);'."\n\n".'$repository_extensions = '.var_export($repository_extensions, true).';'."\n\n".' $ext_last_versions = '.var_export($ext_last_versions, true).";\n\n".'$ext_versions_update_cache = '.time().";\n\n".'?>'))
		{
			error('Unable to write configuration cache file to cache directory.<br />Please make sure PHP has write access to the directory \'cache\'.', __FILE__, __LINE__);
		}
	}

	// Delete every .php file in the cache directory
	function clear($file = '')
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
}
