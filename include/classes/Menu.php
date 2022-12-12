<?php

/**
 * @author SwiftManager.Org
 * @copyright (C) 2021 SwiftManager license GPL
 * @package Menu
**/

class Menu
{
	// Current page section
	public $page_section = '';
	// Current page id
	public $page_id = '';
	
	// Main menu items [] = ['page_section', 'title', 'link']
	public $top_main_items = [];
	
	// Main menu items [] = ['page_section', 'page_id', 'title', 'link']
	public $top_sub_items = [];
	
	// Main menu items [] = ['page_section', 'title']
	public $slide_main_items = [];
	
	// Content items [] = 
	public $content_items = [];
	
	public $cur_content_pages = [];
	
	// Main navigation menu
	public $nav_main_items = [];
	
	public $dropdown_main_items = [];
	
	// Sub navigation menu
	public $nav_sub_items = [];
	
	public $dropdown_sub_items = [];
	
	// Current page titles
	public $cur_project_title = '';
	public $cur_page_title = '';
	public $cur_sub_title = '';
	
	// types of nav main section
	public $management = ['apps', 'management', 'settings', 'users', 'sm_messenger', 'sm_vendors', 'sm_vendors', 'swift_uploader', 'sm_calendar'];
	public $projects = ['hca_5840', 'hca_fs', 'hca_trees', 'hca_turn_over', 'hca_vcr', 'hca_pc', 'hca_sp'];
	
	function __construct($page_section = '', $page_id = '')
	{
		$this->page_section = $page_section;
		$this->page_id = $page_id;

		$this->add_basic_top_main_items();
		$this->add_basic_top_sub_items();
	}

	// Add main menu item
	function add_top_main_item($page_section = '', $title = 'Menu', $link = '', $position = 10)
	{
		$this->top_main_items[] = [
			'page_section' => $page_section,
			'title' => $title,
			'link' => $link,
			'position' => $position
		];
	}
	
	// Add Basic Top Main items to array
	function add_basic_top_main_items()
	{
		global $User, $URL, $Config;
		
		if (!$User->is_guest())
		{
			$this->add_top_main_item('logout', 'Logout', $URL->link('logout', [$User->get('id'), generate_form_token('logout'.$User->get('id'))]), 0);
			
			if ($User->is_admin())
				$this->add_top_main_item('management', 'Administration', $URL->link('admin_index'), 1);
			
			//if ($User->is_admin())
			//	$this->add_top_main_item('apps', 'Applications', $URL->link('apps_management'), 2);

			if ($User->is_admin())
				$this->add_top_main_item('settings', 'Settings', $URL->link('admin_settings_setup'), 3);
			
			$this->add_top_main_item('profile', 'Profile', $URL->link('profile_about', $User->get('id')), 4);
			
			if ($User->is_admmod())
				$this->add_top_main_item('users', 'Users Management', $URL->link('admin_users'), 5);
		}
		else
		{
			if ($Config->get('o_regs_allow') == '1')
				$this->add_top_main_item('register', 'Register', $URL->link('register'), 1);
			
			$this->add_top_main_item('login', 'Login', $URL->link('login'), 2);
		}
		
		// Copy Main links to Slide Menu
		$this->slide_main_items = $this->top_main_items;
	}
	
	function gen_top_main_menu()
	{
		$output = [];
		$logout_confirm = 'onclick="return confirm(\'Are you sure you want to logout?\')"';
		$top_main_items = array_msort($this->top_main_items, array('position' => SORT_ASC));
		
		foreach($top_main_items as $item)
		{
			if ($item['page_section'] == 'logout')
				$output[] = '<li class="'.(($this->page_section == $item['page_section']) ? 'active' : 'normal').'"><a href="'.$item['link'].'" '.$logout_confirm.'>'.$item['title'].'</a></li>';
			else
				$output[] = '<li class="'.(($this->page_section == $item['page_section']) ? 'active' : 'normal').'"><a href="'.$item['link'].'">'.$item['title'].'</a></li>';
		}
		
		return '<div class="main-menu gen-content">'."\n\t".'<ul>'."\n\t\t".implode("\n\t\t", $output)."\n\t".'</ul>'."\n".'</div>';
	}
	
