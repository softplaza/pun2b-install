<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($User->get('g_read_board') == '0' || $id < 1)
	message($lang_common['No view']);

$query = array(
	'SELECT'	=> 'p.*, t.id AS tid, t.subject, t.first_post_id',
	'FROM'		=> 'punbb_posts AS p',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'punbb_topics AS t',
			'ON'			=> 't.id=p.topic_id'
		),
	),
	'WHERE'		=> 'p.id='.$id
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$post_info = $DBLayer->fetch_assoc($result);

if (isset($_POST['save']))
{
	$post_data = ['message'		=> isset($_POST['message']) ? swift_trim($_POST['message']) : ''];
	$topic_data = ['subject'	=> isset($_POST['subject']) ? swift_trim($_POST['subject']) : ''];

	if ($post_data['message'] == '')
		$Core->add_error('Message can not be empty.');
	if (isset($_POST['subject']) && $topic_data['subject'] == '')
		$Core->add_error('Subject can not be empty.');

	if (empty($Core->errors))
	{
		$DBLayer->update('punbb_posts', $post_data, $id);

		if (isset($_POST['subject']))
			$DBLayer->update('punbb_topics', $topic_data, $post_info['tid']);

		// Add flash message
		$flash_message = 'Message has been saved.';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('punbb_posts', $post_info['tid']), $flash_message);
	}
}

$Core->set_page_id('punbb_categories', 'punbb');
require SITE_ROOT.'header.php';
?>
<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
	<div class="card">
		<div class="card-header">
			<h6 class="mb-0">Edit post</h6>
		</div>
		<div class="card-body">
<?php if ($post_info['first_post_id'] == $id): ?>
			<div class="mb-3">
				<label class="form-label" for="fld_subject">Subject</label>
				<input type="text" name="subject" value="<?php echo (isset($_POST['subject']) ? html_encode($_POST['subject']) : html_encode($post_info['subject'])) ?>" class="form-control" id="fld_subject">
			</div>
<?php endif; ?>
			<div class="mb-3">
				<label class="form-label" for="fld_message">Message</label>
				<textarea type="text" name="message" class="form-control" id="fld_message"><?php echo (isset($_POST['message']) ? html_encode($_POST['message']) : html_encode($post_info['message'])) ?></textarea>
			</div>
			<button type="submit" name="save" class="btn btn-primary">Save changes</button>
		</div>
	</div>
</form>
<?php
require SITE_ROOT.'footer.php';
