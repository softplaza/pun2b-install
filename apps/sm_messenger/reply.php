<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$tid = isset($_GET['tid']) ? intval($_GET['tid']) : 0;

if ($tid < 1 || $User->is_guest())
	message($lang_common['No permission']);

$query = array(
	'SELECT'	=> 't.*, u.user_id, u.viewed',
	'FROM'		=> 'sm_messenger_topics AS t',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'sm_messenger_users AS u',
			'ON'			=> 't.id=u.topic_id'
		),
	),
	'WHERE'		=> 't.id='.$tid.' AND u.user_id='.$User->get('id'),
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$topic_info = $DBLayer->fetch_assoc($result);

if ($topic_info['user_id'] == $User->get('id') && $topic_info['viewed'] == 0)
{
	//mark message as read
	$query = array(
		'UPDATE'	=> 'sm_messenger_users',
		'SET'		=> 'viewed=1',
		'WHERE'		=> 'topic_id='.$tid.' AND user_id='.$User->get('id')
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);
}

if (empty($topic_info))
	message($lang_common['Bad request']);

$receiver_id = ($User->get('id') == $topic_info['last_receiver_id']) ? $topic_info['last_sender_id'] : $topic_info['last_receiver_id'];

$query = array(
	'SELECT'	=> 'u.id, u.realname, u.email, u.email_setting',
	'FROM'		=> 'users AS u',
	'WHERE'		=> 'u.id='.$receiver_id,
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$user_info = $DBLayer->fetch_assoc($result);

$query = array(
	'SELECT'	=> 'p.*, t.last_post_id',
	'FROM'		=> 'sm_messenger_posts AS p',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'sm_messenger_topics AS t',
			'ON'			=> 't.id=p.p_topic_id'
		),
	),
	'WHERE'		=> 't.id='.$tid,
	'ORDER BY'	=> 'p.p_posted DESC'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$messenger_info = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$messenger_info[$fetch_assoc['id']] = $fetch_assoc;
}

