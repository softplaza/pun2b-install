<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if ($User->get('g_read_board') == '0' && $User->is_guest())
	message($lang_common['No view']);

$tid = isset($_GET['tid']) ? intval($_GET['tid']) : 0;
$fid = isset($_GET['fid']) ? intval($_GET['fid']) : 0;
if ($tid < 1 && $fid < 1 || $tid > 0 && $fid > 0)
	message($lang_common['Bad request']);

// Fetch some info about the topic and/or the forum
if ($tid)
{
	$query = array(
		'SELECT'	=> 'f.id, f.forum_name, f.moderators, f.redirect_url, fp.post_replies, fp.post_topics, t.subject, t.closed, s.user_id AS is_subscribed',
		'FROM'		=> 'punbb_topics AS t',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'punbb_forums AS f',
				'ON'			=> 'f.id=t.forum_id'
			),
			array(
				'LEFT JOIN'		=> 'punbb_forum_perms AS fp',
				'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.$User->get('g_id').')'
			),
			array(
				'LEFT JOIN'		=> 'punbb_subscriptions AS s',
				'ON'			=> '(t.id=s.topic_id AND s.user_id='.$User->get('id').')'
			)
		),
		'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$tid
	);
}
else
{
	$query = array(
		'SELECT'	=> 'f.id, f.forum_name, f.moderators, f.redirect_url, fp.post_replies, fp.post_topics',
		'FROM'		=> 'punbb_forums AS f',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'punbb_forum_perms AS fp',
				'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.$User->get('g_id').')'
			)
		),
		'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND f.id='.$fid
	);
}
$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
$cur_posting = $forum_db->fetch_assoc($result);

if (!$cur_posting)
	message($lang_common['Bad request']);

$is_subscribed = $tid && $cur_posting['is_subscribed'];

// Is someone trying to post into a redirect forum?
if ($cur_posting['redirect_url'] != '')
	message($lang_common['Bad request']);

// Sort out who the moderators are and if we are currently a moderator (or an admin)
$mods_array = ($cur_posting['moderators'] != '') ? unserialize($cur_posting['moderators']) : array();
$forum_page['is_admmod'] = ($User->get('g_id') == FORUM_ADMIN || ($User->get('g_moderator') == '1' && array_key_exists($User->get('username'), $mods_array))) ? true : false;

// Do we have permission to post?
if ((($tid && (($cur_posting['post_replies'] == '' && $User->get('g_post_replies') == '0') || $cur_posting['post_replies'] == '0')) ||
	($fid && (($cur_posting['post_topics'] == '' && $User->get('g_post_topics') == '0') || $cur_posting['post_topics'] == '0')) ||
	(isset($cur_posting['closed']) && $cur_posting['closed'] == '1')) &&
	!$forum_page['is_admmod'])
	message($lang_common['No permission']);

// Start with a clean slate
$errors = array();

