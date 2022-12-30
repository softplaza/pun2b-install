<?php

/**
 * Localisation
 * @author SwiftProjectManager.Com
 * @copyright (C) 2021 SwiftManager license GPL
 * @package Lang
**/

class Lang
{
	// Default language of user
	public $user_lang = 'English';
	
	// List of core languages
	public $admin_bans = [];
	public $admin_common = [];
	public $admin_ext = [];
	public $admin_groups = [];
	public $admin_index = [];
	public $admin_settings = [];
	public $lang_common = [];
	public $help = [];
	public $index = [];
	public $install = [];
	public $lang_login = [];
	public $profile = [];
	public $userlist = [];
	
	// Set current extension ID
	public $cur_ext_id = '';
	public $cur_ext_lang = [];
	
	// Set user language
	function set($val)
	{
		$this->user_lang = $val;
	}	
	
	// Get common values
	function common($key)
	{
		if (empty($this->lang_common))
		{
			if (file_exists(SITE_ROOT.'lang/'.$this->user_lang.'/common.php'))
				include SITE_ROOT.'lang/'.$this->user_lang.'/common.php';
			else
				include SITE_ROOT.'lang/English/common.php';
			
			$this->lang_common = $lang_common2;
		}
		
		if (isset($this->lang_common[$key]))
			return $this->lang_common[$key];
	}
	
	function login($key)
	{
		if (empty($this->lang_login))
		{
			if (file_exists(SITE_ROOT.'lang/'.$this->user_lang.'/login.php'))
				include SITE_ROOT.'lang/'.$this->user_lang.'/login.php';
			else
				include SITE_ROOT.'lang/English/login.php';
			
			$this->lang_login = $lang_login;
		}
		
		if (isset($this->lang_login[$key]))
			return $this->lang_login[$key];
	}
	
	// Get extension lang values
	function ext($key, $ext_id = '')
	{
		// Get previews dir except $ext_id
		
		if ($ext_id != '')
			$this->cur_ext_id = $ext_id;
		
		if (empty($this->cur_ext_lang))
		{
			if (file_exists(SITE_ROOT.'extansions/'.$this->cur_ext_id.'/lang/'.$this->user_lang.'.php'))
				include SITE_ROOT.'extansions/'.$this->cur_ext_id.'/lang/'.$this->user_lang.'.php';
			
			$this->cur_ext_lang = $cur_ext_lang;
		}
		
		if (isset($this->cur_ext_lang[$key]))
			return $this->cur_ext_lang[$key];
	}
	
	
	
}
