<?php

/**
 * @author SwiftManager.Org
 * @copyright (C) 2021 SwiftManager license GPL
 * @package SwiftMenu
 * @version 1.03
**/

class SwiftMenu
{
	var $cur_url = '';
	var $menu_items = [];

	var $nav_actions = [];
	var $nav_profile_links = [];

	var $bread_crumbs = [];
	var $level_count = 1;

	var $page_actions = true;

	var $nesting_level = 1;
	var $cur_parent_id = '';

	function __construct()
	{
		$this->cur_url = get_cur_url();
		$this->genBasicItems();
	}

	// Add to NavBar action link
	function addNavAction($action){
		$this->nav_actions[] = $action;
	}

	// Add to NavBar Profile link
	function addNavbarProfileLink($link){
		$this->nav_profile_links[] = $link;
	}

	function getNavBar()
	{
		global $URL, $User, $Config;

		$profile_links = $actions = [];

		if ($User->is_admmod())
		{
			// <i class="fa-solid fa-bell fa-lg"></i>
			$profile_links[] = '<li class="nav-item dropdown">';
			//$profile_links[] = '<span class="position-absolute start-50 badge rounded-pill bg-danger">9</span>';
			$profile_links[] = '<span class="nav-link text-white"><a href="'.BASE_URL.'" class="text-white"><i class="fa-solid fa-bell fa-lg"></i></a></span>';
			//$profile_links[] = '<ul class="dropdown-menu dropdown-menu-end">';
			//$profile_links[] = '<li><a class="dropdown-item" href="'.BASE_URL.'"><i class="fa fa-print fa-1x" aria-hidden="true"></i> My projects</a></li>';
			//$profile_links[] = '</ul>';
			$profile_links[] = '</li>';
		}

		// Profile links
		$profile_links[] = '<li class="nav-item dropdown">';
		$profile_links[] = '<span class="nav-link text-white" role="button" data-bs-toggle="dropdown"><i class="fas fa-user-circle fa-lg"></i></span>'; // class="nav-link dropdown-toggle text-white"
		$profile_links[] = '<ul class="dropdown-menu dropdown-menu-end">';

		if ($User->is_guest())
		{
			$profile_links[] = '<li><a class="dropdown-item" href="'.$URL->link('login').'"><i class="fas fa-sign-in-alt"></i> Sign in</a></li>'."\n";

			if ($Config->get('o_regs_allow') == '1')
				$profile_links[] = '<li><a class="dropdown-item" href="'.$URL->link('register').'"><i class="fas fa-user-plus"></i>Sign up</a></li>'."\n";
		}
		else
		{
			$profile_links[] = '<li><a class="dropdown-item" href="'.$URL->link('profile_about', $User->get('id')).'"><i class="fas fa-user-alt"></i> About Me</a></li>'."\n";
			$profile_links[] = '<li><a class="dropdown-item" href="'.$URL->link('profile_identity', $User->get('id')).'"><i class="far fa-id-card"></i> Identity</a></li>'."\n";
			$profile_links[] = '<li><a class="dropdown-item" href="'.$URL->link('profile_settings', $User->get('id')).'"><i class="fas fa-cog"></i> Settings</a></li>'."\n";
			if (!empty($this->nav_profile_links)){
				foreach($this->nav_profile_links as $key => $nav_link){
					$profile_links[] = "\t\t\t\t\t\t\t\t\t\t".$nav_link."\n";
				}
			}
			$profile_links[] = '<li><hr class="dropdown-divider"></li>'."\n";
			$profile_links[] = '<li><a class="dropdown-item" href="'.$URL->link('logout', [$User->get('id'), generate_form_token('logout'.$User->get('id'))]).'"><i class="fas fa-sign-out-alt"></i> Logout</a></li>'."\n";
		}

		$profile_links[] = '</ul>';
		$profile_links[] = '</li>';

		// Action links
		$actions[] = '<li class="nav-item dropdown">';
		$actions[] = '<span class="nav-link text-white" role="button" data-bs-toggle="dropdown"><i class="fas fa-print fa-lg"></i></span>';// Actions <i class="fas fa-share-alt"></i>
		$actions[] = '<ul class="dropdown-menu dropdown-menu-end">';

		if (PAGE_ID == 'print')
			$actions[] = "\t\t\t\t\t\t\t\t\t\t".'<li><a class="dropdown-item" href="javascript:window.print();void 0;"><i class="fa fa-print fa-1x" aria-hidden="true"></i> Print page</a></li>'."\n";
		else
			$actions[] = "\t\t\t\t\t\t\t\t\t\t".'<li><a class="dropdown-item" href="'.get_cur_url('action=print').'"><i class="fa fa-print fa-1x" aria-hidden="true"></i> Print page</a></li>'."\n";
		
		if (!empty($this->nav_actions))
		{
			foreach($this->nav_actions as $key => $nav_action)
			{
				$actions[] = "\t\t\t\t\t\t\t\t\t\t".$nav_action."\n";
			}
		}
		$actions[] = '</ul>';
		$actions[] = '</li>';

		krsort($this->bread_crumbs);
		$bread_crumbs = !empty($this->bread_crumbs) ? implode(' &rArr; ', $this->bread_crumbs) : html_encode($Config->get('o_board_title'));
		$output[] = "\t\t\t\t\t\t".'<nav class="swift-navbar navbar-dark bg-dark" id="navbar_top">'."\n";
		$output[] = "\t\t\t\t\t\t\t".'<a class="site-title" href="#">'.$bread_crumbs.'</a>'."\n";

		$output[] = "\t\t\t\t\t\t\t\t".'<ul class="nav float-end" style="font-size:16px">'."\n";

		if (!empty($profile_links))
			$output[] = implode('', $profile_links);

		if (!empty($actions) && !$User->is_guest() && $this->page_actions)
			$output[] = implode('', $actions);

		$output[] = "\t\t\t\t\t\t\t\t".'</ul>'."\n";
		$output[] = "\t\t\t\t\t\t".'</nav>'."\n";

		return implode("", $output);
	}

