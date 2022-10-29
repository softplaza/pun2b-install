<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

$url_params = isset($_GET['params']) ? swift_trim($_GET['params']) : '';
if ($url_params != '')
{
//	$params = explode();
	$url_to_uids = isset($_GET['to_uids']) ? swift_trim($_GET['to_uids']) : '';
	$url_subject = isset($_GET['subject']) ? $_GET['subject'] : '';
	$url_message = isset($_GET['message']) ? $_GET['message'] : '';
	
	$param = [];
	$param[] = 'subject='.$url_subject;
	$param[] = 'message='.$url_message;
	
	$coded = base64_encode(implode('&', $param));
	//echo base64_decode($coded);
}
$url_to_emails = isset($_GET['to_emails']) ? swift_trim($_GET['to_emails']) : '';

$query = array(
	'SELECT'	=> 'u.id, u.realname, u.email, u.group_id, u.email_setting, g.g_id, g.g_title',
	'FROM'		=> 'groups AS g',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'g.g_id=u.group_id'
		)
	),
	'WHERE'		=> 'u.id > 2 AND u.id!='.$User->get('id'),
	'ORDER BY'	=> 'g.g_id, u.realname',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users_info = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$users_info[$fetch_assoc['id']] = $fetch_assoc;
}

if (isset($_POST['sent']))
{
	$receiver_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
	$receiver_name = isset($users_info[$receiver_id]) ? $users_info[$receiver_id]['realname'] : '';
	$receiver_email = isset($users_info[$receiver_id]) ? $users_info[$receiver_id]['email'] : '';
	$receiver_email_setting = isset($users_info[$receiver_id]) ? $users_info[$receiver_id]['email_setting'] : 2;
	$user_email = isset($_POST['user_email']) ? swift_trim($_POST['user_email']) : '';
	$mail_subject = isset($_POST['subject']) ? swift_trim($_POST['subject']) : '';
	$mail_message = isset($_POST['message']) ? swift_trim($_POST['message']) : '';
	$copy_to_email = isset($_POST['copy_to_email']) ? intval($_POST['copy_to_email']) : 0;
	
	if ($receiver_id == 0 && $user_email == '')
		$Core->add_error('No sender selected.');
	if ($receiver_name == '' && $user_email == '')
		$Core->add_error('The sender\'s name is empty or does not exist.');
	if ($mail_message == '')
		$Core->add_error('Message field cannot be empty.');
	
	if ($user_email != '') {
		$receiver_id = 0;
		$receiver_email = $user_email;
		$receiver_email_setting = 1;
	}
	
	$time_now = time();
	if (empty($Core->errors))
	{
		if ($receiver_id > 0)
		{
			$query = array(
				'INSERT'	=> 'last_short_message, last_sender_id, last_sender_name, last_receiver_id, last_receiver_name, last_posted',
				'INTO'		=> 'sm_messenger_topics',
				'VALUES'	=> 
					'\''.$DBLayer->escape($mail_message).'\',
					\''.$DBLayer->escape($User->get('id')).'\',
					\''.$DBLayer->escape($User->get('realname')).'\',
					\''.$DBLayer->escape($receiver_id).'\',
					\''.$DBLayer->escape($receiver_name).'\',
					'.$time_now.''
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			$new_tid = $DBLayer->insert_id();
			
			if ($new_tid)
			{
				$query = array(
					'INSERT'	=> 'p_topic_id, p_message, p_sender_id, p_sender_name, p_posted',
					'INTO'		=> 'sm_messenger_posts',
					'VALUES'	=> 
						'\''.$DBLayer->escape($new_tid).'\',
						\''.$DBLayer->escape($mail_message).'\',
						\''.$DBLayer->escape($User->get('id')).'\',
						\''.$DBLayer->escape($User->get('realname')).'\',
						'.$time_now
				);
				$DBLayer->query_build($query) or error(__FILE__, __LINE__);
				$new_pid = $DBLayer->insert_id();
				
				if ($mail_subject == '')
					$mail_subject = 'Topic_'.$new_tid;
				
				$query = array(
					'UPDATE'	=> 'sm_messenger_topics',
					'SET'		=> 'subject=\''.$DBLayer->escape($mail_subject).'\', 
						last_post_id='.$new_pid,
					'WHERE'		=> 'id='.$new_tid,
				);
				$DBLayer->query_build($query) or error(__FILE__, __LINE__);
				
				$query = array(
					'INSERT'	=> 'user_id, topic_id, viewed',
					'INTO'		=> 'sm_messenger_users',
					'VALUES'	=> 
						'\''.$DBLayer->escape($User->get('id')).'\',
						\''.$DBLayer->escape($new_tid).'\', 1'
				);
				$DBLayer->query_build($query) or error(__FILE__, __LINE__);
				
				$query = array(
					'INSERT'	=> 'user_id, topic_id',
					'INTO'		=> 'sm_messenger_users',
					'VALUES'	=> 
						'\''.$DBLayer->escape($receiver_id).'\',
						\''.$DBLayer->escape($new_tid).'\''
				);
				$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			}
		}
		
		$mail_intro = [];
		if ($receiver_name != '')
			$mail_intro[] = 'Hello '.$receiver_name.'.';
		$mail_intro[] = 'You have a new message from '.$User->get('realname').'.';
		$mail_intro[] = 'Message: '.$mail_message;
		
		if (isset($new_tid) && $new_tid > 0)
			$mail_message .= 'To reply to this message follow this link: '.$URL->link('sm_messenger_reply', $new_tid);
		
		if ($copy_to_email == 1)
		{
			$SwiftMailer = new SwiftMailer;
			$SwiftMailer->isHTML();
			$SwiftMailer->send($user_email, $mail_subject, implode("\n\n", $mail_intro));
		}

		// Add flash message
		$flash_message = 'Email has been sent to: '.$receiver_email;
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('sm_messenger_outbox'), $flash_message);
	}
}