	// Add main menu item
	function add_top_sub_item($page_section = '', $page_id = '', $title = 'Menu', $link = '')
	{
		global $URL;

		$this->top_sub_items[] = [
			'page_section' => $page_section,
			'page_id' => $page_id,
			'title' => $title,
			'link' => ($link != '') ? $link : $URL->link($page_id),
			'pages' => []
		];
	}
	
	// Add Basic Top Main items to array
	function add_basic_top_sub_items()
	{
		global $User, $URL, $page_param, $id;
		
		$user_id = (isset($id) && $this->page_section == 'profile') ? $id : $User->get('id');
		$page_param['own_profile'] = ($User->get('id') == $user_id) ? true : false;

		if ($User->is_admin())
		{
			$this->add_top_sub_item('management', 'admin_index', 'Information', $URL->link('admin_index'));
		
			$this->add_top_sub_item('management', 'apps_management', 'Applications', $URL->link('apps_management'));

			$this->add_top_sub_item('management', 'admin_extensions_manage', 'Extensions', $URL->link('admin_extensions_manage'));
		}
		
		if ($User->is_admin())
		{
			$this->add_top_sub_item('settings', 'admin_settings_setup', 'Setup', $URL->link('admin_settings_setup'));
			
			$this->add_top_sub_item('settings', 'admin_settings_features', 'Features', $URL->link('admin_settings_features'));
			
			$this->add_top_sub_item('settings', 'admin_settings_announcements', 'Announcements', $URL->link('admin_settings_announcements'));
			
			$this->add_top_sub_item('settings', 'admin_settings_maintenance', 'Maintenance mode', $URL->link('admin_settings_maintenance'));
			
			$this->add_top_sub_item('settings', 'admin_settings_email', 'E-mail', $URL->link('admin_settings_email'));
			
			$this->add_top_sub_item('settings', 'admin_settings_registration', 'Registration', $URL->link('admin_settings_registration'));
		}
		
		if ($User->is_admmod())
		{
			$this->add_top_sub_item('users', 'admin_users', 'Userlist', $URL->link('admin_users'));
			
			if ($User->is_admin())
			{
				$this->add_top_sub_item('users', 'admin_groups', 'Groups', $URL->link('admin_groups'));
			
				$this->add_top_sub_item('users', 'admin_permissions', 'Permissions', $URL->link('admin_permissions'));

				$this->add_top_sub_item('users', 'admin_departments', 'Departments', $URL->link('admin_departments'));
				$this->add_top_sub_item('users', 'admin_positions', 'Positions', $URL->link('admin_positions'));
			}
			if ($User->is_admin() || $User->get('g_mod_ban_users') == '1')
				$this->add_top_sub_item('users', 'admin_bans', 'Banned', $URL->link('admin_bans'));
			
			$this->add_top_sub_item('users', 'admin_new_user', 'New User', $URL->link('admin_new_user'));
		}
		
		if ($User->is_guest() && $User->get('g_view_users') == '1')
			$this->add_top_sub_item('profile', 'profile_about', 'About', $URL->link('profile_about', $user_id));
		else if (!$User->is_guest())
		{
			$this->add_top_sub_item('profile', 'profile_about', 'About', $URL->link('profile_about', $user_id));
			
			if ($User->is_admin() || $page_param['own_profile'] || $User->get('g_mod_edit_users') == '1')
				$this->add_top_sub_item('profile', 'profile_identity', 'Identity', $URL->link('profile_identity', $user_id));
			
			if ($User->is_admin() || $page_param['own_profile'])
				$this->add_top_sub_item('profile', 'profile_settings', 'Settings', $URL->link('profile_settings', $user_id));
			
			//if ($User->is_admin() || $page_param['own_profile'])
			//if ($User->is_admin())
			//	$this->add_top_sub_item('profile', 'profile_signature', 'Signature', $URL->link('profile_signature', $user_id));
			
			//if ($page_param['own_profile'] && $Config->get('o_avatars') == '1')
			//if ($User->is_admin())
			//	$this->add_top_sub_item('profile', 'profile_avatar', 'Photo', $URL->link('profile_avatar', $user_id));
		
			//if ($User->is_admin() || ($User->get('g_moderator') == '1' && $User->get('g_mod_ban_users') == '1' && !$page_param['own_profile']))
			if ($User->is_admin())
				$this->add_top_sub_item('profile', 'profile_admin', 'Administration', $URL->link('profile_admin', $user_id));
			
			if ($User->get('g_view_users') == '1')
				$this->add_top_sub_item('profile', 'userlist', 'Employees', $URL->link('userlist', $user_id));
		}
	}
	