	function getSlideMenu()
	{
		$this->menu_items = $this->orderBy($this->menu_items, 'level', SORT_ASC);

		$this->findParent($this->menu_items);

		$output = [];
		$output[] = '<div class="sidebar close">';
		$output[] = "\t\t\t\t\t\t".'<div class="logo-details">';
		$output[] = "\t\t\t\t\t\t\t".'<i class="fas fa-bars"></i>';
		$output[] = "\t\t\t\t\t\t".'</div>';
		$output[] = "\t\t\t\t\t\t".'<ul class="nav-links">';
		$output[] = $this->buildMenu();
		$output[] = "\t\t\t\t\t\t\t".'</ul>';
		$output[] = "\t\t\t\t\t\t".'</div>';

		$output[] = $this->getNavBar();

		return implode("\n", $output);
	}

	function addItem($options = [])
	{
		$this->menu_items[] = 
		[
			'id'		=> isset($options['id']) ? $options['id'] : '0',
			'title'		=> isset($options['title']) ? $options['title'] : 'Menu',
			'link'		=> isset($options['link']) ? $options['link'] : '#',
			'parent_id'	=> isset($options['parent_id']) ? $options['parent_id'] : '',
			'icon'		=> isset($options['icon']) ? $options['icon'] : '',
			'level'		=> isset($options['level']) ? intval($options['level']) : $this->level_count,
		];
		++$this->level_count;
	}

	function findParent(&$array, $parent_id = 0, $child = [])
	{
		// make $array modifiable & $row modifiable
	    foreach($array as $i => &$row)
	    {
	    	// if not zero
	        if ($parent_id)
	        {
	        	// if found parent
	            if ($row['id'] == $parent_id)
	            {
	            	// append child to parent's nodes subarray
	                $row['nodes'][] = $child;
	            }
	            // go down rabbit hole looking for parent
	            else if(isset($row['nodes']))
	            {
	            	// look deeper for parent while preserving the initial parent_id and row
	                $this->findParent($row['nodes'], $parent_id, $child);
	            }
	        	else continue;
	        }
	        // child requires adoption
	        else if ($row['parent_id'])
	        {
	        	// remove child from level because it will be store elsewhere and won't be its own parent 
	        	// (reduce iterations in next loop & avoid infinite recursion)
	            unset($array[$i]);

	            // look for parent using parent_id while carrying the entire row as the childarray
	            $this->findParent($array, $row['parent_id'], $row);
	        }
	        else continue;
	    }
	    // return the modified array
	    return $array;
	}

