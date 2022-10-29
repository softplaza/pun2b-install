<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

$Messenger = new Messenger;

if (isset($_POST['delete']))
{
	$checked_messages = isset($_POST['checked_messages']) ? array_values($_POST['checked_messages']) : '';
	
	if (!empty($checked_messages))
	{
		$query = array(
			'DELETE'	=> 'sm_messenger_users',
			'WHERE'		=> 'topic_id IN('.implode(',', $checked_messages).') AND user_id='.$User->get('id')
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		// Delete empty conversation
		$Messenger->delete_empty_topics();

		// Add flash message
		$flash_message = 'Selected messages deleted.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

$jquery_checkboxes = '$("#msg_delete_all").click(function () {
	$("input:checkbox").prop("checked", this.checked);
});';
$Loader->add_js($jquery_checkboxes, array('type' => 'inline', 'weight' => 200, 'group' => SPM_JS_GROUP_SYSTEM));

$query = array(
	'SELECT'	=> 't.*, u.viewed',
	'FROM'		=> 'sm_messenger_topics AS t',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'sm_messenger_users AS u',
			'ON'			=> 't.id=u.topic_id'
		)
	),
	'WHERE'		=> 'u.user_id='.$User->get('id'),
	'ORDER BY'	=> 't.last_post_id DESC'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$topic_ids = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$topic_ids[] = $fetch_assoc['id'];
}

$topics_info = array();
$query = array(
	'SELECT'	=> 'p.*, t.*',
	'FROM'		=> 'sm_messenger_posts AS p',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'sm_messenger_topics AS t',
			'ON'			=> 't.id=p.p_topic_id'
		),
	),
	'ORDER BY'	=> 'p.p_posted DESC'
);
if (!empty($topic_ids))
{
	$query['WHERE'] = 't.id IN('.implode(',', $topic_ids).') AND p.p_sender_id='.$User->get('id');
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		$topics_info[$fetch_assoc['id']] = $fetch_assoc;
	}
}

$query = array(
	'SELECT'	=> 'u.*',
	'FROM'		=> 'sm_messenger_users AS u',
);
$user_statuses = array();
if (!empty($topic_ids))
{
	$query['WHERE'] = 'u.topic_id IN('.implode(',', $topic_ids).') AND u.user_id!='.$User->get('id');
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		if ($fetch_assoc['viewed'] == 1)
			$user_statuses[$fetch_assoc['topic_id']] = $fetch_assoc['viewed'];
	}
}

$Core->set_page_title('Sent Messages');
$Core->set_page_id('sm_messenger_outbox', 'sm_messenger');
require SITE_ROOT.'header.php';

?>
<style>
table{table-layout: initial;}
.msg_list td{padding: .3em;}
.msg_list .td_1, .msg_list .td_2 {width: 2.3em;min-width: 2em;text-align: center;padding-left: 0;padding-right: 0;vertical-align: middle;}
.msg_list .td_3, .msg_list .td_5{width: 100px;min-width: 80px;}
.msg_list .td_4 {min-width: 15em;}
.msg_list .td_5{}
.unreaded-msgs{font-weight: bold;}
.reply{float: right;padding-right: 1em;}
</style>

<div class="main-content main-frm">
<?php
if (!empty($topics_info)) 
{
?>
	<div class="ct-group">
		<form method="post" accept-charset="utf-8" action="">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			<table class="msg_list">
				<thead>
					<tr>
						<th><input id="msg_delete_all" type="checkbox" name="delete_all" value="" class=""></th>
						<th><img src="<?php echo $URL->link('sm_messenger'.'img/sent.png') ?>"></th>
						<th>Sent to</th>
						<th>Subject</th>
						<th>Last edited</th>
					</tr>
				</thead>
				<tbody>
<?php
	foreach($topics_info as $cur_topic)
	{
		$cur_info = array();
		$cur_info['viewed'] = isset($user_statuses[$cur_topic['id']]) ? $user_statuses[$cur_topic['id']] : 0;
		$cur_info['unread'] = ($cur_info['viewed'] == 0) ? ' unreaded-msgs' : '';
		$cur_info['receiver'] = ($User->get('id') == $cur_topic['last_receiver_id']) ? '<a href="'.$URL->link('user', $cur_topic['last_sender_id']).'">'.$cur_topic['last_sender_name'].'</a>' : '<a href="'.$URL->link('user', $cur_topic['last_receiver_id']).'">'.$cur_topic['last_receiver_name'].'</a>';
		
		$cur_info['subject'] = ($cur_topic['subject'] != '') ? html_encode($cur_topic['subject']) : '[no subject]';
		$cur_info['topic'] = '<a href="'.$URL->link('sm_messenger_reply', $cur_topic['id']).'"><span>'.$cur_info['subject'].'</span></a> - <span class="short-message">'.substr(html_encode($cur_topic['last_short_message']), 0, 80).'...'.'</span>';
		$cur_info['img_status'] = ($cur_info['viewed'] == 0) ? '<img src="'.$URL->link('sm_messenger'.'img/sent.png').'">' : '<img src="'.$URL->link('sm_messenger'.'img/read.png').'">';
		
?>
					<tr class="">
						<td class="td_1"><input type="checkbox" name="checked_messages[]" value="<?php echo $cur_topic['id'] ?>"></td>
						<td class="td_2"><?php echo $cur_info['img_status'] ?></td>
						<td class="td_3"><?php echo $cur_info['receiver'] ?></td>
						<td class="td_4"><?php echo $cur_info['topic'] ?></span></td>
						<td class="td_5"><?php echo format_time($cur_topic['last_posted']) ?></td>
					</tr>
<?php
	}
?>
				</tbody>
			</table>
			<div class="frm-buttons">
				<span class="submit primary caution"><input type="submit" name="delete" value="Delete" /></span>
			</div>
		</form>
	</div>
<?php

} else {
	
?>
	<div class="ct-box warn-box">
		<p>This outbox is empty.</p>
	</div>
<?php
}
?>
</div>
<?php
require SITE_ROOT.'footer.php';