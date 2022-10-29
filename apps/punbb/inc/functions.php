<?php

// Define the version and database revision that this code was written for
define('FORUM_VERSION', '1.4.4');
define('FORUM_DB_REVISION', 5);

// Define a few commonly used constants
define('FORUM_UNVERIFIED', 0);
define('FORUM_ADMIN', 1);
define('FORUM_GUEST', 2);

// Define avatars type
define('FORUM_AVATAR_NONE', 0);
define('FORUM_AVATAR_GIF', 1);
define('FORUM_AVATAR_JPG', 2);
define('FORUM_AVATAR_PNG', 3);

define('FORUM_SUBJECT_MAXIMUM_LENGTH', 70);
define('FORUM_DATABASE_QUERY_MAXIMUM_LENGTH', 140000);

define('FORUM_SEARCH_MIN_WORD', 3);
define('FORUM_SEARCH_MAX_WORD', 20);

define('FORUM_PUN_EXTENSION_REPOSITORY_URL', 'http://punbb.informer.com/extensions/1.4');

// Set a cookie, PunBB style!
// Like other headers, cookies must be sent before any output from your script.
// Use headers_sent() to ckeck wether HTTP headers has been sent already.
function forum_setcookie($name, $value, $expire)
{
	global $cookie_path, $cookie_domain, $cookie_secure, $cookie_samesite;

	$return = ($hook = get_hook('fn_forum_setcookie_start')) ? eval($hook) : null;
	if ($return !== null)
		return;

	if (empty($cookie_samesite))
		$cookie_samesite = 'Lax';
	else if ($cookie_samesite !== 'Strict' && $cookie_samesite !== 'Lax' && $cookie_samesite !== 'None')
		$cookie_samesite = 'Lax';

		// Enable sending of a P3P header
	header('P3P: CP="CUR ADM"');

	if (PHP_VERSION_ID < 70300)
		setcookie($name, $value, $expire, $cookie_path.'; SameSite='.$cookie_samesite, $cookie_domain, $cookie_secure, true);
	else
		setcookie($name, $value, [
			'expires'  => $expire,
			'path'     => $cookie_path,
			'domain'   => $cookie_domain,
			'secure'   => $cookie_secure,
			'httponly' => true,
			'samesite' => $cookie_samesite,
		]);
}

