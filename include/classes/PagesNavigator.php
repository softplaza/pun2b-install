<?php 
/**
 * @copyright (C) 2020 SwiftProjectManager.Com, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftPagination
 */

class PagesNavigator
{
	// 
	public $navigation = true;
	
	// Display all items on page
	public $link_to_all = false;
	
	// Number of all got items
	public $total_items = 1;
	
	// How many items we gonna display on one page
	public $items_on_page = 1;
	
	// 
	public $disp_items = 15;
	
	// 
	public $start_from = 1;

	public $pages_navi_top = true;
	public $pages_navi_bottom = true;
	
	// Set number of got items
	function set_total($total = 1)
	{
		$this->total_items = $total;
		$this->disp_items = $this->disp_items();
	}

	// Check user options (num_items_on_page)
	function disp_items()
	{
		global $User, $Config;
		
		$disp_items = (!$User->is_guest() && $User->get('num_items_on_page') > 0) ? $User->get('num_items_on_page') : $Config->get('o_num_items_on_page');
		
		return ($disp_items > 0) ? $disp_items : 15;
	}
	
	// Determine the topic offset (based on $_GET['p'])
	function cur_page($num_pages)
	{
		$cur_page = (!isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] == 0 || $_GET['p'] > $num_pages) ? 1 : $_GET['p'];
		
		return $cur_page;
	}
	
	// Get limit query for DB 'LIMIT' => 
	function limit()
	{
		$num_pages = ceil($this->total_items / $this->disp_items);
		$cur_page = $this->cur_page($num_pages);
		$start_from = $this->disp_items * ($cur_page - 1);
		
		$this->start_from = $start_from;
		return ($cur_page > -1) ? $start_from.', '.$this->disp_items : '';
	}
	
	// Rebuld link with "p" param
	function query_url($page = 1)
	{
		$url_parts = parse_url(get_current_url());
		if (isset($url_parts['query'])) {
			parse_str($url_parts['query'], $params);
		} else {
			$params = array();
		}

		// Set num page
		$stack = array('p' => $page);
		if (!in_array('p', $params))
			$params = array_merge($params, $stack);

		$query_string = '';
		foreach($params as $key => $val)
		{
			if ($key == 'p')
				$val = $page;
			
			if ($query_string == '')
				$query_string = '?'.$key.'='.$val;
			else
				$query_string .= '&'.$key.'='.$val;
		}

		$url_param = array(
			$url_parts['scheme'],
			'://',
			$url_parts['host'],
			$url_parts['path'],
			$query_string
		);
		
		return implode('', $url_param);
	}
	
	// Get array and count items on page
	function num_items($input = 1)
	{
		$this->items_on_page = is_array($input) ? count($input) : intval($input);
	}
	
	// Get page navigation to display
	function getNavi()
	{
		$items_per_page = $this->items_on_page;
		$total_items = $this->total_items;
		$num_pages = ceil($total_items / $this->disp_items);
		$cur_page = $this->cur_page($num_pages);
		
		$pages = array();
		$link_to_all = false;
	
		// If $cur_page == -1, we link to all pages
		if ($cur_page == -1)
		{
			$cur_page = 1;
			$link_to_all = true;
			$this->link_to_all = true;
		}
		
		$output = '';
		if ($num_pages > 1)
		{
			// Add a previous page link
			if ($num_pages > 1 && $cur_page > 1)
				$pages[] = '<li class="page-item"><a class="page-link" href="'.$this->query_url($cur_page - 1).'"><i class="fas fa-angle-double-left"></i></a></li>';
			else
				$pages[] = '<li class="page-item disabled"><span class="page-link"><i class="fas fa-angle-double-left"></i></span></li>';
			
			if ($cur_page > 3)
			{
				$pages[] = '<li class="page-item"><a class="page-link" href="'.$this->query_url(1).'">'.gen_number_format(1).'</a></li>';
			
				if ($cur_page > 5)
					$pages[] = '<li class="page-item"><span class="page-link">...</span></li>';
			}
			
			// Don't ask me how the following works. It just does, OK? :-)
			for ($current = ($cur_page == 5) ? $cur_page - 3 : $cur_page - 2, $stop = ($cur_page + 4 == $num_pages) ? $cur_page + 4 : $cur_page + 3; $current < $stop; ++$current)
			{
				if ($current < 1 || $current > $num_pages)
					continue;
				else if ($current != $cur_page || $link_to_all)
					$pages[] = '<li class="page-item"><a class="page-link" href="'.$this->query_url($current).'">'.gen_number_format($current).'</a></li>';
				else
					$pages[] = '<li class="page-item active" aria-current="page"><span class="page-link">'.gen_number_format($current).'</span></li>';
			}

			if ($cur_page <= ($num_pages-3))
			{
				if ($cur_page != ($num_pages-3) && $cur_page != ($num_pages-4))
					$pages[] = '<li class="page-item"><span class="page-link">...</span></li>';

				$pages[] = '<li class="page-item"><a class="page-link" href="'.$this->query_url($num_pages).'">'.gen_number_format($num_pages).'</a></li>';
			}

			// Add a next page link
			if ($num_pages > 1 && !$link_to_all && $cur_page < $num_pages)
				$pages[] = '<li class="page-item"><a class="page-link" href="'.$this->query_url($cur_page + 1).'"><i class="fas fa-angle-double-right"></i></a></li>';
			else
				$pages[] = '<li class="page-item disabled"><span class="page-link"><i class="fas fa-angle-double-right"></i></span></li>';
			
			$paging = [];
			$paging[] = '<ul class="pagination me-2">'.implode('', $pages).'</ul>';
			
			if ($total_items > 1)
				$paging[] = '<span class="page-link">Items: <span class="fw-bold">'.$items_per_page.'</span> of <span class="fw-bold">'.$total_items.'</span></span>';

			$output = (!empty($paging)) ? '<nav aria-label="Page navigation" class="nav-pagination">'."\n\t".implode("\n\t", $paging)."\n".'</nav>' : '';
		}
		else
			// Display num items only
			$output = ($total_items > 1) ? '<nav aria-label="Page navigation" class="nav-pagination">'."\n\t".'<span class="page-link">Items: <span class="fw-bold">'.$items_per_page.'</span> of <span class="fw-bold">'.$total_items.'</span></span>'."\n".'</nav>' : '';

		//if ($this->navigation)
		//{
			//$this->navigation = false;
			return $output;
		//}	
	}
}