	function buildMenu($menu_array = [])
	{
		$output = '';
		$menu_array = !empty($menu_array)? $menu_array : $this->menu_items;

		foreach($menu_array as $id => $item)
		{
			if ($this->cur_parent_id != $item['parent_id'])
				$this->nesting_level = 1;

			$active = ($this->cur_url == $item['link'] || $this->isActive($item)) ? 'active' : '';
			$icon = ($item['icon'] != '') ? $item['icon'] : '<i class="bx bx-grid-alt"></i>';

			if ($item['parent_id'] == '')
			{
				$show = (isset($item['nodes']) && $this->findActive($item['nodes']) || ($active != '') ? 'show' : '');

				$output .= "\t\t\t\t\t\t\t".'<li class="'.$show.'">'."\n";
				$output .= "\t\t\t\t\t\t\t\t".'<div class="icon-link">'."\n";
				$output .= "\t\t\t\t\t\t\t\t\t".'<a href="'.$item['link'].'" id="menu_item_'.$item['id'].'" class="flex-fill">'."\n";
				$output .= "\t\t\t\t\t\t\t\t\t\t".$icon."\n";
				$output .= "\t\t\t\t\t\t\t\t\t\t".'<span class="link_name">'.$item['title'].'</span>'."\n";
				$output .= "\t\t\t\t\t\t\t\t\t".'</a>'."\n";
				$output .= "\t\t\t\t\t\t\t\t\t".'<i class="fas fa-chevron-down arrow"></i>'."\n";
				$output .= "\t\t\t\t\t\t\t\t".'</div>'."\n";

				if (isset($item['nodes']))
					$output .= "\t\t\t\t\t\t\t\t".'<ul class="sub-menu">'.$this->buildMenu($item['nodes']).'</ul>'."\n";

				$output .= "\t\t\t\t\t\t\t".'</li>'."\n";

				if ($show == 'show' || $active != '')
					$this->bread_crumbs[] = $item['title'];
			}
			else
			{
				if (isset($item['nodes']))
				{	
					$show = ($this->cur_url == $item['link'] || $this->isActive($item)) ? 'show' : '';

					$output .= "\t\t\t\t\t\t\t".'<li class="'.$show.'">'."\n";
					$output .= '<div class="sub-arrow d-flex justify-content-between">';
					$output .= '<a href="#" id="menu_item_'.$item['id'].'" class="flex-fill">';

					//$output .= '<i class="fas fa-project-diagram fa-lg"></i>';
					$output .= '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="grey" class="bi bi-diagram-3 me-2" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M6 3.5A1.5 1.5 0 0 1 7.5 2h1A1.5 1.5 0 0 1 10 3.5v1A1.5 1.5 0 0 1 8.5 6v1H14a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-1 0V8h-5v.5a.5.5 0 0 1-1 0V8h-5v.5a.5.5 0 0 1-1 0v-1A.5.5 0 0 1 2 7h5.5V6A1.5 1.5 0 0 1 6 4.5v-1zM8.5 5a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1zM0 11.5A1.5 1.5 0 0 1 1.5 10h1A1.5 1.5 0 0 1 4 11.5v1A1.5 1.5 0 0 1 2.5 14h-1A1.5 1.5 0 0 1 0 12.5v-1zm1.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1zm4.5.5A1.5 1.5 0 0 1 7.5 10h1a1.5 1.5 0 0 1 1.5 1.5v1A1.5 1.5 0 0 1 8.5 14h-1A1.5 1.5 0 0 1 6 12.5v-1zm1.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1zm4.5.5a1.5 1.5 0 0 1 1.5-1.5h1a1.5 1.5 0 0 1 1.5 1.5v1a1.5 1.5 0 0 1-1.5 1.5h-1a1.5 1.5 0 0 1-1.5-1.5v-1zm1.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1z"/></svg>';

					$output .= $item['title'];
					$output .= '</a>';
					$output .= '<i class="fas fa-chevron-down fa-lg"></i>';
					$output .= '</div>';

					$output .= '<ul class="sub-menu-2">'.$this->buildMenu($item['nodes']).'</ul>';
					$output .= "\t\t\t\t\t\t\t".'</li>'."\n";
				}
				else
				{
					$output .= "\t\t\t\t\t\t\t".'<li class="'.$active.'">'."\n";
					$output .= '<div class="d-flex justify-content-between">';
					
					$output .= '<a href="'.$item['link'].'" id="menu_item_'.$item['id'].'" class="sub-menu-link flex-fill">';
					$output .= '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="grey" class="bi bi-arrow-return-right me-2" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1.5 1.5A.5.5 0 0 0 1 2v4.8a2.5 2.5 0 0 0 2.5 2.5h9.793l-3.347 3.346a.5.5 0 0 0 .708.708l4.2-4.2a.5.5 0 0 0 0-.708l-4-4a.5.5 0 0 0-.708.708L13.293 8.3H3.5A1.5 1.5 0 0 1 2 6.8V2a.5.5 0 0 0-.5-.5z"/></svg>  ';
					//$output .= '<i class="bx bx-grid-alt"></i>';
					$output .= '<span>'.$item['title'].'</span>';
					$output .= '</a>';
					$output .= '</div>';
					$output .= "\t\t\t\t\t\t\t".'</li>'."\n";
				}

				if ($active != '')
					$this->bread_crumbs[] = $item['title'];
			}

			$this->cur_parent_id = $item['parent_id'];
		}

		return $output;
	}

	function findActive($sub_menu)
	{
		$output = false;
		if (!empty($sub_menu))
		{
			foreach($sub_menu as $key => $item)
			{
				if (isset($item['nodes']))
				{
					foreach($item['nodes'] as $key => $nodes)
					{
						if (isset($nodes['link']) && $this->cur_url == $nodes['link'])
						{
							$output = true;
							break;
						}
					}
				}
			}
		}
		return $output;
	}