$query = array(
	'SELECT'	=> 'p.*',
	'FROM'		=> 'sm_messenger_posts AS p',
	'WHERE'		=> 'p.id='.$topic_info['last_post_id'],
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$last_message_info = $DBLayer->fetch_assoc($result);

if (isset($_POST['sent']))
{
	$message = isset($_POST['message']) ? swift_trim($_POST['message']) : '';
	$copy_to_email = isset($_POST['copy_to_email']) ? intval($_POST['copy_to_email']) : 0;
	
	if ($receiver_id == 0)
		$Core->add_error('No sender selected.');
	if ($user_info['realname'] == '')
		$Core->add_error('The sender\'s name is empty or does not exist.');
	if ($message == '')
		$Core->add_error('Message field cannot be empty.');
		
	$time_now = time();
	if (empty($Core->errors))
	{
		if (!empty($last_message_info) && $topic_info['viewed'] == 0)
		{
			$query = array(
				'UPDATE'	=> 'sm_messenger_posts',
				'SET'		=> '
					p_message=\''.$DBLayer->escape($message).'\',
					p_posted='.$time_now,
				'WHERE'		=> 'id='.$last_message_info['id'],
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
		else
		{
			$query = array(
				'INSERT'	=> 'p_topic_id, p_message, p_sender_id, p_sender_name, p_posted',
				'INTO'		=> 'sm_messenger_posts',
				'VALUES'	=> 
					'\''.$DBLayer->escape($tid).'\',
					\''.$DBLayer->escape($message).'\',
					\''.$DBLayer->escape($User->get('id')).'\',
					\''.$DBLayer->escape($User->get('realname')).'\',
					'.$time_now
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			$new_pid = $DBLayer->insert_id();
	
			$query = array(
				'UPDATE'	=> 'sm_messenger_topics',
				'SET'		=> '
					last_short_message=\''.$DBLayer->escape($message).'\',
					last_posted=\''.$DBLayer->escape($time_now).'\',
					last_sender_id=\''.$DBLayer->escape($User->get('id')).'\',
					last_sender_name=\''.$DBLayer->escape($User->get('realname')).'\',
					last_receiver_id=\''.$DBLayer->escape($receiver_id).'\',
					last_receiver_name=\''.$DBLayer->escape($user_info['realname']).'\',
					last_post_id='.$new_pid,
				'WHERE'		=> 'id='.$tid,
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			
			$query = array(
				'UPDATE'	=> 'sm_messenger_users',
				'SET'		=> 'viewed=0',
				'WHERE'		=> 'topic_id='.$tid.' AND user_id!='.$User->get('id'),
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			
			$mail_subject = $topic_info['subject'];
			$mail_message = 'Hello '.$user_info['realname'].'. You have a new message from '.$User->get('realname').'.'."\n\n";
			$mail_message .= 'Message: '.$message."\n\n";
			$mail_message .= 'To reply to this message follow this link: '.$URL->link('sm_messenger_reply', $tid);
			
			if ($copy_to_email > 0)
			{
				$SwiftMailer = new SwiftMailer;
				$SwiftMailer->send($user_info['email'], $mail_subject, $mail_message);
			}
		}
		
		// Add flash message
		if ($new_pid)
			$flash_message = 'Email has been sent to: '.$user_info['email'];
		else
			$flash_message = 'Email message for '.$user_info['realname'].' has been updated.';
		
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('sm_messenger_outbox'), $flash_message);
	}
	
}

$page_param['fld_count'] = $page_param['group_count'] = $page_param['item_count'] = 0;

$Core->set_page_title('Reply to '.html_encode($user_info['realname']));
$Core->set_page_id('sm_messenger_new', 'sm_messenger');
require SITE_ROOT.'header.php';

$message = $disabled = '';
$send_email_help = 'Send copy of message to email';

if ($user_info['email_setting'] == '2')
{
	$disabled = ' disabled';
	$send_email_help = 'The recipient has disabled the receipt of messages by email.';
}
?>

<style>
.entry-content{white-space: pre-line;padding-left: 8px;}
.data-box strong{font-size: 14px;}
.brd .posthead .hn {padding-left: 8px;}
.post-number{float:right;}
</style>

<div class="main-content main-frm">
	<div class="ct-group">
		<form method="post" accept-charset="utf-8" action="<?php echo $URL->link('sm_messenger_reply', $tid) ?>">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token($URL->link('sm_messenger_reply', $tid)) ?>" />
			<fieldset class="frm-group group<?php echo ++$page_param['group_count'] ?>">
				<div class="ct-set group-item1">
					<div class="ct-box">
						<h6 class="ct-legend hn"><span>Subject:</span></h6>
						<p><span><strong><?php echo html_encode($topic_info['subject']) ?></strong></span></p>
					</div>
				</div>
				<div class="txt-set set<?php echo ++$page_param['item_count'] ?>">
					<div class="txt-box textarea required">
						<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span>Message</span><small>Write your reply</small></label>
						<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $page_param['fld_count'] ?>" name="message" rows="5" cols="95" required=""><?php echo $message ?></textarea></span></div>
					</div>
				</div>
				<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
					<div class="sf-box checkbox">
						<input type="hidden" name="copy_to_email" value="0">
						<span class="fld-input"><input type="checkbox" name="copy_to_email" value="1" checked="checked" <?php echo $disabled ?>></span>
						<label for="fld<?php echo $page_param['fld_count'] ?>"><span></span><?php echo $send_email_help ?></label>
					</div>
				</div>
			</fieldset>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="sent" value="Send"></span>
			</div>
		</form>
<?php

	$page_param['item_count'] = 0;
	foreach ($messenger_info as $cur_post)
	{
		++$page_param['item_count'];

		$page_param['message'] = html_encode($cur_post['p_message']);
		
		// Generate the post heading
		$page_param['post_ident'] = array();
		$page_param['post_ident']['num'] = '<span class="post-number" id="post'.$cur_post['id'].'">#'.$cur_post['id'].'</span>';
		$page_param['post_ident']['user'] = '<span class="post-byline"><a href="'.$URL->link('user', $cur_post['p_sender_id']).'">'.html_encode($cur_post['p_sender_name']).'</a></span>';
		$page_param['post_ident']['link'] = '<span class="post-link">'.format_time($cur_post['p_posted']).'</span>';

?>
	<div class="post<?php if ($page_param['item_count'] == 1) echo ' firstpost' ?>">
		<div class="posthead">
			<h6 class="hn post-ident"><?php echo implode(' ', $page_param['post_ident']) ?></h6>
		</div>
		<div class="postbody">
			<div class="post-entry">
				<div class="entry-content">
					<?php echo $page_param['message']."\n" ?>
				</div>
			</div>
		</div>
	</div>
<?php
	}
?>
	</div>
</div>

<?php
require SITE_ROOT.'footer.php';