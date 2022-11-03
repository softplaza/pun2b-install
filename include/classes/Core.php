<?php
/**
 * Basic functions of SwiftManager
 * @author SwiftManager.Org
 * @copyright (C) 2021 SwiftManager license GPL
 * @package Core
**/

class Core
{
	// Default 'help'
	public $cur_page_id = '';
	
	// Default 'index'
	public $cur_page_section = '';
	
	// Collect page errors
	public $errors = [];

	// Collect page warnings
	public $warnings = [];
	public $warnings_collapsed = true;
	// Collect page notifications
	public $notices = [];
	
	// Set title of notice
	public $notice_title = true;
	
	// ??
	public $page_menu_items = [];
	
	// Information of current user
	public $user_info = [];
	
	// Basic configurations
	public $configs = [];
	
	// Url rules
	public $urls = [];
	
	// Common translations of /lang/English/common.php
	public $translations = [];
	
	// Title of current page
	public $page_title = '';
	
	// Extensions info: ID, PATH, URL, NAME
	public $ext_info = [];
	
	// Setup basic information of Extension
	public $cur_ext_info = [];
	
	// Array of dropdown items
	public $dropdown_items = [];
	public $bootstrap_dropdown_items = [];
	
	// Add extension info
	function add_ext_info($ext_info = []) {
		if (isset($ext_info['id']))
			$this->ext_info[$ext_info['id']] = $ext_info;
	}
	
	// Add only one error to array
	function add_error($val) {
		$this->errors[] = $val;
	}	
	
	// Add errors as array
	function add_errors($arr) {
		if (is_array($arr) && !empty($arr)) {
			foreach($arr as $error)
				$this->errors[] = $error;
		}
	}
	
	// Check errors
	function has_errors() {
		return !empty($this->errors) ? true : false;
	}

	// Add only one warning to array
	function add_warning($val) {
		$this->warnings[] = $val;
	}
	
	// Add some warnings to array
	function add_warnings($arr = []) {
		if (!empty($arr)) {
			foreach($arr as $warning)
				$this->warnings[] = $warning;
		}
	}
	
	function add_notice($val) {
		$this->notices[] = $val;
	}
	
	// Get current user info
	function cur_user($key = '') {
		return isset($this->user_info[$key]) ? $this->user_info[$key] : '';
	}
	
	// Set configurations as key => value
	function set_configs($arr) {
		$this->configs = $arr;
	}
	// Get configuration value by key
	function get_config($key = '') {
		return isset($this->configs[$key]) ? $this->configs[$key] : '';
	}
	
	// Set URLs as key => value
	function set_urls($arr) {
		$this->urls = $arr;
	}

	// Get url value by key
	function get_url($key = '') {
		return isset($this->urls[$key]) ? $this->urls[$key] : '';
	}
	
	// Set Translations as key => value
	function set_translations($arr) {
		$this->translations = $arr;
	}
	// Get Lang value by key
	function get_lang($key = '') {
		return isset($this->translations[$key]) ? $this->translations[$key] : '';
	}
	
	// Replace...
	function set_page_section_id($id) {
		if (!defined('PAGE_SECTION_ID'))
			define('PAGE_SECTION_ID', $id);
		
		//define('PAGE_SECTION', $id);
	}
	// to...
	function set_page_section($page_section) {
		if (!defined('PAGE_SECTION_ID')) {
			define('PAGE_SECTION_ID', $page_section);
			//define('PAGE_SECTION', $page_section);
			$this->cur_page_section = $page_section;
		}
	}
	
	function set_page_id($id, $page_section = '') {
		$action = isset($_GET['action']) ? $_GET['action'] : null;
		
		if (!defined('PAGE_SECTION_ID') && $page_section != '') {
			define('PAGE_SECTION_ID', $page_section);
			//define('PAGE_SECTION', $page_section);
		}
		
		$this->cur_page_section = $page_section;
		
		if ($action == 'print') {
			define('PAGE_ID', 'print');
			//define('PAGE_ID', 'print');
			$this->cur_page_id = 'print';
		}
		else
		{
			define('PAGE_ID', $id);
			//define('PAGE_ID', $id);
			$this->cur_page_id = $id;
		}
	}
	