	function gen_top_sub_menu()
	{
		$output = [];
		
		foreach($this->top_sub_items as $item)
		{
			if ($this->page_section == $item['page_section'])
				$output[] = '<li class="'.(($this->page_id == $item['page_id'] || in_array($this->page_id, $item['pages'])) ? 'active' : 'normal').'"><a href="'.$item['link'].'">'.$item['title'].'</a></li>';
		}

		return '<div class="main-submenu gen-content">'."\n\t".'<ul>'."\n\t\t".implode("\n\t\t", $output)."\n\t".'</ul>'."\n".'</div>';
	}
	
	// Add main menu item
	function add_slide_main_item($page_section = '', $title = 'Menu', $link = '', $position = 10)
	{
		$this->slide_main_items[] = [
			'page_section' => $page_section,
			'title' => $title,
			'link' => $link,
			'position' => $position
		];
	}
	
	// Generate Slide Menu
	function gen_slide_menu()
	{
		$output = [];
		$logout_confirm = 'onclick="return confirm(\'Are you sure you want to logout?\')"';
		
		$main_items = array_msort($this->slide_main_items, array('position' => SORT_ASC));
		
		foreach($main_items as $main_item)
		{
			if (in_array($main_item['page_section'], ['logout', 'login', 'register']))
				$output[] = '<li class="base-item '.(($this->page_section == $main_item['page_section']) ? 'active' : 'normal').'"><a href="'.$main_item['link'].'" '.($main_item['page_section'] == 'logout' ? $logout_confirm : '').'>'.$main_item['title'].'</a><ul>';
			else
				$output[] = '<li class="base-item '.(($this->page_section == $main_item['page_section']) ? 'active' : 'normal').'"><a href="#">'.$main_item['title'].'</a><ul>';
			
			foreach($this->top_sub_items as $sub_item)
			{	
				if ($main_item['page_section'] == $sub_item['page_section'])
					$output[] = '<li class="'.(($this->page_id == $sub_item['page_id']) ? 'active' : 'normal').'"><a href="'.$sub_item['link'].'">'.$sub_item['title'].'</a></li>';
			}
			
			$output[] = '</ul></li>';
		}
		
		return '<nav class="menu-bar">'."\n\t".'<div class="menu-btn"><span></span></div>'."\n\t".'<div class="menu-header">Menu</div>'."\n\t".'<ul>'."\n\t\t".implode("\n\t\t", $output)."\n\t".'</ul>'."\n\t".'</nav>';
	}
	
	// 
	function add_content_pages($pages)
	{
		$this->cur_content_pages = $pages;
		
		if (!empty($this->top_sub_items))
		{
			foreach($this->top_sub_items as $key => $item)
			{
				if (in_array($item['page_id'], $pages))
					$this->top_sub_items[$key]['pages'] = $pages;
			}
		}
	}
	
	// 
	function add_content_item($page_id = '', $title = 'Menu', $link = '')
	{
		global $URL;

		$this->content_items[] = [
			'page_id' => $page_id,
			'title' => $title,
			'link' => ($link != '') ? $link : $URL->link($page_id),
			'pages' => $this->cur_content_pages
		];
	}
	
	function gen_subhead_menu()
	{
		$output = [];
		
		foreach($this->content_items as $item)
		{
			if (in_array($this->page_id, $item['pages']))
				$output[] = '<li class="'.(($this->page_id == $item['page_id']) ? 'active' : 'normal').'"><a href="'.$item['link'].'">'.$item['title'].'</a></li>';
		}
		
		if (!empty($output))
			return '<div class="subhead-menu"><ul>'.implode("\n\t\t", $output)."\n\t".'</ul></div>';
	}
	
//--DROPDOWN MENU--//

	// Add main menu item
	function add_nav_main_item($nav_section = '', $title = 'Menu', $link = '', $position = 10)
	{
		$this->nav_main_items[] = [
			'nav_section' => $nav_section,
			'title' => $title,
			'link' => $link,
			'position' => $position
		];
	}
	