// Creates a new topic with its first post
function add_topic($post_info, &$new_tid, &$new_pid)
{
	global $DBLayer, $db_type, $Config, $lang_common;

	// Add the topic
	$query = array(
		'INSERT'	=> 'poster, subject, posted, last_post, last_poster, forum_id',
		'INTO'		=> 'punbb_topics',
		'VALUES'	=> '\''.$DBLayer->escape($post_info['poster']).'\', \''.$DBLayer->escape($post_info['subject']).'\', '.$post_info['posted'].', '.$post_info['posted'].', \''.$DBLayer->escape($post_info['poster']).'\', '.$post_info['forum_id']
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$new_tid = $DBLayer->insert_id();

	// To subscribe or not to subscribe, that ...
	if (!$post_info['is_guest'] && $post_info['subscribe'])
	{
		$query = array(
			'INSERT'	=> 'user_id, topic_id',
			'INTO'		=> 'punbb_subscriptions',
			'VALUES'	=> $post_info['poster_id'].' ,'.$new_tid
		);

		($hook = get_hook('fn_add_topic_qr_add_subscription')) ? eval($hook) : null;
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	}

	// Create the post ("topic post")
	$query = array(
		'INSERT'	=> 'poster, poster_id, poster_ip, message, hide_smilies, posted, topic_id',
		'INTO'		=> 'punbb_posts',
		'VALUES'	=> '\''.$DBLayer->escape($post_info['poster']).'\', '.$post_info['poster_id'].', \''.$DBLayer->escape(get_remote_address()).'\', \''.$DBLayer->escape($post_info['message']).'\', '.$post_info['hide_smilies'].', '.$post_info['posted'].', '.$new_tid
	);

	// If it's a guest post, there might be an e-mail address we need to include
	if ($post_info['is_guest'] && $post_info['poster_email'] !== null)
	{
		$query['INSERT'] .= ', poster_email';
		$query['VALUES'] .= ', \''.$DBLayer->escape($post_info['poster_email']).'\'';
	}

	($hook = get_hook('fn_add_topic_qr_add_topic_post')) ? eval($hook) : null;
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$new_pid = $DBLayer->insert_id();

	// Update the topic with last_post_id and first_post_id
	$query = array(
		'UPDATE'	=> 'punbb_topics',
		'SET'		=> 'last_post_id='.$new_pid.', first_post_id='.$new_pid,
		'WHERE'		=> 'id='.$new_tid
	);

	($hook = get_hook('fn_add_topic_qr_update_topic')) ? eval($hook) : null;
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	if (!defined('SEARCH_IDX_FUNCTIONS_LOADED'))
		require SITE_ROOT.'apps/punbb/inc/search_idx.php';

	update_search_index('post', $new_pid, $post_info['message'], $post_info['subject']);

	sync_forum($post_info['forum_id']);

	send_forum_subscriptions($post_info, $new_tid);

	// Increment user's post count & last post time
	if (isset($post_info['update_user']) && $post_info['update_user'])
	{
		if ($post_info['is_guest'])
		{
			$query = array(
				'UPDATE'	=> 'online',
				'SET'		=> 'last_post='.$post_info['posted'],
				'WHERE'		=> 'ident=\''.$DBLayer->escape(get_remote_address()).'\''
			);
		}
		else
		{
			$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> 'num_posts=num_posts+1, last_post='.$post_info['posted'],
				'WHERE'		=> 'id='.$post_info['poster_id']
			);
		}

		($hook = get_hook('fn_add_topic_qr_update_last_post')) ? eval($hook) : null;
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	}

	// If the posting user is logged in update his/her unread indicator
	if (!$post_info['is_guest'] && isset($post_info['update_unread']) && $post_info['update_unread'])
	{
		$tracked_topics = get_tracked_topics();
		$tracked_topics['topics'][$new_tid] = time();
		set_tracked_topics($tracked_topics);
	}

	($hook = get_hook('fn_add_topic_end')) ? eval($hook) : null;
}