// Did someone just hit "Submit" or "Preview"?
if (isset($_POST['form_sent']))
{
	// If it's a new topic
	if ($fid)
	{
		$subject = swift_trim($_POST['subject']);

		if ($subject == '')
			$errors[] = 'No subject';
		else if (utf8_strlen($subject) > SUBJECT_MAXIMUM_LENGTH)
			$errors[] = 'Too long subject';
		else if ($Config->get('p_subject_all_caps') == '0' && check_is_all_caps($subject) && !$forum_page['is_admmod'])
			$errors[] = 'All caps subject';
	}

	// If we're an administrator or moderator, make sure the CSRF token in $_POST is valid
	if ($User->get('is_admmod') && (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== generate_form_token(get_current_url())))
		$errors[] = 'CSRF token mismatch';

	// Clean up message from POST
	$message = forum_linebreaks(swift_trim($_POST['message']));

	if (strlen($message) > DATABASE_QUERY_MAXIMUM_LENGTH)
		$errors[] = 'Too long message';
	else if ($Config->get('p_message_all_caps') == '0' && check_is_all_caps($message) && !$forum_page['is_admmod'])
		$errors[] = 'All caps message';

	// Validate BBCode syntax
	if ($Config->get('p_message_bbcode') == '1' || $Config->get('o_make_links') == '1')
	{
		if (!defined('FORUM_PARSER_LOADED'))
			require SITE_ROOT.'include/parser.php';

		$message = preparse_bbcode($message, $errors);
	}

	if ($message == '')
		$errors[] = 'No message';

	$hide_smilies = isset($_POST['hide_smilies']) ? 1 : 0;
	$subscribe = isset($_POST['subscribe']) ? 1 : 0;

	$now = time();

	// Did everything go according to plan?
	if (empty($errors) && !isset($_POST['preview']))
	{
		// If it's a reply
		if ($tid)
		{
			$post_info = array(
				'is_guest'		=> $User->get('is_guest'),
				'poster'		=> $User->get('username'),
				'poster_id'		=> $User->get('id'),	// Always 1 for guest posts
				'poster_email'	=> ($User->get('is_guest') && $email != '') ? $email : null,	// Always null for non-guest posts
				'subject'		=> $cur_posting['subject'],
				'message'		=> $message,
				'hide_smilies'	=> $hide_smilies,
				'posted'		=> $now,
				'subscr_action'	=> ($Config->get('o_subscriptions') == '1' && $subscribe && !$is_subscribed) ? 1 : (($Config->get('o_subscriptions') == '1' && !$subscribe && $is_subscribed) ? 2 : 0),
				'topic_id'		=> $tid,
				'forum_id'		=> $cur_posting['id'],
				'update_user'	=> true,
				'update_unread'	=> true
			);

			add_post($post_info, $new_pid);
		}
		// If it's a new topic
		else if ($fid)
		{
			$post_info = array(
				'is_guest'		=> $User->get('is_guest'),
				'poster'		=> $User->get('username'),
				'poster_id'		=> $User->get('id'),	// Always 1 for guest posts
				'poster_email'	=> null,	// Always null for non-guest posts
				'subject'		=> $subject,
				'message'		=> $message,
				'hide_smilies'	=> $hide_smilies,
				'posted'		=> $now,
				'subscribe'		=> ($Config->get('o_subscriptions') == '1' && (isset($_POST['subscribe']) && $_POST['subscribe'] == '1')),
				'forum_id'		=> $fid,
				'forum_name'	=> $cur_posting['forum_name'],
				'update_user'	=> true,
				'update_unread'	=> true
			);

			add_topic($post_info, $new_tid, $new_pid);
		}

		redirect($URL->link('punbb_posts', $new_pid), 'Post redirect');
	}
}

$Core->set_page_id('punbb_post', 'punbb');
require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
	<input type="hidden" name="forum_user" value="<?php echo $User->get('username') ?>">
	<div class="card">
		<div class="card-header">
			<h6 class="mb-0"><?php echo ($tid) ? 'Compose your reply' : 'Compose your topic' ?></h6>
		</div>
		<div class="card-body">
<?php if ($fid) : ?>
			<div class="mb-3">
				<label class="form-label" for="fld_subject">Subject</label>
				<input type="text" name="subject" class="form-control" id="fld_subject" value="<?php echo (isset($_POST['subject']) ? html_encode($_POST['subject']) : '') ?>">
			</div>
<?php endif; ?>
			<div class="mb-3">
				<?php $Hooks->get_hook('PunbbForumPostPreMessage'); ?>
				<textarea type="text" name="message" class="form-control editor" id="fld_message" data-editor><?php echo (isset($_POST['message']) ? html_encode($_POST['message']) : '') ?></textarea>
			</div>
			<button type="submit" name="form_sent" class="btn btn-primary"><?php echo ($tid) ? 'Submit reply' : 'Submit topic' ?></button>
		</div>
	</div>
</form>

<?php
require SITE_ROOT.'footer.php';
