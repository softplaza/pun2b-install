<?php
/**
 * @copyright (C) 2020 SwiftProjectManager.Com, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftManager
 */

class User
{
	// Info of current user
	var $user_info = [];
	// DB permissions of current user
	var $user_perms = [];
	var $userlist_have_access = [];

	// Ident project for permission
	var $cur_project_id = '';
	// array[project_id][key] = title
	var $perms_info = [];
	var $apps_info = [];

	// APN keys
	var $access_keys = [];
	var $permission_keys = [];
	var $notification_keys = [];

	// APN created rules
	var $user_access = [];
	var $user_permissions = [];
	var $user_notifications = [];

	private $access_checked = false;

	function __construct()
	{
		global $user_info;

		$this->cookie_login($user_info);
	}

	// Authenticates the provided username and password against the user database
	// $user can be either a user ID (integer) or a username (string)
	// $password can be either a plaintext password or a password hash including salt ($password_is_hash must be set accordingly)
	function authenticate($user, $password, $password_is_hash = false)
	{
		global $DBLayer, $user_info;
	
		// Check if there's a user matching $user and $password
		$query = array(
			'SELECT'	=> 'u.*, g.*, o.logged, o.idle, o.csrf_token, o.prev_url',
			'FROM'		=> 'users AS u',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'groups AS g',
					'ON'			=> 'g.g_id=u.group_id'
				),
				array(
					'LEFT JOIN'		=> 'online AS o',
					'ON'			=> 'o.user_id=u.id'
				)
			)
		);
	
		// Are we looking for a user ID or a username?
		$query['WHERE'] = is_int($user) ? 'u.id='.intval($user) : 'u.username=\''.$DBLayer->escape($user).'\'';
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$user_info = $DBLayer->fetch_assoc($result);
	
		if (!isset($user_info['id']) ||
			($password_is_hash && $password != $user_info['password']) ||
			(!$password_is_hash && spm_hash($password, $user_info['salt']) != $user_info['password']))
			$this->set_default();
	}
	
	// Like other headers, cookies must be sent before any output from your script.
	// Use headers_sent() to ckeck wether HTTP headers has been sent already.
	function set_cookie($name, $value, $expire)
	{
		global $cookie_path, $cookie_domain, $cookie_secure;
	
		// Enable sending of a P3P header
		header('P3P: CP="CUR ADM"');
	
		if (version_compare(PHP_VERSION, '5.2.0', '>='))
			setcookie($name, $value, $expire, $cookie_path, $cookie_domain, $cookie_secure, true);
		else
			setcookie($name, $value, $expire, $cookie_path.'; HttpOnly', $cookie_domain, $cookie_secure);
	}
	
	// Attempt to login with the user ID and password hash from the cookie
	function cookie_login(&$user_info)
	{
		global $DBLayer, $Config, $db_type, $cookie_name, $cookie_path, $cookie_domain, $cookie_secure, $forum_time_formats, $forum_date_formats;
	
		$now = time();
		$expire = $now + 1209600;	// The cookie expires after 14 days
	
		// We assume it's a guest
		$cookie = array('user_id' => 1, 'password_hash' => 'Guest', 'expiration_time' => 0, 'expire_hash' => 'Guest');
	
		// If a cookie is set, we get the user_id and password hash from it
		if (!empty($_COOKIE[$cookie_name]))
		{
			$cookie_data = explode('|', base64_decode($_COOKIE[$cookie_name]));
	
			if (!empty($cookie_data) && count($cookie_data) == 4)
				list($cookie['user_id'], $cookie['password_hash'], $cookie['expiration_time'], $cookie['expire_hash']) = $cookie_data;
		}
	
		// If this a cookie for a logged in user and it shouldn't have already expired
		if (intval($cookie['user_id']) > 1 && intval($cookie['expiration_time']) > $now)
		{
			$this->authenticate(intval($cookie['user_id']), $cookie['password_hash'], true);
			
			// We now validate the cookie hash
			if ($cookie['expire_hash'] !== sha1($user_info['salt'].$user_info['password'].spm_hash(intval($cookie['expiration_time']), $user_info['salt'])))
				$this->set_default();
	
			// If we got back the default user, the login failed
			if ($user_info['id'] == '1')
			{
				$this->set_cookie($cookie_name, base64_encode('1|'.random_key(8, false, true).'|'.$expire.'|'.random_key(8, false, true)), $expire);
				return;
			}
	
			// Send a new, updated cookie with a new expiration timestamp
			$expire = (intval($cookie['expiration_time']) > $now + $Config->get('o_timeout_visit')) ? $now + 1209600 : $now + $Config->get('o_timeout_visit');
			$this->set_cookie($cookie_name, base64_encode($user_info['id'].'|'.$user_info['password'].'|'.$expire.'|'.sha1($user_info['salt'].$user_info['password'].spm_hash($expire, $user_info['salt']))), $expire);
	
			// Set a default language if the user selected language no longer exists
			if (!file_exists(SITE_ROOT.'lang/'.$user_info['language'].'/common.php'))
				$user_info['language'] = $Config->get('o_default_lang');
	
			// Set a default style if the user selected style no longer exists
			if (!file_exists(SITE_ROOT.'style/'.$user_info['style'].'/index.php'))
				$user_info['style'] = $Config->get('o_default_style');
	
			// Check user has a valid date and time format
			if (!isset($forum_time_formats[$user_info['time_format']]))
				$user_info['time_format'] = 0;
			if (!isset($forum_date_formats[$user_info['date_format']]))
				$user_info['date_format'] = 0;
	
			// Define this if you want this visit to affect the online list and the users last visit data
			if (!defined('SPM_QUIET_VISIT'))
			{
				// Update the online list
				if (!$user_info['logged'])
				{
					$user_info['logged'] = $now;
					$user_info['csrf_token'] = random_key(40, false, true);
					$user_info['prev_url'] = get_current_url(255);
	
					// REPLACE INTO avoids a user having two rows in the online table
					$query = array(
						'REPLACE'	=> 'user_id, ident, logged, csrf_token',
						'INTO'		=> 'online',
						'VALUES'	=> $user_info['id'].', \''.$DBLayer->escape($user_info['username']).'\', '.$user_info['logged'].', \''.$user_info['csrf_token'].'\'',
						'UNIQUE'	=> 'user_id='.$user_info['id']
					);
	
					if ($user_info['prev_url'] !== null)
					{
						$query['REPLACE'] .= ', prev_url';
						$query['VALUES'] .= ', \''.$DBLayer->escape($user_info['prev_url']).'\'';
					}
					$DBLayer->query_build($query) or error(__FILE__, __LINE__);
				}
				else
				{
					// Special case: We've timed out, but no other user has browsed the forums since we timed out
					if ($user_info['logged'] < ($now-$Config->get('o_timeout_visit')))
					{
						$query = array(
							'UPDATE'	=> 'users',
							'SET'		=> 'last_visit='.$user_info['logged'],
							'WHERE'		=> 'id='.$user_info['id']
						);
						$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	
						$user_info['last_visit'] = $user_info['logged'];
					}
	
					// Now update the logged time and save the current URL in the online list
					$query = array(
						'UPDATE'	=> 'online',
						'SET'		=> 'logged='.$now,
						'WHERE'		=> 'user_id='.$user_info['id']
					);
	
					$current_url = get_current_url(255);
					if ($current_url !== null && !defined('SPM_REQUEST_AJAX'))
						$query['SET'] .= ', prev_url=\''.$DBLayer->escape($current_url).'\'';
	
					if ($user_info['idle'] == '1')
						$query['SET'] .= ', idle=0';
					$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	
					// Update tracked topics with the current expire time
					if (isset($_COOKIE[$cookie_name.'_track']))
						$this->set_cookie($cookie_name.'_track', $_COOKIE[$cookie_name.'_track'], $now + $Config->get('o_timeout_visit'));
				}
			}
	
			$user_info['is_guest'] = false;
			$user_info['is_admmod'] = $user_info['g_id'] == USER_GROUP_ADMIN || $user_info['g_moderator'] == '1';
		}
		else
			$this->set_default();
			
		$this->set($user_info);
	}
	
	// Fill $user_info with default values (for guests)
	function set_default()
	{
		global $DBLayer, $Config, $db_type, $user_info;
	
		$remote_addr = get_remote_address();
	
		// Fetch guest user
		$query = array(
			'SELECT'	=> 'u.*, g.*, o.logged, o.csrf_token, o.prev_url, o.last_post, o.last_search',
			'FROM'		=> 'users AS u',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'groups AS g',
					'ON'			=> 'g.g_id=u.group_id'
				),
				array(
					'LEFT JOIN'		=> 'online AS o',
					'ON'			=> 'o.ident=\''.$DBLayer->escape($remote_addr).'\''
				)
			),
			'WHERE'		=> 'u.id=1'
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$user_info = $DBLayer->fetch_assoc($result);
	
		if (!$user_info)
			exit('Unable to fetch guest information. The table \''.$DBLayer->prefix.'users\' must contain an entry with id = 1 that represents anonymous users.');
	
		if (!defined('SPM_QUIET_VISIT'))
		{
			// Update online list
			if (!$user_info['logged'])
			{
				$user_info['logged'] = time();
				$user_info['csrf_token'] = random_key(40, false, true);
				$user_info['prev_url'] = get_current_url(255);
	
				// REPLACE INTO avoids a user having two rows in the online table
				$query = array(
					'REPLACE'	=> 'user_id, ident, logged, csrf_token',
					'INTO'		=> 'online',
					'VALUES'	=> '1, \''.$DBLayer->escape($remote_addr).'\', '.$user_info['logged'].', \''.$user_info['csrf_token'].'\'',
					'UNIQUE'	=> 'user_id=1 AND ident=\''.$DBLayer->escape($remote_addr).'\''
				);
	
				if ($user_info['prev_url'] !== null)
				{
					$query['REPLACE'] .= ', prev_url';
					$query['VALUES'] .= ', \''.$DBLayer->escape($user_info['prev_url']).'\'';
				}
				$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			}
			else
			{
				$query = array(
					'UPDATE'	=> 'online',
					'SET'		=> 'logged='.time(),
					'WHERE'		=> 'ident=\''.$DBLayer->escape($remote_addr).'\''
				);
	
				$current_url = get_current_url(255);
				if ($current_url !== null)
					$query['SET'] .= ', prev_url=\''.$DBLayer->escape($current_url).'\'';
				$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			}
		}
	
		$user_info['timezone'] = $Config->get('o_default_timezone');
		$user_info['dst'] = $Config->get('o_default_dst');
		$user_info['language'] = $Config->get('o_default_lang');
		$user_info['style'] = $Config->get('o_default_style');
		$user_info['is_guest'] = true;
		$user_info['is_admmod'] = false;
		
		$this->set($user_info);
	}
	
	// Check whether the connecting user is banned (and delete any expired bans while we're at it)
	function check_bans()
	{
		global $DBLayer, $Config, $Cachinger, $lang_common, $bans_info;
	
		// Admins aren't affected
		if (defined('USER_GROUP_ADMIN') && $this->is_admin() || !$bans_info)
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
	
			if ($cur_ban['username'] != '' && utf8_strtolower($this->get('username')) == utf8_strtolower($cur_ban['username']))
				$is_banned = true;
	
			if ($cur_ban['email'] != '' && $this->get('email') == $cur_ban['email'])
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
					'WHERE'		=> 'ident=\''.$DBLayer->escape($this->get('username')).'\''
				);
				$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	
				message($lang_common['Ban message'].(($cur_ban['expire'] != '') ? ' '.sprintf($lang_common['Ban message 2'], format_time($cur_ban['expire'], 1, null, null, true)) : '').(($cur_ban['message'] != '') ? ' '.$lang_common['Ban message 3'].'</p><p><strong>'.html_encode($cur_ban['message']).'</strong></p>' : '</p>').'<p>'.sprintf($lang_common['Ban message 4'], '<a href="mailto:'.html_encode($Config->get('o_admin_email')).'">'.html_encode($Config->get('o_admin_email')).'</a>'));
			}
		}
	
		// If we removed any expired bans during our run-through, we need to regenerate the bans cache
		if ($bans_altered)
		{
			$Cachinger->gen_bans();;
		}
	}
	
	// Update "Users online"
	function update_online()
	{
		global $DBLayer, $Config, $user_info;
	
		$now = time();
		
		// Fetch all online list entries that are older than "o_timeout_online"
		$query = array(
			'SELECT'	=> 'o.*',
			'FROM'		=> 'online AS o',
			'WHERE'		=> 'o.logged < '.($now - $Config->get('o_timeout_online'))
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	
		$need_delete_expired_guest = false;
		$expired_users_id = $idle_users_id = array();
		while ($cur_user = $DBLayer->fetch_assoc($result))
		{
			if ($cur_user['user_id'] != '1')
			{
				// If the entry is older than "o_timeout_visit", update last_visit for the user in question, then delete him/her from the online list
				if ($cur_user['logged'] < ($now - $Config->get('o_timeout_visit')))
				{
					$query = array(
						'UPDATE'	=> 'users',
						'SET'		=> 'last_visit='.$cur_user['logged'],
						'WHERE'		=> 'id='.$cur_user['user_id']
					);
					$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	
					// Add to expired list
					$expired_users_id[] = $cur_user['user_id'];
				}
				else
				{
					// Add to idle list
					if ($cur_user['idle'] == '0')
					{
						$idle_users_id[] = $cur_user['user_id'];
					}
				}
			}
			else
			{
				// We have expired guest — delete it later
				$need_delete_expired_guest = true;
			}
		}
	
		// Remove all guest that are older than "o_timeout_online"
		if ($need_delete_expired_guest)
		{
			$query = array(
				'DELETE'	=> 'online',
				'WHERE'		=> 'user_id=1 AND logged < '.($now - $Config->get('o_timeout_online'))
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
	
	
		// Delete expired users
		if (!empty($expired_users_id))
		{
			$query = array(
				'DELETE'	=> 'online',
				'WHERE'		=> 'user_id IN ('.implode(',', $expired_users_id).')'
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
	
		// Update idle users
		if (!empty($idle_users_id))
		{
			$query = array(
				'UPDATE'	=> 'online',
				'SET'		=> 'idle=1',
				'WHERE'		=> 'user_id IN ('.implode(',', $idle_users_id).')'
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
	}

	// Add user info as key => val
	function set($user_info)
	{
		if (!empty($user_info))
		{
			foreach($user_info as $key => $val)
				$this->user_info[$key] = $val;
		}
	}
	
	// Get user info by key
	function get($key)
	{
		if (isset($this->user_info[$key]))
			return $this->user_info[$key];
	}	
	
	// Check if current user is ADMIN
	function is_admin()
	{
		return ($this->user_info['g_id'] == USER_GROUP_ADMIN) ? true : false;
	}

	// Check if current user is GUEST
	function is_guest()
	{
		return ($this->user_info['g_id'] == USER_GROUP_GUEST) ? true : false;
	}
	
	// Check if current user is ASSISTANT
	function is_admmod()
	{
		return $this->user_info['is_admmod'];
	}

	// Check if logged
	function logged()
	{
		return isset($this->user_info['logged']) ? $this->user_info['logged'] : 0;
	}

	// Check project permissions
	function check_perms($perm_for, $perm_key = 0)
	{
		global $DBLayer;

		if ($this->is_admin())
			return true;

		$output = false;

		if (empty($this->user_perms))
		{
			$query = array(
				'SELECT'	=> 'p.id, p.group_id, p.user_id, p.perm_for, p.perm_key, p.perm_value',
				'FROM'		=> 'permissions AS p',
				'WHERE'		=> 'p.user_id='.$this->get('id').' OR p.group_id='.$this->get('group_id')
			);
			$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
			while ($row = $DBLayer->fetch_assoc($result))
			{
				$this->user_perms[] = $row;
			}
		}

		if (!empty($this->user_perms))
		{
			foreach($this->user_perms as $cur_perm)
			{
				if ($cur_perm['perm_for'] == $perm_for && $cur_perm['perm_key'] == $perm_key && $cur_perm['perm_value'] == 1)
				{
					$output = true;
					break;
				}
				else if ($cur_perm['perm_for'] == $perm_for && $perm_key == 0)
				{
					$output = true;
					break;
				}
			}
		}

		return $output;
	}

	// Set before add any permissions
	function setPermProjId($id, $title){
		$this->cur_project_id = $id;
		$this->apps_info[$id] = $title;
	}
	// Add permission by key and title
	function addPerm($key, $title){
		if ($this->cur_project_id != '' && $title != '')
		{
			$this->perms_info[$this->cur_project_id][$key] = $title;
		}
	}
	// Clear project id after add permission
	function unsetPermProjId(){
		$this->cur_project_id = '';
	}
	// Get permission by project id and value
	function getPerm($project_id, $key){
		if (isset($this->perms_info[$project_id][$key]))
			return $this->perms_info[$project_id][$key];
	}
	// Get permission by project id and value
	function getPermList($perm_for = '')
	{
		$output = [];
		if (!empty($this->perms_info))
		{
			foreach($this->perms_info as $cur_project => $cur_perm){
				if ($perm_for != '')
				{
					// Get a project permissions
					foreach($cur_perm as $key => $title){
						if ($perm_for == $cur_project)
							$output[$key] = $title;
					}
				}
				// Get all project permissions
				else
					$output[$cur_project] = $cur_perm;
			}
		}
		return $output;
	}

	function checkAccess($to, $key = 0)
	{
		global $DBLayer;

		if ($this->is_guest())
			return false;
		else if ($this->is_admin())
			return true;

		$output = false;
		if (!$this->access_checked)
		{
			$query = [
				'SELECT'	=> 'a.id, a.a_gid, a.a_uid, a.a_to, a.a_key, a.a_value',
				'FROM'		=> 'user_access AS a',
				'WHERE'		=> 'a.a_uid='.$this->get('id').' OR a.a_gid='.$this->get('group_id')
			];
			$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
			while ($row = $DBLayer->fetch_assoc($result))
			{
				$this->user_access[] = $row;
			}

			$this->access_checked = true;
		}

		if (!empty($this->user_access))
		{
			foreach($this->user_access as $cur_info)
			{
				if ($cur_info['a_to'] == $to && $cur_info['a_key'] == $key && $cur_info['a_value'] == 1)
				{
					$output = true;
					break;
				}
				else if ($cur_info['a_to'] == $to && $key == 0)
				{
					$output = true;
					break;
				}
			}
		}

		return $output;
	}

	function checkPermissions($to, $key = 0)
	{
		global $DBLayer;

		if ($this->is_guest())
			return false;
		else if ($this->is_admin())
			return true;

		$output = false;
		if (empty($this->user_permissions))
		{
			// get all permissions of current user og group
			$query = [
				'SELECT'	=> 'p.id, p.p_gid, p.p_uid, p.p_to, p.p_key, p.p_value',
				'FROM'		=> 'user_permissions AS p',
				'WHERE'		=> 'p.p_uid='.$this->get('id').' OR p.p_gid='.$this->get('group_id')
			];
			$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
			while ($row = $DBLayer->fetch_assoc($result))
			{
				$this->user_permissions[] = $row;
			}
		}

		if (!empty($this->user_permissions))
		{
			foreach($this->user_permissions as $cur_info)
			{
				if ($cur_info['p_to'] == $to && $cur_info['p_key'] == $key && $cur_info['p_value'] == 1)
				{
					$output = true;
					break;
				}
				else if ($cur_info['p_to'] == $to && $key == 0)
				{
					$output = true;
					break;
				}
			}
		}

		return $output;
	}

	function checkNotification($to, $key, $uid)
	{
		global $DBLayer;

		$query = [
			'SELECT'	=> 'n.id, n.n_gid, n.n_uid, n.n_to, n.n_key, n.n_value, u.realname, u.email',
			'FROM'		=> 'user_notifications AS n',
			'JOINS'		=> [
				[
					'INNER JOIN'	=> 'users AS u',
					'ON'			=> 'u.id=n.n_uid'
				]
			],
			'WHERE'		=> 'n.n_to=\''.$DBLayer->escape($to).'\' AND n.n_key='.$key.' AND n.n_uid='.$uid
		];
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		return $DBLayer->fetch_assoc($result);
	}

	// types: 1 - UID - Check by User ID only one person
	// or 2 - GID ???
	function checkNotifications($to, $key = 0, $uid = 0, $type = 1)
	{
		global $DBLayer;

		if (empty($this->user_notifications) && !$this->is_guest())
		{
			$query = [
				'SELECT'	=> 'n.id, n.n_gid, n.n_uid, n.n_to, n.n_key, n.n_value, u.realname, u.email',
				'FROM'		=> 'user_notifications AS n',
				'JOINS'		=> [
					[
						'INNER JOIN'	=> 'users AS u',
						'ON'			=> 'u.id=n.n_uid'
					]
				],
				'WHERE'		=> 'n.n_to=\''.$DBLayer->escape($to).'\' AND n.n_key='.$key
			];

			if ($uid > 0 && $type == 1)
				$query['WHERE'] .= ' AND n.n_uid='.$uid;
			//else if ($type == 2)
			//	$query['WHERE'] .= ' AND n.n_gid='.$uid;

			$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
			while ($row = $DBLayer->fetch_assoc($result))
			{
				if ($row['n_key'] > 0 && $row['n_value'] == 1)
					$this->user_notifications[] = $row;
			}
		}

		return $this->user_notifications;
	}

	function getUserAccess($project_id = '', $key = 0, $value = 0)
	{
		global $DBLayer;

		$where = [];
		if ($project_id != '')
			$where[] = 'a.a_to=\''.$DBLayer->escape($project_id).'\'';
		if ($key > 0)
			$where[] = 'a.a_key='.$key;
		if ($value > 0)
			$where[] = 'a.a_value=1';

		$query = [
			'SELECT'	=> 'u.id, u.group_id, u.realname, u.email',
			'FROM'		=> 'users AS u',
			'JOINS'		=> [
				[
					'INNER JOIN'	=> 'user_access AS a',
					'ON'			=> 'u.id=a.a_uid'
				],
			],
			'ORDER BY'	=> 'u.realname',
		];

		if (!empty($where))
			$query['WHERE'] = implode(' AND ', $where);

		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$users = [];
		while ($row = $DBLayer->fetch_assoc($result)) {
			$users[] = $row;
		}

		$this->userlist_have_access = $users;
		return $users;
	}

	function getUserAccessIDS()
	{
		$output = [];
		if (!empty($this->userlist_have_access)){
			foreach($this->userlist_have_access as $cur_info){
				$output[] = $cur_info['id'];
			}
		}
		return $output;
	}

	function getNotifyEmails($app_id, $key = 0)
	{
		global $DBLayer;

		$where = [];
		$where[] = 'n.n_to=\''.$DBLayer->escape($app_id).'\'';

		if ($key > 0)
			$where[] = 'n.n_key='.$key;

		$where[] = 'n.n_value=1';

		$query = [
			'SELECT'	=> 'n.*, u.email',
			'FROM'		=> 'user_notifications AS n',
			'JOINS'		=> [
				[
					'INNER JOIN'	=> 'users AS u',
					'ON'			=> 'u.id=n.n_uid'
				],
			],
		];

		if (!empty($where))
			$query['WHERE'] = implode(' AND ', $where);

		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$emails = [];
		while ($row = $DBLayer->fetch_assoc($result))
		{
			$emails[$row['n_uid']] = $row['email'];
		}

		return $emails;
	}

	// get list of notified users and groups
	function getNotifyInfo($app_id, $key = 0)
	{
		global $DBLayer;

		$where = [];
		$where[] = 'n.n_to=\''.$DBLayer->escape($app_id).'\'';

		if ($key > 0)
			$where[] = 'n.n_key='.$key;

		$where[] = 'n.n_value=1';

		$query = [
			'SELECT'	=> 'u.id, u.realname, u.email',//
			'FROM'		=> 'users AS u',
			'JOINS'		=> [
				[
					'LEFT JOIN'		=> 'user_notifications AS n',
					'ON'			=> '(n.n_uid=u.id OR n.n_gid=u.group_id)'
				],
			],
			'ORDER BY'	=> 'u.realname',
		];

		if (!empty($where))
			$query['WHERE'] = implode(' AND ', $where);

		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$emails = [];
		while ($row = $DBLayer->fetch_assoc($result))
		{
			$emails[$row['id']] = $row;
		}

		return $emails;
	}
	
}