	// Add Basic Top Main items to array
	function add_basic_dropdown_main_items()
	{
		global $User, $Config, $URL;
		
		if (!$User->is_guest())
		{
			$this->add_nav_main_item('profile', 'Profile', '', 3);
			
			$this->add_nav_main_item('management', 'Management', '', 4);
			
			$this->add_nav_main_item('projects', 'Projects', '', 5);
		}
		else
		{
			if ($Config->get('o_regs_allow') == '1')
				$this->add_nav_main_item('register', 'Register', $URL->link('register'), 1);
			
			$this->add_nav_main_item('login', 'Login', $URL->link('login'), 2);
		}
	}
	
	// PROFILE - MANAGEMENT - PROJECTS
	function dropdown_main_menu()
	{
		global $User, $URL;
		
		$this->add_basic_dropdown_main_items();
		
		$output = [];
		
		// Generate Profile links
		$output[] = '<li class="'.(($this->page_section == 'profile') ? 'active' : 'normal').'"><button type="button" class="darkblue" onclick="dropDownNavMenu(\'profile\')">Profile<i class="fa fa-caret-down"></i></button></li>';
		$output[] = '<ul class="main-dropdown-list" id="dropdown_nav_menu_profile">';
		
		if (!$User->is_guest())
			$output[] = '<li class="item"><a href="'.$URL->link('logout', [$User->get('id'), generate_form_token('logout'.$User->get('id'))]).'" onclick="return confirm(\'Are you sure you want to logout?\')">Logout</a></li>';
		else
			$output[] = '<li class="item"><a href="'.$URL->link('login').'">Login</a></li>';
		
		foreach($this->top_sub_items as $item)
		{
			if ($item['page_section'] == 'profile')
				$output[] = '<li class="item '.(($this->page_id == $item['page_id']) ? 'active' : 'normal').'"><a href="'.$item['link'].'" id="dropdown_page_id_'.$item['page_id'].'">'.$item['title'].'</a></li>';
			
			if ($this->page_id == $item['page_id'])
				$this->cur_project_title = $item['title'];
		}
		$output[] = '</ul>';
		
		// Generate Management links
		$management_items = [];
		foreach($this->slide_main_items as $item)
		{
			if (in_array($item['page_section'], $this->management))
				$management_items[] = '<li class="item '.(($this->page_section == $item['page_section']) ? 'active' : 'normal').'"><a href="'.$item['link'].'" id="dropdown_page_section_'.$item['page_section'].'">'.$item['title'].'</a></li>';
			
			if ($this->page_section == $item['page_section'])
				$this->cur_project_title = $item['title'];
		}

		if (!empty($management_items))
		{
			$output[] = '<li class="'.((in_array($this->page_section, $this->management)) ? 'active' : 'normal').'"><button type="button" class="darkblue" onclick="dropDownNavMenu(\'management\')">Management<i class="fa fa-caret-down"></i></button></li>';
			$output[] = '<ul class="main-dropdown-list" id="dropdown_nav_menu_management">';
			$output[] = implode("\n", $management_items);
			$output[] = '</ul>';
		}
		
		// Generate Projects links
		$output[] = '<li class="'.((in_array($this->page_section, $this->projects)) ? 'active' : 'normal').'"><button type="button" class="darkblue" onclick="dropDownNavMenu(\'projects\')">Projects<i class="fa fa-caret-down"></i></button></li>';
		$output[] = '<ul class="main-dropdown-list" id="dropdown_nav_menu_projects">';
		foreach($this->slide_main_items as $item)
		{
			if (in_array($item['page_section'], $this->projects))
				$output[] = '<li class="item '.(($this->page_section == $item['page_section']) ? 'active' : 'normal').'"><a href="'.$item['link'].'" id="dropdown_page_section_'.$item['page_section'].'">'.$item['title'].'</a></li>';
			
			if ($this->page_section == $item['page_section'])
				$this->cur_project_title = $item['title'];
		}
		$output[] = '</ul>';
		
		return '<div class="dropdown-main-menu">'."\n\t".'<ul>'."\n\t\t".implode("\n\t\t", $output)."\n\t".'</ul>'."\n".'</div>';
	}
	
