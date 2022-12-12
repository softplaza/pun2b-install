<?php
/**
 * @copyright (C) 2020 SwiftManager.Org, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftManager
 */

$page_param['styles'] = get_style_packs();

$page_param['languages'] = array();
$page_param['d'] = dir(SITE_ROOT.'lang');
while (($page_param['entry'] = $page_param['d']->read()) !== false)
{
	if ($page_param['entry'] != '.' && $page_param['entry'] != '..' && is_dir(SITE_ROOT.'lang/'.$page_param['entry']) && file_exists(SITE_ROOT.'lang/'.$page_param['entry'].'/common.php'))
		$page_param['languages'][] = $page_param['entry'];
}
$page_param['d']->close();

// Setup the form
$page_param['group_count'] = $page_param['item_count'] = $page_param['fld_count'] = 0;
$page_param['form_action'] = $URL->link('profile_settings', $id);

$page_param['hidden_fields'] = array(
	'form_sent'		=> '<input type="hidden" name="form_sent" value="1" />',
	'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token($page_param['form_action']).'" />'
);

$page_title = ($page_param['own_profile']) ? $lang_profile['Settings welcome'] : sprintf($lang_profile['Settings welcome user'], html_encode($user['realname']));
$Core->set_page_title($page_title);

?>
<div class="col-md-8">

	<form method="post" accept-charset="utf-8" action="<?php echo $page_param['form_action'] ?>">
		<?php echo implode("\n\t\t\t\t", $page_param['hidden_fields'])."\n" ?>
		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0"><?php echo $lang_profile['Local settings'] ?></h6>
			</div>
			<div class="card-body">	
<?php
		// Only display the language selection box if there's more than one language available
		if (count($page_param['languages']) > 1)
		{
			natcasesort($page_param['languages']);
?>
				<div class="mb-3">
					<label class="form-label" for="input_language"><?php echo $lang_profile['Language'] ?></label>
					<select id="input_language" name="form[language]" class="form-select">
<?php
			foreach ($page_param['languages'] as $temp)
			{
				if ($User->get('language') == $temp)
					echo "\t\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.$temp.'</option>'."\n";
				else
					echo "\t\t\t\t\t\t".'<option value="'.$temp.'">'.$temp.'</option>'."\n";
			}
?>
					</select>
				</div>
<?php
		}
?>
				<div class="mb-3">
					<label class="form-label" for="input_time_format">Time format</label>
					<select id="input_time_format" name="form[time_format]" class="form-select">
<?php

		foreach (array_unique($FormatDateTime->get_time_formats()) as $key => $time_format)
		{
			echo "\t\t\t\t\t\t".'<option value="'.$key.'"';
			if ($user['time_format'] == $key)
				echo ' selected="selected"';
			echo '>'. format_time(time(), 2, null, $time_format);
			if ($key == 0)
				echo ' ('.$lang_profile['Default'].')';
			echo "</option>\n";
		}
?>
					</select>
				</div>

				<div class="mb-3">
					<label class="form-label" for="input_date_format">Date format</label>
					<select id="input_date_format" name="form[date_format]" class="form-select">
<?php
		foreach (array_unique($FormatDateTime->get_date_formats()) as $key => $date_format)
		{
			echo "\t\t\t\t\t\t\t".'<option value="'.$key.'"';
			if ($user['date_format'] == $key)
				echo ' selected="selected"';
			echo '>'. format_time(time(), 1, $date_format, null, true);
			if ($key == 0)
				echo ' ('.$lang_profile['Default'].')';
			echo "</option>\n";
		}
?>
					</select>
				</div>

<?php
		// Only display the style selection box if there's more than one style available
		if (count($page_param['styles']) == 1)
			echo "\t\t\t\t".'<input type="hidden" name="form[style]" value="'.$page_param['styles'][0].'" />'."\n";
		else if (count($page_param['styles']) > 1)
		{
			natcasesort($page_param['styles']);
?>
				<div class="mb-3">
					<label class="form-label" for="input_style"><?php echo $lang_profile['Styles'] ?></label>
					<select id="input_style" name="form[style]" class="form-select">
<?php
			foreach ($page_param['styles'] as $temp)
			{
				if ($user['style'] == $temp)
					echo "\t\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.str_replace('_', ' ', $temp).'</option>'."\n";
				else
					echo "\t\t\t\t\t\t".'<option value="'.$temp.'">'.str_replace('_', ' ', $temp).'</option>'."\n";
			}
?>
					</select>
				</div>
<?php
		}
?>
				<div class="mb-3">
					<label class="form-label" for="input_num_items_on_page">Number of items per page</label>
					<select id="input_num_items_on_page" name="form[num_items_on_page]" class="form-select">
<?php
			$num_items_on_page_arr = array(15,25,50,75,100);
			foreach ($num_items_on_page_arr as $topic_num)
			{
				if ($user['num_items_on_page'] == $topic_num)
					echo "\t\t\t\t\t\t".'<option value="'.$topic_num.'" selected="selected">'.$topic_num.'</option>'."\n";
				else
					echo "\t\t\t\t\t\t".'<option value="'.$topic_num.'">'.$topic_num.'</option>'."\n";
			}
?>
					</select>
				</div>

				<div class="mb-3">
					<label class="form-label" for="input_users_sort_by">Sorting Users: Set in what order you would like to see the list of users on the pages.</label>
					<select id="input_users_sort_by" name="form[users_sort_by]" class="form-select">		
<?php
			$users_sort_by_arr = array(0 => 'Sort by First Name', 1 => 'Sort by Last Name');
			foreach ($users_sort_by_arr as $key => $val)
			{
				if ($user['users_sort_by'] == $key)
					echo "\t\t\t\t\t\t".'<option value="'.$key.'" selected="selected">'.$val.'</option>'."\n";
				else
					echo "\t\t\t\t\t\t".'<option value="'.$key.'">'.$val.'</option>'."\n";
			}
?>
					</select>
				</div>
			</div>

			<div class="card-header">
				<h6 class="card-title mb-0"><?php echo $lang_profile['E-mail settings'] ?></h6>
			</div>
			<div class="card-body">	
				<div class="form-check">
					<input type="radio" class="form-check-input" id="form_email_setting1" name="form[email_setting]" value="0" <?php if ($user['email_setting'] == '0') echo ' checked="checked"' ?>>
					<label class="form-check-label" for="form_email_setting1"><?php echo $lang_profile['E-mail setting 1'] ?></label>
				</div>
				<div class="form-check">
					<input type="radio" class="form-check-input" id="form_email_setting2" name="form[email_setting]" value="1" <?php if ($user['email_setting'] == '1') echo ' checked="checked"' ?>>
					<label class="form-check-label" for="form_email_setting2"><?php echo $lang_profile['E-mail setting 2'] ?></label>
				</div>
				<div class="form-check">
					<input type="radio" class="form-check-input" id="form_email_setting3" name="form[email_setting]" value="2" <?php if ($user['email_setting'] == '2') echo ' checked="checked"' ?>>
					<label class="form-check-label" for="form_email_setting3"><?php echo $lang_profile['E-mail setting 3'] ?></label>
				</div>
			</div>
<?php
Hook::doAction('ProfileChangeDetailsSettingsEmailFieldsetEnd');
?>
			<div class="card-body">	
				<button type="submit" name="update" class="btn btn-primary">Update profile</button>
			</div>
		</div>
	</form>
</div>