	// 
	function get_messages(){}
	
	// Shows Errors, Warnings and notifications. Runs in header.php
	function get_system_messages()
	{
		$output = [];

		// GET ERRORS
		if (!empty($this->errors))
		{
			$output[] = '<div class="alert alert-danger" role="alert">';
			$output[] = '<h6 class="alert-heading mb-0">Error!</h6>';
			$output[] = '<hr class="my-1">';
			$output[] = '<ul class="">';
			foreach ($this->errors as $cur_error)
				$output[] = '<li>'.$cur_error.'</li>';
			$output[] = '</ul>';
			$output[] = '</div>';
		}

		// GET WARNINGS
		if (!empty($this->warnings))
		{
			$output[] = '<div class="accordion mb-2" id="warning_messages">';
			$output[] = '<div class="accordion-item">';
			$output[] = '<button class="accordion-button bg-warning text-danger '.($this->warnings_collapsed ? 'collapsed' : '').'" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_warnings" aria-expanded="'.($this->warnings_collapsed ? 'false' : 'true').'" aria-controls="collapse_warnings">Warning!</button>';
			$output[] = '<div id="collapse_warnings" class="accordion-collapse collapse '.(!$this->warnings_collapsed ? 'show' : '').'" aria-labelledby="heading_warnings" data-bs-parent="#warning_messages">';
			$output[] = '<div class="accordion-body" style="background: #fffdc9;">';
			$output[] = '<ul>';
			foreach ($this->warnings as $cur_warning)
				$output[] = '<li style="font-weight:bold;color:#b47400;">'.$cur_warning.'</li>';
			$output[] = '</ul>';
			$output[] = '</div>';
			$output[] = '</div>';
			$output[] = '</div>';
			$output[] = '</div>';
		}
		
		// If there were any notices, show them
		if (!empty($this->notices))
		{
			$output[] = '<div class="message-box ct-box error-box" style="background:#e2ffe2;">';
			if ($this->notice_title)
				$output[] = '<span class="warn hn"><strong>Message!</strong></span>';
			$output[] = '<ul class="error-list">';
			foreach ($this->notices as $cur_notice)
				$output[] = '<li class="warn"><span>'.$cur_notice.'</span></li>';
			$output[] = '</ul>';
			$output[] = '</div>';
		}
		
		//$Hook->get('ClassCoreFnGetSystemMessages');

		return !empty($output) ? implode("\n\t\t\t\t\t", $output) : '';
	}
	
	// Remove //
	function add_menu_item($item) {
		$this->page_menu_items[] = $item;
	}
	
	// Add menu item
	function add_page_action($item) {
		$this->page_menu_items[] = $item;
	}
	
	// Generate menu items
	function get_page_action_menu($print = true)
	{
		$output = [];
		$output[] = '<div class="page-actions">';
		
		if (PAGE_ID == 'print')
			$output[] = '<span class="item"><a href=\'javascript:window.print(); void 0;\'><i class="fa fa-print fa-2x" aria-hidden="true"></i>Print page</a></span>';
		else
		{
			$output[] = '<button type="button" class="springwood" onclick="btnPageAction()">Actions'."\t";
			$output[] = '<i class="fa fa-caret-down"></i>';
			$output[] = '</button>';
			$output[] = '<div class="action-menu">';
			
			if ($print)
				$output[] = '<span class="item"><a href="'.get_cur_url('action=print').'" target="_blank"><i class="fa fa-print fa-2x" aria-hidden="true"></i>Print page</a></span>';
			
			if (!empty($this->page_menu_items))
			{
				$output[] = '<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">';
				$output[] = '<input type="hidden" name="csrf_token" value="'.generate_form_token().'" />';
				
				foreach($this->page_menu_items as $action)
				{
					$output[] = '<span class="item">'.$action.'</span>';
				}
				
				$output[] = '</form>';
			}
			
			$output[] = '</div>';
		}
		
		$output[] = '</div>';
			
		return (!empty($output) ? implode("\n\t", $output) : '');
	}