// Creates a new post
function add_post($post_info, &$new_pid)
{
	global $DBLayer, $db_type, $Config, $lang_common;

	$return = ($hook = get_hook('fn_add_post_start')) ? eval($hook) : null;
	if ($return !== null)
		return;

	// Add the post
	$query = array(
		'INSERT'	=> 'poster, poster_id, poster_ip, message, hide_smilies, posted, topic_id',
		'INTO'		=> 'punbb_posts',
		'VALUES'	=> '\''.$DBLayer->escape($post_info['poster']).'\', '.$post_info['poster_id'].', \''.$DBLayer->escape(get_remote_address()).'\', \''.$DBLayer->escape($post_info['message']).'\', '.$post_info['hide_smilies'].', '.$post_info['posted'].', '.$post_info['topic_id']
	);

	// If it's a guest post, there might be an e-mail address we need to include
	if ($post_info['is_guest'] && $post_info['poster_email'] !== null)
	{
		$query['INSERT'] .= ', poster_email';
		$query['VALUES'] .= ', \''.$DBLayer->escape($post_info['poster_email']).'\'';
	}

	($hook = get_hook('fn_add_post_qr_add_post')) ? eval($hook) : null;
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$new_pid = $DBLayer->insert_id();

	if (!$post_info['is_guest'])
	{
		// Subscribe or unsubscribe?
		if ($post_info['subscr_action'] == 1)
		{
			$query = array(
				'INSERT'	=> 'user_id, topic_id',
				'INTO'		=> 'punbb_subscriptions',
				'VALUES'	=> $post_info['poster_id'].' ,'.$post_info['topic_id']
			);

			($hook = get_hook('fn_add_post_qr_add_subscription')) ? eval($hook) : null;
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
		else if ($post_info['subscr_action'] == 2)
		{
			$query = array(
				'DELETE'	=> 'punbb_subscriptions',
				'WHERE'		=> 'topic_id='.$post_info['topic_id'].' AND user_id='.$post_info['poster_id']
			);

			($hook = get_hook('fn_add_post_qr_delete_subscription')) ? eval($hook) : null;
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
	}

	// Count number of replies in the topic
	$query = array(
		'SELECT'	=> 'COUNT(p.id)',
		'FROM'		=> 'punbb_posts AS p',
		'WHERE'		=> 'p.topic_id='.$post_info['topic_id']
	);

	($hook = get_hook('fn_add_post_qr_get_topic_reply_count')) ? eval($hook) : null;
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$num_replies = $DBLayer->result($result, 0) - 1;

	// Update topic
	$query = array(
		'UPDATE'	=> 'punbb_topics',
		'SET'		=> 'num_replies='.$num_replies.', last_post='.$post_info['posted'].', last_post_id='.$new_pid.', last_poster=\''.$DBLayer->escape($post_info['poster']).'\'',
		'WHERE'		=> 'id='.$post_info['topic_id']
	);

	($hook = get_hook('fn_add_post_qr_update_topic')) ? eval($hook) : null;
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	sync_forum($post_info['forum_id']);

	if (!defined('FORUM_SEARCH_IDX_FUNCTIONS_LOADED'))
		require SITE_ROOT.'include/search_idx.php';

	update_search_index('post', $new_pid, $post_info['message']);

	send_subscriptions($post_info, $new_pid);

	// Increment user's post count & last post time
	if (isset($post_info['update_user']))
	{
		if ($post_info['is_guest'])
		{
			$query = array(
				'UPDATE'	=> 'online',
				'SET'		=> 'last_post='.$post_info['posted'],
				'WHERE'		=> 'ident=\''.$DBLayer->escape(get_remote_address()).'\''
			);
		}
		else
		{
			$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> 'num_posts=num_posts+1, last_post='.$post_info['posted'],
				'WHERE'		=> 'id='.$post_info['poster_id']
			);
		}

		($hook = get_hook('fn_add_post_qr_update_last_post')) ? eval($hook) : null;
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	}

	// If the posting user is logged in update his/her unread indicator
	if (!$post_info['is_guest'] && isset($post_info['update_unread']) && $post_info['update_unread'])
	{
		$tracked_topics = get_tracked_topics();
		$tracked_topics['topics'][$post_info['topic_id']] = time();
		set_tracked_topics($tracked_topics);
	}

	($hook = get_hook('fn_add_post_end')) ? eval($hook) : null;
}

// Update posts, topics, last_post, last_post_id and last_poster for a forum
function sync_forum($forum_id)
{
	global $DBLayer;

	$return = ($hook = get_hook('fn_sync_forum_start')) ? eval($hook) : null;
	if ($return !== null)
		return;

	// Get topic and post count for forum
	$query = array(
		'SELECT'	=> 'COUNT(t.id) AS num_topics, SUM(t.num_replies) AS num_posts',
		'FROM'		=> 'punbb_topics AS t',
		'WHERE'		=> 't.forum_id='.$forum_id
	);

	($hook = get_hook('fn_sync_forum_qr_get_forum_stats')) ? eval($hook) : null;
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$forum_stats = $DBLayer->fetch_assoc($result);

	// $num_posts is only the sum of all replies (we have to add the topic posts)
	$forum_stats['num_posts'] = $forum_stats['num_posts'] + $forum_stats['num_topics'];


	// Get last_post, last_post_id and last_poster for forum (if any)
	$query = array(
		'SELECT'	=> 't.last_post, t.last_post_id, t.last_poster',
		'FROM'		=> 'punbb_topics AS t',
		'WHERE'		=> 't.forum_id='.$forum_id.' AND t.moved_to is NULL',
		'ORDER BY'	=> 't.last_post DESC',
		'LIMIT'		=> '1'
	);

	($hook = get_hook('fn_sync_forum_qr_get_forum_last_post_data')) ? eval($hook) : null;
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$last_post_info = $DBLayer->fetch_assoc($result);

	if ($last_post_info)
	{
		$last_post_info['last_poster'] = '\''.$DBLayer->escape($last_post_info['last_poster']).'\'';
	}
	else
		$last_post_info['last_post'] = $last_post_info['last_post_id'] = $last_post_info['last_poster'] = 'NULL';

	// Now update the forum
	$query = array(
		'UPDATE'	=> 'punbb_forums',
		'SET'		=> 'num_topics='.$forum_stats['num_topics'].', num_posts='.$forum_stats['num_posts'].', last_post='.$last_post_info['last_post'].', last_post_id='.$last_post_info['last_post_id'].', last_poster='.$last_post_info['last_poster'],
		'WHERE'		=> 'id='.$forum_id
	);

	($hook = get_hook('fn_sync_forum_qr_update_forum')) ? eval($hook) : null;
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	($hook = get_hook('fn_sync_forum_end')) ? eval($hook) : null;
}

// Send out subscription emails
function send_forum_subscriptions($topic_info, $new_tid)
{
	global $SwiftMailer, $Config, $DBLayer, $forum_url, $lang_common;

	$return = ($hook = get_hook('fn_send_forum_subscriptions_start')) ? eval($hook) : null;
	if ($return !== null)
		return;

	if ($Config->get('o_subscriptions') != '1')
		return;

	// Get any subscribed users that should be notified (banned users are excluded)
	$query = array(
		'SELECT'	=> 'u.id, u.email, u.notify_with_post, u.language',
		'FROM'		=> 'users AS u',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'forum_subscriptions AS fs',
				'ON'			=> 'u.id=fs.user_id'
			),
			array(
				'LEFT JOIN'		=> 'forum_perms AS fp',
				'ON'			=> '(fp.forum_id='.$topic_info['forum_id'].' AND fp.group_id=u.group_id)'
			),
			array(
				'LEFT JOIN'		=> 'online AS o',
				'ON'			=> 'u.id=o.user_id'
			),
			array(
				'LEFT JOIN'		=> 'bans AS b',
				'ON'			=> 'u.username=b.username'
			),
		),
		'WHERE'		=> 'b.username IS NULL AND (fp.read_forum IS NULL OR fp.read_forum=1) AND fs.forum_id='.$topic_info['forum_id'].' AND u.id!='.$topic_info['poster_id']
	);

	($hook = get_hook('fn_send_forum_subscriptions_qr_get_users_to_notify')) ? eval($hook) : null;
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);

	$subscribers = array();
	while ($row = $DBLayer->fetch_assoc($result))
	{
		$subscribers[] = $row;
	}

	if (!empty($subscribers))
	{
		$notification_emails = array();

		// Loop through subscribed users and send e-mails
		foreach ($subscribers as $cur_subscriber)
		{
			// Is the subscription e-mail for $cur_subscriber['language'] cached or not?
			if (!isset($notification_emails[$cur_subscriber['language']]) && file_exists(FORUM_ROOT.'lang/'.$cur_subscriber['language'].'/mail_templates/new_topic.tpl'))
			{
				// Load the "new topic" template
				$mail_tpl = forum_trim(file_get_contents(FORUM_ROOT.'lang/'.$cur_subscriber['language'].'/mail_templates/new_topic.tpl'));

				// Load the "new topic full" template (with first post included)
				$mail_tpl_full = forum_trim(file_get_contents(FORUM_ROOT.'lang/'.$cur_subscriber['language'].'/mail_templates/new_topic_full.tpl'));

				// The first row contains the subject (it also starts with "Subject:")
				$first_crlf = strpos($mail_tpl, "\n");
				$mail_subject = forum_trim(substr($mail_tpl, 8, $first_crlf-8));
				$mail_message = forum_trim(substr($mail_tpl, $first_crlf));

				$first_crlf = strpos($mail_tpl_full, "\n");
				$mail_subject_full = forum_trim(substr($mail_tpl_full, 8, $first_crlf-8));
				$mail_message_full = forum_trim(substr($mail_tpl_full, $first_crlf));

				$mail_subject = str_replace('<forum_name>', '\''.$topic_info['forum_name'].'\'', $mail_subject);
				$mail_message = str_replace('<forum_name>', '\''.$topic_info['forum_name'].'\'', $mail_message);
				$mail_message = str_replace('<topic_starter>', $topic_info['poster'], $mail_message);
				$mail_message = str_replace('<topic_subject>', '\''.$topic_info['subject'].'\'', $mail_message);
				$mail_message = str_replace('<topic_url>', forum_link($forum_url['topic'], array($new_tid, sef_friendly($topic_info['subject']))), $mail_message);
				$mail_message = str_replace('<unsubscribe_url>', forum_link($forum_url['forum_unsubscribe'], array($topic_info['forum_id'], generate_form_token('forum_unsubscribe'.$topic_info['forum_id'].$cur_subscriber['id']))), $mail_message);
				$mail_message = str_replace('<board_mailer>', sprintf($lang_common['Forum mailer'], $Config->get('o_board_title')), $mail_message);

				$mail_subject_full = str_replace('<forum_name>', '\''.$topic_info['forum_name'].'\'', $mail_subject_full);
				$mail_message_full = str_replace('<forum_name>', '\''.$topic_info['forum_name'].'\'', $mail_message_full);
				$mail_message_full = str_replace('<topic_starter>', $topic_info['poster'], $mail_message_full);
				$mail_message_full = str_replace('<topic_subject>', '\''.$topic_info['subject'].'\'', $mail_message_full);
				$mail_message_full = str_replace('<message>', $topic_info['message'], $mail_message_full);
				$mail_message_full = str_replace('<topic_url>', forum_link($forum_url['topic'], $new_tid), $mail_message_full);
				$mail_message_full = str_replace('<unsubscribe_url>', forum_link($forum_url['forum_unsubscribe'], array($topic_info['forum_id'], generate_form_token('forum_unsubscribe'.$topic_info['forum_id'].$cur_subscriber['id']))), $mail_message_full);
				$mail_message_full = str_replace('<board_mailer>', sprintf($lang_common['Forum mailer'], $Config->get('o_board_title')), $mail_message_full);

				$notification_emails[$cur_subscriber['language']][0] = $mail_subject;
				$notification_emails[$cur_subscriber['language']][1] = $mail_message;
				$notification_emails[$cur_subscriber['language']][2] = $mail_subject_full;
				$notification_emails[$cur_subscriber['language']][3] = $mail_message_full;

				$mail_subject = $mail_message = $mail_subject_full = $mail_message_full = null;
			}

			// We have to double check here because the templates could be missing
			// Make sure the e-mail address format is valid before sending
			if (isset($notification_emails[$cur_subscriber['language']]) && is_valid_email($cur_subscriber['email']))
			{
				if ($cur_subscriber['notify_with_post'] == '0')
					$SwiftMailer->send($cur_subscriber['email'], $notification_emails[$cur_subscriber['language']][0], $notification_emails[$cur_subscriber['language']][1]);
				else
					$SwiftMailer->send($cur_subscriber['email'], $notification_emails[$cur_subscriber['language']][2], $notification_emails[$cur_subscriber['language']][3]);
			}
		}
	}

	($hook = get_hook('fn_send_forum_subscriptions_end')) ? eval($hook) : null;
}

