<?php

define('SITE_ROOT', '../');
require SITE_ROOT.'include/common.php';

if (!$User->is_admmod() )
	message($lang_common['No permission']);

// Load the admin.php language file
require SITE_ROOT.'lang/'.$User->get('language').'/admin_common.php';
require SITE_ROOT.'lang/'.$User->get('language').'/admin_settings.php';

$section = isset($_GET['section']) ? $_GET['section'] : null;


if (isset($_POST['form_sent']))
{
	$form = array_map('trim', $_POST['form']);

	// Validate input depending on section
	switch ($section)
	{
		case 'setup':
		{
			if ($form['board_title'] == '')
				message($lang_admin_settings['Error no board title']);

			// Clean default_lang, default_style, and sef
			$form['default_style'] = preg_replace('#[\.\\\/]#', '', $form['default_style']);
			$form['default_lang'] = preg_replace('#[\.\\\/]#', '', $form['default_lang']);

			// Make sure default_lang, default_style, and sef exist
			if (!file_exists(SITE_ROOT.'style/'.$form['default_style'].'/index.php'))
				message($lang_common['Bad request']);
			if (!file_exists(SITE_ROOT.'lang/'.$form['default_lang'].'/common.php'))
				message($lang_common['Bad request']);
			if (!isset($form['default_dst']) || $form['default_dst'] != '1')
				$form['default_dst'] = '0';

			break;
		}

		case 'features':
		{
			if (!isset($form['search_all_forums']) || $form['search_all_forums'] != '1') $form['search_all_forums'] = '0';
			if (!isset($form['ranks']) || $form['ranks'] != '1') $form['ranks'] = '0';
			if (!isset($form['censoring']) || $form['censoring'] != '1') $form['censoring'] = '0';
			if (!isset($form['quickjump']) || $form['quickjump'] != '1') $form['quickjump'] = '0';
			if (!isset($form['show_version']) || $form['show_version'] != '1') $form['show_version'] = '0';
			if (!isset($form['show_moderators']) || $form['show_moderators'] != '1') $form['show_moderators'] = '0';
			if (!isset($form['users_online']) || $form['users_online'] != '1') $form['users_online'] = '0';

			if (!isset($form['quickpost']) || $form['quickpost'] != '1') $form['quickpost'] = '0';
			if (!isset($form['subscriptions']) || $form['subscriptions'] != '1') $form['subscriptions'] = '0';
			//if (!isset($form['force_guest_email']) || $form['force_guest_email'] != '1') $form['force_guest_email'] = '0';
			if (!isset($form['show_dot']) || $form['show_dot'] != '1') $form['show_dot'] = '0';
			if (!isset($form['topic_views']) || $form['topic_views'] != '1') $form['topic_views'] = '0';
			if (!isset($form['show_post_count']) || $form['show_post_count'] != '1') $form['show_post_count'] = '0';
			if (!isset($form['show_user_info']) || $form['show_user_info'] != '1') $form['show_user_info'] = '0';

			if (!isset($form['message_bbcode']) || $form['message_bbcode'] != '1') $form['message_bbcode'] = '0';
			if (!isset($form['message_img_tag']) || $form['message_img_tag'] != '1') $form['message_img_tag'] = '0';
			if (!isset($form['smilies']) || $form['smilies'] != '1') $form['smilies'] = '0';
			if (!isset($form['make_links']) || $form['make_links'] != '1') $form['make_links'] = '0';
			if (!isset($form['message_all_caps']) || $form['message_all_caps'] != '1') $form['message_all_caps'] = '0';
			if (!isset($form['subject_all_caps']) || $form['subject_all_caps'] != '1') $form['subject_all_caps'] = '0';

			$form['indent_num_spaces'] = intval($form['indent_num_spaces']);
			$form['quote_depth'] = intval($form['quote_depth']);

			if (!isset($form['signatures']) || $form['signatures'] != '1') $form['signatures'] = '0';
			if (!isset($form['sig_bbcode']) || $form['sig_bbcode'] != '1') $form['sig_bbcode'] = '0';
			if (!isset($form['sig_img_tag']) || $form['sig_img_tag'] != '1') $form['sig_img_tag'] = '0';
			if (!isset($form['smilies_sig']) || $form['smilies_sig'] != '1') $form['smilies_sig'] = '0';
			if (!isset($form['sig_all_caps']) || $form['sig_all_caps'] != '1') $form['sig_all_caps'] = '0';

			$form['sig_length'] = intval($form['sig_length']);
			$form['sig_lines'] = intval($form['sig_lines']);

			if (!isset($form['avatars']) || $form['avatars'] != '1') $form['avatars'] = '0';

			// Make sure avatars_dir doesn't end with a slash
			if (substr($form['avatars_dir'], -1) == '/')
				$form['avatars_dir'] = substr($form['avatars_dir'], 0, -1);

			$form['avatars_width'] = intval($form['avatars_width']);
			$form['avatars_height'] = intval($form['avatars_height']);
			$form['avatars_size'] = intval($form['avatars_size']);

			if (!isset($form['check_for_updates']) || $form['check_for_updates'] != '1') $form['check_for_updates'] = '0';
			if (!isset($form['check_for_versions']) || $form['check_for_versions'] != '1') $form['check_for_versions'] = '0';

			if (!isset($form['mask_passwords']) || $form['mask_passwords'] != '1') $form['mask_passwords'] = '0';
			if (!isset($form['gzip']) || $form['gzip'] != '1') $form['gzip'] = '0';

			break;
		}

		case 'email':
		{
			$form['admin_email'] = strtolower($form['admin_email']);
			if (!is_valid_email($form['admin_email']))
				message($lang_admin_settings['Error invalid admin e-mail']);

			$form['webmaster_email'] = strtolower($form['webmaster_email']);
			if (!is_valid_email($form['webmaster_email']))
				message($lang_admin_settings['Error invalid web e-mail']);

			$form['email_mode'] = intval($form['email_mode']);

			if (!isset($form['smtp_ssl']) || $form['smtp_ssl'] != '1') $form['smtp_ssl'] = '0';

			break;
		}

		case 'announcements':
		{
			if (!isset($form['announcement']) || $form['announcement'] != '1') $form['announcement'] = '0';

			if ($form['announcement_message'] != '')
				$form['announcement_message'] = convert_line_breaks($form['announcement_message']);
			else
				$form['announcement_message'] = $lang_admin_settings['Announcement message default'];

			break;
		}

		case 'registration':
		{
			if (!isset($form['regs_allow']) || $form['regs_allow'] != '1') $form['regs_allow'] = '0';
			if (!isset($form['regs_verify']) || $form['regs_verify'] != '1') $form['regs_verify'] = '0';
			if (!isset($form['allow_banned_email']) || $form['allow_banned_email'] != '1') $form['allow_banned_email'] = '0';
			if (!isset($form['allow_dupe_email']) || $form['allow_dupe_email'] != '1') $form['allow_dupe_email'] = '0';
			if (!isset($form['regs_report']) || $form['regs_report'] != '1') $form['regs_report'] = '0';

			if (!isset($form['rules']) || $form['rules'] != '1') $form['rules'] = '0';

			if ($form['rules_message'] != '')
				$form['rules_message'] = convert_line_breaks($form['rules_message']);
			else
				$form['rules_message'] = $lang_admin_settings['Rules default'];

			break;
		}

		case 'maintenance':
		{
			if (!isset($form['maintenance']) || $form['maintenance'] != '1') $form['maintenance'] = '0';

			if ($form['maintenance_message'] != '')
				$form['maintenance_message'] = convert_line_breaks($form['maintenance_message']);
			else
				$form['maintenance_message'] = $lang_admin_settings['Maintenance message default'];

			break;
		}

		default:
		{
			break;
		}
	}

	foreach ($form as $key => $input)
	{
		// Only update permission values that have changed
		if ($Config->key_exists('p_'.$key) && $Config->get('p_'.$key) != $input)
		{
			$query = array(
				'UPDATE'	=> 'config',
				'SET'		=> 'conf_value='.intval($input),
				'WHERE'		=> 'conf_name=\'p_'.$DBLayer->escape($key).'\''
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}

		// Only update option values that have changed
		if ($Config->key_exists('o_'.$key) && $Config->get('o_'.$key) != $input)
		{
			if ($input != '' || is_int($input))
				$value = '\''.$DBLayer->escape($input).'\'';
			else
				$value = 'NULL';

			$query = array(
				'UPDATE'	=> 'config',
				'SET'		=> 'conf_value='.$value,
				'WHERE'		=> 'conf_name=\'o_'.$DBLayer->escape($key).'\''
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
	}

	// Regenerate the config cache
	$Cachinger->gen_config();

	// Add flash message
	$FlashMessenger->add_info($lang_admin_settings['Settings updated']);

	redirect($URL->link('admin_settings_'.$section), $lang_admin_settings['Settings updated']);
}


if (!$section || $section == 'setup')
{
	require SITE_ROOT.'admin/settings_setup.php';
}

else if ($section == 'features')
{
	require SITE_ROOT.'admin/settings_features.php';
}
else if ($section == 'announcements')
{
	require SITE_ROOT.'admin/settings_announcements.php';
}
else if ($section == 'registration')
{
	require SITE_ROOT.'admin/settings_registration.php';
}

else if ($section == 'maintenance')
{
	require SITE_ROOT.'admin/settings_maintenance.php';
}

else if ($section == 'email')
{
	require SITE_ROOT.'admin/settings_email.php';
}

require SITE_ROOT.'footer.php';