	// Add dropdown menu item
	function add_dropdown_item($item)
	{
		$this->dropdown_items[] = $item;
	}

	// Generate dropdown menu items
	function get_dropdown_menu($id = 1)
	{
		$output = $items = [];
		$output[] = '<div class="dropdown dropend">';
		$output[] = '<button class="btn btn-sm btn-warning dropdown-toggle" type="button" id="dropdownMenu'.$id.'" data-bs-toggle="dropdown">';
		//$output[] = '<i class="fa fa-cog"></i>';
		$output[] = '<i class="fas fa-edit"></i>';
		$output[] = '</button>';
		$output[] = '<div class="dropdown-menu" aria-labelledby="dropdownMenu'.$id.'">';
		$output[] = '<ul class="list-group">';
		if (!empty($this->dropdown_items))
		{
			foreach($this->dropdown_items as $action)
			{
				if ($action != '')
					$items[] = '<li class="dropdown-item">'.str_replace('$1', $id, $action).'</li>';
			}
		}
		$output[] = implode("\n", $items);
		$output[] = '</ul>';
		$output[] = '</div>';
		$output[] = '</div>';
		
		$this->dropdown_items = [];
		
		if (!empty($items))
			return implode("\n", $output);
	}
	
	function get_dropdown_menu2($id = 0)
	{
		$output = [];
		$output[] = '<div class="dropdown-menu">';
		$output[] = '<button type="button" class="coral" onclick="dropDownListActions('.$id.')"><i class="fa fa-cog"></i>';
		$output[] = '<i class="fa fa-caret-down"></i>';
		$output[] = '</button>';
		$output[] = '<div class="list-actions" id="dropdown_menu_'.$id.'">';
		
		if (!empty($this->dropdown_items))
		{
			foreach($this->dropdown_items as $action)
			{
				$output[] = '<span class="item">'.str_replace('$1', $id, $action).'</span>';
			}
		}
		
		$output[] = '</div>';
		$output[] = '</div>';
		
		$this->dropdown_items = [];
			
		return (!empty($output) ? implode("\n", $output) : '');
	}

	function get_subhead()
	{
		$output = [];
		
		// Get generated page actions
		$output[] = $this->get_page_action_menu();
		
		// Generate page title
		if (!empty($this->page_title))
			$output[] = '<div class="crumbs-top"><span>'.$this->page_title.'</span></div>';
		else if (!empty($this->get_ext_info('name')))
			$output[] = '<div class="crumbs-top"><span>'.$this->get_ext_info('name').'</span></div>';
		else
			$output[] = '<div class="crumbs-top"><span>'.$this->get_config('o_board_title').'</span></div>';
		
		return !empty($output) ? '<div class="sub-head">'."\n\t\t".implode('', $output)."\n\t".'</div>' : '';
	}
	
	
	// Add current ext info
	function set_ext_info($app_id, $array = [])
	{
		if ($this->cur_page_section == $app_id)
		{
			if (!empty($array))
			{
				foreach($array as $key => $val)
					$this->cur_ext_info[$key] = $val;
			}
		}
		
		$this->ext_info[$app_id] = $array;
	}
	
	// get ext info by id
	function get_ext_info($key)
	{
		if (isset($this->cur_ext_info[$key]))
			return $this->cur_ext_info[$key];
	}
	
	// 
	function set_page_title($title)
	{
		$this->page_title = $title;
	}
	
	// 
	function get_data_by_id($array = [], $id = 0, $column = '')
	{
		$output = [];
		$str = '';
		
		if (!empty($array) && $id > 0)
		{
			foreach($array as $data)
			{
				if ($data['id'] == $id)
				{
					if ($column != '' && isset($data[$column]))
					{
						$str = $data[$column];
						break;
					}
					else
					{
						$output[] = $data;
						break;
					}
				}
				else
					$str = '';
			}
		}
		
		return ($column != '') ? $str : $output;
	}
}