// Save array of tracked topics in cookie
function set_tracked_topics($tracked_topics)
{
	global $cookie_name, $cookie_path, $cookie_domain, $cookie_secure, $Config;

	$return = ($hook = get_hook('fn_set_tracked_topics_start')) ? eval($hook) : null;
	if ($return !== null)
		return;

	$cookie_data = '';
	if (!empty($tracked_topics))
	{
		// Sort the arrays (latest read first)
		arsort($tracked_topics['topics'], SORT_NUMERIC);
		arsort($tracked_topics['forums'], SORT_NUMERIC);

		// Homebrew serialization (to avoid having to run unserialize() on cookie data)
		foreach ($tracked_topics['topics'] as $id => $timestamp)
			$cookie_data .= 't'.$id.'='.$timestamp.';';
		foreach ($tracked_topics['forums'] as $id => $timestamp)
			$cookie_data .= 'f'.$id.'='.$timestamp.';';

		// Enforce a 4048 byte size limit (4096 minus some space for the cookie name)
		if (strlen($cookie_data) > 4048)
		{
			$cookie_data = substr($cookie_data, 0, 4048);
			$cookie_data = substr($cookie_data, 0, strrpos($cookie_data, ';')).';';
		}
	}

	forum_setcookie($cookie_name.'_track', $cookie_data, time() + $Config->get('o_timeout_visit'));
	$_COOKIE[$cookie_name.'_track'] = $cookie_data;	// Set it directly in $_COOKIE as well
}