$page_param['fld_count'] = $page_param['group_count'] = $page_param['item_count'] = 0;

$Core->set_page_title('Create a new message');
$Core->set_page_id('sm_messenger_new', 'sm_messenger');
require SITE_ROOT.'header.php';
?>

<div class="main-content main-frm">
	<div class="ct-group">
		<form method="post" accept-charset="utf-8" action="">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			<fieldset class="frm-group group<?php echo ++$page_param['group_count'] ?>">
				<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
					<div class="sf-box select required">
						<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span>Send to</span><small></small></label><br>
						<span class="fld-input"><select id="user_id" name="user_id" onchange="enterManually()" required>
<?php
$optgroup = 0;
echo '<option value="0" selected="selected" disabled>Select an user</option>'."\n";
echo '<option value="0">Enter Email Manually</option>'."\n";

foreach ($users_info as $cur_user)
{
	if ($cur_user['group_id'] != $optgroup) {
		if ($optgroup) {
			echo '</optgroup>';
		}
		echo '<optgroup label="'.html_encode($cur_user['g_title']).'">';
		$optgroup = $cur_user['group_id'];
	}
	
	if ($cur_user['email_setting'] == 2)
		echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'" disabled style="color:red">'.html_encode($cur_user['realname']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'">'.html_encode($cur_user['realname']).'</option>'."\n";
}
?>
						</select></span>
					</div>
				</div>
				<div class="sf-set set<?php echo ++$page_param['item_count'] ?>" id="user_email" <?php echo ($url_to_emails == '' ? 'style="display:none"' : '') ?>>
					<div class="sf-box text required">
						<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span>Email</span></label><br>
						<span class="fld-input"><input type="email" id="fld<?php echo $page_param['fld_count'] ?>" name="user_email" value="<?php echo (isset($url_to_emails) ? $url_to_emails : '') ?>" size="35" maxlength="35"></span>
					</div>
				</div>
				<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span>Subject</span></label><br>
						<span class="fld-input"><input type="text" id="fld<?php echo $page_param['fld_count'] ?>" name="subject" value="<?php echo (isset($url_subject) ? $url_subject : '') ?>" size="35" maxlength="255"></span>
					</div>
				</div>
				<div class="txt-set set<?php echo ++$page_param['item_count'] ?>">
					<div class="txt-box textarea required">
						<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span>Message</span></label>
						<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $page_param['fld_count'] ?>" name="message" rows="10" cols="95" required=""><?php echo (isset($url_message) ? $url_message : '') ?></textarea></span></div>
					</div>
				</div>
				<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
					<div class="sf-box checkbox">
						<input type="hidden" name="copy_to_email" value="0">
						<span class="fld-input"><input type="checkbox" name="copy_to_email" value="1" checked="checked"></span>
						<label for="fld<?php echo $page_param['fld_count'] ?>"><span></span>Send copy of message to email</label>
					</div>
				</div>
			</fieldset>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="sent" value="Send"></span>
			</div>
		</form>
	</div>
</div>

<script>
function enterManually(){
	var v = $("#user_id").val();
	if(v == 0){
		$("#user_email input").val("");
		$("#user_email").css("display","block");
	}else{
		$("#user_email").css("display","none");
		$("#user_email input").val("");
	}
}
</script>

<?php
require SITE_ROOT.'footer.php';