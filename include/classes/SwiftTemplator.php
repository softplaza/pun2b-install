<?php

/**
 * @author SwiftProjectManager.Com
 * @copyright (C) 2021 SwiftManager license GPL
 * @package SwiftTemplator
**/

class SwiftTemplator
{
	public $tpl_name = 'main';
	public $tpl_path = SITE_ROOT.'include/template/';
	public $tpl_content = '';

	public $head_elements = [];

	function setTemplate($tpl_name = ''){
		global $User, $Loader;
		
		$this->tpl_name = ($tpl_name != '') ? $tpl_name : $this->tpl_name;
		
		if (file_exists(SITE_ROOT.'style/'.$User->get('style').'/'.$this->tpl_name.'.tpl'))
			$this->tpl_path = SITE_ROOT.'style/'.$User->get('style').'/'.$this->tpl_name.'.tpl';
		else if (file_exists(SITE_ROOT.'include/template/'.$this->tpl_name.'.tpl'))
			$this->tpl_path = SITE_ROOT.'include/template/'.$this->tpl_name.'.tpl';
		else
			$this->tpl_path = SITE_ROOT.'include/template/main.tpl';
		
		$this->tpl_content = file_get_contents($this->tpl_path);

		require SITE_ROOT.'style/'.$User->get('style').'/index.php';
	}

	function genHeadElements(){	
		global $Config;
	
		header('Expires: Thu, 21 Jul 1977 07:30:00 GMT');
		header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
		header('Cache-Control: post-check=0, pre-check=0', false);
		// For HTTP/1.0 compability
		header('Pragma: no-cache');
		// Send the Content-type header in case the web server is setup to send something else
		header('Content-type: text/html; charset=utf-8');
	
		$this->head_elements[] = '<title>'.html_encode($Config->get('o_board_title')).'</title>';
		
		$head_elements = implode("\n", $this->head_elements);
		$this->tpl_content = str_replace('<!--head_elements-->', $head_elements, $this->tpl_content);
	}

	function getSection($section = ''){
		global $URL, $Hooks;

		if (
			//$this->tpl_name == 'admin' && 
			file_exists(SITE_ROOT.'admin/sections/'.$section.'.php'))
		{
			ob_start();
			require SITE_ROOT.'admin/sections/'.$section.'.php';
			$content = swift_trim(ob_get_contents());
			$this->tpl_content = str_replace('<!--'.$section.'-->', $content, $this->tpl_content);
			ob_end_clean();
		}
	}

	function getCSS(){
		global $Loader;
		$this->tpl_content = str_replace('<!--css_elements-->', $Loader->render_css(), $this->tpl_content);
	}
	
	function getJS(){
		global $Loader;
		$this->tpl_content = str_replace('<!--avascript-->', $Loader->render_js(), $this->tpl_content);
	}

	// Flash messages
	function getFlashMessenger(){
		global $FlashMessenger;
		$flash_messages = '<div id="brd-messages">'.$FlashMessenger->show(true).'</div>'."\n";
		$this->tpl_content = str_replace('<!--flash_messages-->', $flash_messages, $this->tpl_content);
	}

	// START SUBST - <!--content-->
	function startContent($tpl_name = ''){
		global $Hooks;

		$this->setTemplate($tpl_name);
		
		$Hooks->get_hook('hd_head');

		$this->genHeadElements();
		$this->getCSS();
		
		$this->getFlashMessenger();

		$this->getSection('navbar');
		$this->getSection('sidebar');
		$this->getSection('footer');
		
		ob_start();
	}

	// END SUBST - <!--content-->
	function endContent(){
		global $DBLayer, $Hooks;
		
		$Hooks->get_hook('ft_js_include');

		$content = swift_trim(ob_get_contents());
		$this->tpl_content = str_replace('<!--content-->', $content, $this->tpl_content);
		ob_end_clean();
		
		$this->getJS();
		
		$this->debug();

		echo  $this->tpl_content;
		
		// End the transaction
		$DBLayer->end_transaction();
		
		// Close the db connection (and free up any result data)
		$DBLayer->close();
		
		exit();
	}

	function debug(){
		global $User, $DBLayer, $microtime_start, $lang_common;

		if ($User->is_admin())
		{
			ob_start();
		
			// Calculate script generation time
			$time_diff = get_microtime() - $microtime_start;
			$query_time_total = $time_percent_db = 0.0;
		
			$saved_queries = $DBLayer->get_saved_queries();
			if (count($saved_queries) > 0)
			{
				foreach ($saved_queries as $cur_query)
				{
					$query_time_total += $cur_query[1];
				}
		
				if ($query_time_total > 0 && $time_diff > 0)
				{
					$time_percent_db = ($query_time_total / $time_diff) * 100;
				}
			}
		
			echo '<p id="querytime" class="quiet">'.sprintf($lang_common['Querytime'],
				gen_number_format($time_diff, 3),
				gen_number_format(100 - $time_percent_db, 0),
				gen_number_format($time_percent_db, 0),
				gen_number_format($DBLayer->get_num_queries())).'</p>'."\n";
		
			if (defined('SPM_SHOW_QUERIES') || ($User->is_admin()))
				echo get_saved_queries();
		
			$tpl_temp = swift_trim(ob_get_contents());
			$this->tpl_content = str_replace('<!--debug-->', $tpl_temp, $this->tpl_content);
			ob_end_clean();
		}
	}
}