// Extract array of tracked topics from cookie
function get_tracked_topics()
{
	global $cookie_name;

	$return = ($hook = get_hook('fn_get_tracked_topics_start')) ? eval($hook) : null;
	if ($return !== null)
		return $return;

	$tracked_topics = array('topics' => array(), 'forums' => array());

	$cookie_data = isset($_COOKIE[$cookie_name.'_track']) ? $_COOKIE[$cookie_name.'_track'] : false;
	if (! $cookie_data || strlen($cookie_data) > 4048)
		return $tracked_topics;

	// Unserialize data from cookie
	foreach (explode(';', $cookie_data) as $id_data)
	{
		if (isset($id_data[3])) {
			$type = substr($id_data, 0, 1) === 'f' ? 'forums' : 'topics';
			$data = explode('=', substr($id_data, 1), 2);

			if (
				isset($data[1])
				&& 0 < ($id = (int) $data[0])
				&& 0 < ($timestamp = (int) $data[1])
			) {
				$tracked_topics[$type][$id] = $timestamp;
			}
		}
	}

	($hook = get_hook('fn_get_tracked_topics_end')) ? eval($hook) : null;

	return $tracked_topics;
}

// Make a string safe to use in a URL
function sef_friendly($str)
{
	global $Config, $User;
	static $lang_url_replace, $forum_reserved_strings;

	if (!isset($lang_url_replace))
		require SITE_ROOT.'lang/'.$User->get('language').'/url_replace.php';

	if (!isset($forum_reserved_strings))
	{
		// Bring in any reserved strings
		if (file_exists(SITE_ROOT.'include/url/'.$Config->get('o_sef').'/reserved_strings.php'))
			require SITE_ROOT.'include/url/'.$Config->get('o_sef').'/reserved_strings.php';
		else
			require SITE_ROOT.'include/url/Default/reserved_strings.php';
	}

	$str = strtr($str, $lang_url_replace);
	$str = strtolower(utf8_decode($str));
	$str = forum_trim(preg_replace(array('/[^a-z0-9\s]/', '/[\s]+/'), array('', '-'), $str), '-');

	foreach ($forum_reserved_strings as $match => $replace)
		if ($str == $match)
			return $replace;
		else if ($match != '')
			$str = str_replace($match, $replace, $str);

	return $str;
}