	function isActive($array)
	{
		$output = false;
		if (!empty($array) && isset($array['nodes']))
		{
			foreach($array['nodes'] as $key => $item)
			{
				if (isset($item['link']) && $this->cur_url == $item['link'])
				{
					$output = true;
					break;
				}
			}
		}
		return $output;
	}

	function genBasicItems()
	{
		global $User, $URL, $Config;

		if (!$User->is_guest())
		{
			$id = isset($_GET['id']) ? intval($_GET['id']) : $User->get('id');
			$own_profile = ($User->get('id') == $id) ? true : false;

			// Administration section
			if ($User->is_admin())
			{
				$this->addItem(['title' => 'Management', 'link' => '#', 'id' => 'admin', 'icon' => '<i class="fas fa-tachometer-alt"></i>', 'level' => 1]);

				$this->addItem(['title' => 'Information', 'link' => $URL->link('admin_index'), 'id' => 'admin_index', 'parent_id' => 'admin']);
				//$this->addItem(['title' => 'Extensions', 'link' => $URL->link('admin_extensions_manage'), 'id' => 'admin_extensions_manage', 'parent_id' => 'admin']);
				$this->addItem(['title' => 'Applications', 'link' => $URL->link('apps_management'), 'id' => 'apps_management', 'parent_id' => 'admin']);
			}

			// Settimgs
			if ($User->is_admin())
			{
				$this->addItem(['title' => 'Settings', 'link' => '#', 'id' => 'settings', 'icon' => '<i class="fas fa-cog"></i>', 'level' => 2]);

				$this->addItem(['title' => 'Setup', 'link' => $URL->link('admin_settings_setup'), 'id' => 'admin_settings_setup', 'parent_id' => 'settings']);
				$this->addItem(['title' => 'Features', 'link' => $URL->link('admin_settings_features'), 'id' => 'admin_settings_features', 'parent_id' => 'settings']);
				$this->addItem(['title' => 'Announcements', 'link' => $URL->link('admin_settings_announcements'), 'id' => 'admin_settings_announcements', 'parent_id' => 'settings']);
				$this->addItem(['title' => 'Maintenance mode', 'link' => $URL->link('admin_settings_maintenance'), 'id' => 'admin_settings_maintenance', 'parent_id' => 'settings']);	
				$this->addItem(['title' => 'E-mail', 'link' => $URL->link('admin_settings_email'), 'id' => 'admin_settings_email', 'parent_id' => 'settings']);
				$this->addItem(['title' => 'Registration', 'link' => $URL->link('admin_settings_registration'), 'id' => 'admin_settings_registration', 'parent_id' => 'settings']);
			}

			// Users
			if ($User->get('g_view_users') == '1')
			{
				$this->addItem(['title' => 'Users', 'link' => '#', 'id' => 'users', 'icon' => '<i class="fas fa-users-cog"></i>', 'level' => 3]);

				$this->addItem(['title' => 'Userlist', 'link' => $URL->link('admin_users'), 'id' => 'admin_users', 'parent_id' => 'users']);

				if ($User->checkAccess('system', 11))
					$this->addItem(['title' => 'Add User', 'link' => $URL->link('admin_new_user'), 'id' => 'admin_new_user', 'parent_id' => 'users']);
			}

			if ($User->is_admin())
			{
				$this->addItem(['title' => 'Groups', 'link' => $URL->link('admin_groups'), 'id' => 'admin_groups', 'parent_id' => 'users']);
				$this->addItem(['title' => 'Permissions', 'link' => $URL->link('admin_access'), 'id' => 'admin_access', 'parent_id' => 'users']);
				//$this->addItem(['title' => 'Add User', 'link' => $URL->link('admin_new_user'), 'id' => 'admin_new_user', 'parent_id' => 'users']);
			}
		}
		else
		{
			// Profile section
			$this->addItem(['title' => 'Login', 'link' => $URL->link('login'), 'id' => 'login', 'icon' => '<i class="fas fa-sign-in-alt"></i>', 'level' => 1]);

			if ($Config->get('o_regs_allow') == '1')
				$this->addItem(['title' => 'Register', 'link' => $URL->link('register'), 'id' => 'register', 'icon' => '<i class="fas fa-user-plus"></i>', 'level' => 1]);
		}
	}

	function orderBy()
	{
		$args = func_get_args();
		$data = array_shift($args);
		foreach ($args as $n => $field) {
			if (is_string($field)) {
				$tmp = array();
				foreach ($data as $key => $row)
					$tmp[$key] = $row[$field];
				$args[$n] = $tmp;
				}
		}
		$args[] = &$data;
		call_user_func_array('array_multisort', $args);
		return array_pop($args);
	}
}
