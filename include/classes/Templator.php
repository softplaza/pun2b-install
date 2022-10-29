<?php

/**
 * @author SwiftManager.Org
 * @copyright (C) 2021 SwiftManager license GPL
 * @package Templator
**/

class Templator
{
	// Default template name
	public $tpl_name = 'main';
	
	// Extension of templates
	public $tpl_ext = '.tpl';
	
	// Default template path
	public $tpl_path = '';
	
	// Current template's content
	public $tpl_main = '';

	// Setup the form
	public $fld_count = 50;
	public $group_count = 50;
	public $item_count = 50;

	function __construct(){
	}

	function setTPL()
	{
		global $User, $Loader;

		// Send no-cache headers
		// When yours truly first set eyes on this world! :)
		header('Expires: Thu, 21 Jul 1977 07:30:00 GMT');
		header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
		header('Cache-Control: post-check=0, pre-check=0', false);
		// For HTTP/1.0 compability
		header('Pragma: no-cache');
		// Send the Content-type header in case the web server is setup to send something else
		header('Content-type: text/html; charset=utf-8');

		if (!defined('PAGE_SECTION_ID'))
			define('PAGE_SECTION_ID', 'index');

		if (!defined('PAGE_ID'))
			define('PAGE_ID', 'help');

		// Load the main template
		if (PAGE_ID == 'help')
		{
			if (file_exists(SITE_ROOT.'style/'.$User->get('style').'/help.tpl'))
				$this->tpl_path = SITE_ROOT.'style/'.$User->get('style').'/help.tpl';
			else
				$this->tpl_path = SITE_ROOT.'include/template/help.tpl';
		}
		else if (PAGE_ID == 'print')
		{
			if (file_exists(SITE_ROOT.'style/'.$User->get('style').'/print.tpl'))
				$this->tpl_path = SITE_ROOT.'style/'.$User->get('style').'/print.tpl';
			else
				$this->tpl_path = SITE_ROOT.'include/template/print.tpl';
		}
		else
		{
			if (file_exists(SITE_ROOT.'style/'.$User->get('style').'/'.$this->tpl_name.'.tpl'))
				$this->tpl_path = SITE_ROOT.'style/'.$User->get('style').'/'.$this->tpl_name.'.tpl';
			else
				$this->tpl_path = SITE_ROOT.'include/template/main.tpl';
		}

		$this->tpl_main = file_get_contents($this->tpl_path);

		if (PAGE_ID == 'print')
			$Loader->add_css(BASE_URL.'/style/print.css?v='.time(), array('type' => 'url', 'group' => SPM_CSS_GROUP_SYSTEM, 'media' => 'print'));
	}

	function getSection($section)
	{
		$section = ($section != '') ? $section : '<!--'.$tpl_name.'-->';
		if ($this->tpl_name == 'admin' && file_exists(SITE_ROOT.'admin/sections/'.$section.'.php'))
		{
			ob_start();
			require SITE_ROOT.'admin/sections/'.$section.'.php';
			$content = swift_trim(ob_get_contents());
			$this->tpl_main = str_replace('<!--'.$tpl_name.'-->', $content, $this->tpl_main);
			ob_end_clean();
		}
	}

	// Generate HEAD elements
	function gen_head_elements($elements)
	{
		global $User, $Loader, $Config;

		$head_elements = [];
		$head_elements['robots'] = '<meta name="ROBOTS" content="NOINDEX, FOLLOW" />';
		$head_elements['title'] = '<title>'.html_encode($Config->get('o_board_title')).'</title>';
		
		$head_elements = array_merge($head_elements, $elements);

		// START
		ob_start();

		// Include stylesheets
		require SITE_ROOT.'style/'.$User->get('style').'/index.php';

		$head_temp = swift_trim(ob_get_contents());
		$num_temp = 0;

		foreach (explode("\n", $head_temp) as $style_temp)
			$head_elements['style'.$num_temp++] = $style_temp;

		ob_end_clean();
		// END SUBST - <!--head_elements-->

		// Render CSS from forum_loader
		$tmp_head = implode("\n\t", $head_elements).$Loader->render_css();

		$this->tpl_main = str_replace('<!--head_elements-->', $tmp_head, $this->tpl_main);

		return $this->tpl_main;
	}
	