// Check the text is CAPSED
function check_is_all_caps($text)
{
	return (bool)/**/(utf8_strtoupper($text) == $text && utf8_strtolower($text) != $text);
}

// Convert \r\n and \r to \n
function forum_linebreaks($str)
{
	return str_replace(array("\r\n", "\r"), "\n", $str);
}

// Checks if a word is a valid searchable word
function validate_search_word($word)
{
	global $User;
	static $stopwords;

	$return = ($hook = get_hook('fn_validate_search_word_start')) ? eval($hook) : null;
	if ($return !== null)
		return $return;

	if (!isset($stopwords))
	{
		if (file_exists(SITE_ROOT.'apps/punbb/lang/'.$User->get('language').'/stopwords.txt'))
		{
			$stopwords = file(SITE_ROOT.'apps/punbb/lang/'.$User->get('language').'/stopwords.txt');
			$stopwords = array_map('forum_trim', $stopwords);
			$stopwords = array_filter($stopwords);
		}
		else
			$stopwords = array();

		($hook = get_hook('fn_validate_search_word_modify_stopwords')) ? eval($hook) : null;
	}

	$num_chars = utf8_strlen($word);

	$return = ($hook = get_hook('fn_validate_search_word_end')) ? eval($hook) : null;
	if ($return !== null)
		return $return;

	return $num_chars >= FORUM_SEARCH_MIN_WORD && $num_chars <= FORUM_SEARCH_MAX_WORD && !in_array($word, $stopwords);
}