	function dropdown_sub_menu()
	{
		$output = [];
		
		foreach($this->top_sub_items as $sub_item)
		{
			if ($this->page_section == $sub_item['page_section'])
			{
				$output[] = '<div class="sub-dropdown">';
				
				if (in_array($this->page_id, $sub_item['pages']))
				{
					$output[] = '<li class="'.(($this->page_id == $sub_item['page_id'] || in_array($this->page_id, $sub_item['pages'])) ? 'active' : 'normal').'"><button type="button" class="darkblue" onclick="dropDownNavMenu(\''.$sub_item['page_id'].'\')" id="btn_subdropdown_'.$sub_item['page_id'].'">'.$sub_item['title'].'<i class="fa fa-caret-down"></i></button></li>';
					
					$output[] = '<ul class="sub-dropdown-list" id="dropdown_nav_menu_'.$sub_item['page_id'].'">';
					foreach($this->content_items as $item)
					{
						if (in_array($this->page_id, $item['pages']))
							$output[] = '<li class="item '.(($this->page_id == $item['page_id']) ? 'active' : 'normal').'"><a href="'.$item['link'].'" id="dropdown_page_id_'.$item['page_id'].'">'.$item['title'].'</a></li>';
						
						if ($this->page_id == $item['page_id'])
							$this->cur_sub_title = $item['title'];
					}
					$output[] = '</ul>';
				}
				else
				{
					$cur_sub_dropdown = [];
					foreach($this->content_items as $item)
					{
						if (in_array($sub_item['page_id'], $item['pages']))
							$cur_sub_dropdown[] = '<li class="item normal"><a href="'.$item['link'].'" id="dropdown_page_id_'.$item['page_id'].'">'.$item['title'].'</a></li>';
					}
					
					if (!empty($cur_sub_dropdown))
					{
						$output[] = '<li class="'.(($this->page_id == $sub_item['page_id']) ? 'active' : 'normal').'"><button type="button" onclick="dropDownNavMenu(\''.$sub_item['page_id'].'\')" id="btn_subdropdown_'.$sub_item['page_id'].'">'.$sub_item['title'].'<i class="fa fa-caret-down"></i></button></li>';
						$output[] = '<ul class="sub-dropdown-list" id="dropdown_nav_menu_'.$sub_item['page_id'].'">';
						
						foreach($cur_sub_dropdown as $sub_dropdown)
							$output[] = $sub_dropdown;
						
						$output[] = '</ul>';
					}
					else
					{
						$output[] = '<li class="'.(($this->page_id == $sub_item['page_id']) ? 'active' : 'normal').'"><button type="button"><a href="'.$sub_item['link'].'" id="dropdown_page_id_'.$sub_item['page_id'].'">'.$sub_item['title'].'</a></button></li>';

						// ???
						$output[] = '<ul class="sub-dropdown-list" id="dropdown_nav_menu_'.$sub_item['page_id'].'">';	
						$output[] = '</ul>';
					}
				}
				
				$output[] = '</div>';
				
				if ($this->page_id == $sub_item['page_id'])
					$this->cur_page_title = $sub_item['title'];
			}
		}
		
		return '<div class="dropdown-sub-menu">'."\n\t".'<ul>'."\n\t\t".implode("\n\t\t", $output)."\n\t".'</ul>'."\n".'</div>';
	}
	
	function gen_crumbs($delimiter = ' / ')
	{
		global $Core;
		
		$output = [];
		
		if ($this->cur_project_title != '')
			$output[] = $this->cur_project_title;
		
		if ($this->cur_page_title != '')
			$output[] = $this->cur_page_title;	
		
		if ($this->cur_sub_title != '')
			$output[] = $this->cur_sub_title;
		
		return ($Core->page_title != '') ? $Core->page_title : implode($delimiter, $output);
	}
	
	// Add item to nav main "Management" section
	function add_management($id)
	{
		$this->management[] = $id;
	} 
	
	// Add item to nav main "Projects" section 
	function add_project($id)
	{
		$this->projects[] = $id;
	} 
	
	// to remove
	function gen_content_menu(){}
	function show_menu(){} 
	function add_top_item(){}
	function add_slide_item(){}
	function add_slide_subitem(){}
}