	// Generate MAIN elements
	function gen_main_elements($elements)
	{
		$this->tpl_main = str_replace(array_keys($elements), array_values($elements), $this->tpl_main);
		return $this->tpl_main;
	}

	// Update content
	function update($tpl_main)
	{
		$this->tpl_main = $tpl_main;
		return $this->tpl_main;
	}

	// Update content
	function insert($key, $str)
	{
		$this->tpl_main = str_replace($key, $str, $this->tpl_main);
		return $this->tpl_main;
	}

	function footer_about()
	{
		// START SUBST - <!--footer_about-->
		ob_start();
?>
			<p id="copyright" style="opacity: 0.3;">&copy; Powered by <a href="https://swiftmanage.com/" target="_blank"><?php echo SPM_NAME ?> <?php echo SPM_VERSION ?></a></p>
<?php

		$tpl_temp = swift_trim(ob_get_contents());
		$this->tpl_main = str_replace('<!--footer_about-->', $tpl_temp, $this->tpl_main);
		ob_end_clean();
		// END SUBST - <!--footer_about-->
	}

	function footer_debug()
	{
		global $User, $DBLayer, $microtime_start, $lang_common;

		if (defined('SPM_DEBUG') || defined('SPM_SHOW_QUERIES') || $User->is_admin())
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
		
			if ($User->is_admin())
				echo '<p id="querytime" class="quiet">'.sprintf($lang_common['Querytime'],
					gen_number_format($time_diff, 3),
					gen_number_format(100 - $time_percent_db, 0),
					gen_number_format($time_percent_db, 0),
					gen_number_format($DBLayer->get_num_queries())).'</p>'."\n";
		
			if (defined('SPM_SHOW_QUERIES') || ($User->is_admin()))
				echo get_saved_queries();
		
			$tpl_temp = swift_trim(ob_get_contents());
			$this->tpl_main = str_replace('<!--footer_debug-->', $tpl_temp, $this->tpl_main);
			ob_end_clean();

			return $this->tpl_main;
		}
	}

	// START SUBST - <!--page_content-->
	function start_page_content()
	{
		ob_start();
	}
	
	// END SUBST - <!--page_content-->
	function end_page_content()
	{
		$tpl_temp = sm_trim(ob_get_contents());
		$this->tpl_main = str_replace('<!--page_content-->', $tpl_temp, $this->tpl_main);
		ob_end_clean();
	}
	
	// TEMPLATES
	function info_box($text)
	{
		$output = [];
		$output[] = '<div class="ct-box info-box">';
		$output[] = '<p>'.$text.'</p>';
		$output[] = '</div>';
		echo implode("\n", $output);
	}

	function content_head($text)
	{
		$output = [];
		$output[] = '<div class="content-head">';
		$output[] = '<h2 class="hn"><span>'.$text.'</span></h2>';
		$output[] = '</div>';
		echo implode("\n", $output);
	}

	/* FORM TEMPLATES */
	// DATE INPUT
	function form_set_date($title, $input_name, $input_value = '')
	{
		if (isset($_POST[$input_name]) && $input_value != '' && $input_value > 0)
			$input_value = date('Y-m-d', $input_value);
		else if ($input_value != '' && $input_value > 0)
			$input_value = date('Y-m-d', $input_value);
		else if (isset($_POST[$input_name]))
			$input_value = $_POST[$input_name];
	?>
		<div class="sf-set set<?=++$this->item_count?>">
			<div class="sf-box text">
				<label for="fld<?=++$this->fld_count?>"><span><strong><?=$title?></strong></span></label><br>
				<span class="fld-input"><input type="date" name="<?=$input_name?>" value="<?=$input_value?>"><img src="<?=BASE_URL?>/img/clear.png" onclick="clearDate(<?=$this->item_count?>)"></span>
			</div>
		</div>
	<?php
	}

	// TEXT INPUT
	function form_set_text($title, $input_name, $input_value = '', $required = false, $help = null, $placeholder = '')
	{
		if (isset($_POST[$input_name]))
			$input_value = html_encode($_POST[$input_name]);
		else if ($input_value != '')
			$input_value = html_encode($input_value);

		$required_sym = ($required) ? '<strong style="color:red;">*</strong>' : '';
		$help = ($help === null) ? 'Leave your comment' : '';

		$attr = [];
		$attr[] = ($required) ? 'required' : '';
		$attr[] = ($placeholder != '') ? 'placeholder="'.$placeholder.'"' : '';
	?>
		<div class="sf-set set<?=++$this->fld_count?>">
			<div class="sf-box select">
				<label for="fld<?=++$this->fld_count?>"><span><strong><?=$title?><?=$required_sym?></strong></span></label><br>
				<span class="fld-input" id="input_<?=$input_name?>"><input type="text" name="<?=$input_name?>" value="<?=$input_value?>" maxlength="255" <?=implode(' ', $attr)?>/></span>
			</div>
		</div>
	<?php
	}

	// TEXTAREA
	function form_set_textarea($title, $txt_name, $txt_value = '', $required = false, $help = null, $placeholder = '')
	{
		if ($txt_value != '')
			$txt_value = html_encode($txt_value);
		else if (isset($_POST[$txt_name]))
			$txt_value = html_encode($_POST[$txt_name]);

		$required_sym = ($required) ? '<strong style="color:red;">*</strong>' : '';
		$help = ($help === null) ? 'Enter details here' : $help;

		$attr = [];
		$attr[] = ($required) ? 'required' : '';
		$attr[] = ($placeholder != '') ? 'placeholder="'.$placeholder.'"' : '';
	?>
		<div class="txt-set set<?=++$this->item_count?>">
			<div class="txt-box textarea">
				<label for="fld<?=++$this->fld_count?>"><span><strong><?=$title?><?=$required_sym?></strong></span><small><?=$help?></small></label>
				<div class="txt-input"><span class="fld-input"><textarea id="fld<?=++$this->fld_count?>" name="<?=$txt_name?>" cols="55" <?=implode(' ', $attr)?>><?=$txt_value?></textarea></span></div>
			</div>
		</div>
	<?php
	}

	// RADIO FORM
	function form_set_radio($title, $input_name, $val_name = [0=>'No',1=>'Yes'], $input_value = '', $default = 0)
	{
		if (isset($_POST[$input_name]))
			$input_value = intval($_POST[$input_name]);
		else if ($input_value != '')
			$input_value = intval($input_value);
?>
		<fieldset class="mf-set set<?=++$this->item_count?>" id="form_radio_<?=$input_name?>">
			<legend><span><?=$title?></span></legend>
			<div class="mf-box" style="display:inline-flex;">
<?php 
		if (is_array($val_name) && !empty($val_name))
		{
			$i = 0;
			foreach($val_name as $val => $name)
			{
				if ($input_value == '')
					$checked = ($i == 0) ? ' checked="checked"' : '';
				else
					$checked = ($val == $input_value) ? ' checked="checked"' : '';
?>
				<div class="mf-item">
					<span class="fld-input"><input type="radio" name="<?=$input_name?>" value="<?=$val?>" <?=$checked?>></span>
					<label for="fld<?=++$this->fld_count?>"><?=$name?></label>
				</div>
<?php 
				++$i;
			}
		} 
?>
			</div>
		</fieldset>
<?php
	}


	/* INFORMATION BOXES */
	function view_set_text($title, $text)
	{
?>
	<div class="sf-set set<?=++$this->item_count?>">
		<div class="sf-box text">
			<label for="fld<?=++$this->fld_count?>"><span><?=$title?></span></label><br>
			<span class="fld-input"><strong><?=html_encode($text)?></strong></span>
		</div>
	</div>
<?php
		
	}
}
