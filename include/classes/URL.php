<?php

/**
 * @author SwiftProjectManager.Com
 * @copyright (C) 2020 SwiftManager license GPL
 * @package URL
**/

class URL
{
	// Url rules
	private $urls = [];

	// Register basic urls
	function __construct()
	{
		global $Config;
		require SITE_ROOT.'include/url/Default/url_scheme.php';

		if (isset($url_scheme))
		{
			$this->urls = $url_scheme;
		}
	}

	// Add some urls as array
	function add_urls($urls)
	{
		if (!empty($urls))
		{
			foreach($urls as $key => $val)
				$this->urls[$key] = $val;
		}
	}

	// Add a url
	function add($key, $val)
	{
		$this->urls[$key] = $val;
	}
	
	// Generate a hyperlink by key with parameters
	function link($key, $args = null)
	{
		// If key not exists we will use key as link
		$link = isset($this->urls[$key]) ? $this->urls[$key] : $key;
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

	// Generate a hyperlink with parameters and anchor and a subsection such as a subpage
	function sublink($link, $sublink, $subarg, $args = null)
	{
		if ($sublink == $this->urls['page'] && $subarg == 1)
			return $this->link($link, $args);

		$gen_link = $link;
		if (!is_array($args) && $args !== null)
			$gen_link = str_replace('$1', $args, $link);
		else
		{
			for ($i = 0; isset($args[$i]); ++$i)
				$gen_link = str_replace('$'.($i + 1), $args[$i], $gen_link);
		}

		if (isset($this->urls['insertion_find']))
			$gen_link = BASE_URL.'/'.str_replace($this->urls['insertion_find'], str_replace('$1', str_replace('$1', $subarg, $sublink), $this->urls['insertion_replace']), $gen_link);
		else
			$gen_link = BASE_URL.'/'.$gen_link.str_replace('$1', $subarg, $sublink);

		return $gen_link;
	}

	// Autogenerate a link. Args example ['id' => $id]
	function genLink($key, $args = null)
	{
		// If key not exists we will use key as link
		$link = isset($this->urls[$key]) ? $this->urls[$key] : $key;
		$output = BASE_URL.'/'.$link;

		$added_arg = false;
		if (is_array($args) && !empty($args))
		{
			foreach($args as $k => $v)
			{
				if (!$added_arg)
				{
					$output .= '?'.$k.'='.$v;
					$added_arg = true;
				}
				else
					$output .='&'.$k.'='.$v;
			}
		}

		return $output;
	}

	// Switches Sort By param: Title, ASC, DESC.
	function sortBy($title, $asc, $desc = 0)
	{
		$sort_by = isset($_GET['sort_by']) ? intval($_GET['sort_by']) : 0;

		$url_parts = parse_url(get_current_url());
		if (isset($url_parts['query'])) {
			parse_str($url_parts['query'], $params);
			$params = ($sort_by == $desc) ? array_merge($params, ['sort_by' => $asc]) : array_merge($params, ['sort_by' => $desc]);
		} else {
			$params = ($sort_by == $desc) ? ['sort_by' => $asc] : ['sort_by' => $desc];
		}

		$query_string = '';
		if (!empty($params))
		{
			foreach($params as $key => $val)
			{
				if ($query_string == '')
					$query_string = '?'.$key.'='.$val;
				else
					$query_string .= '&'.$key.'='.$val;
			}
		}

		$url_param = array(
			$url_parts['scheme'],
			'://',
			$url_parts['host'],
			$url_parts['path'],
			$query_string
		);

		if ($sort_by == $desc)
			return '<a href="'.implode('', $url_param).'" class="text-white">'.$title.' <i class="fas fa-sort-down"></i></a>';
		else
			return '<a href="'.implode('', $url_param).'" class="text-white">'.$title.' <i class="fas fa-sort-up"></i></a>';
	}

	function addParam($new_params = [])
	{
		$url_parts = parse_url(get_current_url());
		if (isset($url_parts['query'])) {
			parse_str($url_parts['query'], $params);
			$params = array_merge($params, $new_params);
		} else {
			$params = $new_params;
		}

		$query_string = '';
		if (!empty($params))
		{
			foreach($params as $key => $val)
			{
				if ($query_string == '')
					$query_string = '?'.$key.'='.$val;
				else
					$query_string .= '&'.$key.'='.$val;
			}
		}

		$url_param = [
			$url_parts['scheme'],
			'://',
			$url_parts['host'],
			$url_parts['path'],
			$query_string
		];

		return implode('', $url_param);
	}
}
