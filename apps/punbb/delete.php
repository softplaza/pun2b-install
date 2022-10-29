<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$tid = isset($_GET['tid']) ? intval($_GET['tid']) : 0;
$pid = isset($_GET['pid']) ? intval($_GET['pid']) : 0;

$access = ($User->is_admin()) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$topic_id = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;
$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

$query = array(
	'SELECT'	=> 'p.*, t.id AS tid, t.subject, t.forum_id, t.first_post_id',
	'FROM'		=> 'punbb_posts AS p',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'punbb_topics AS t',
			'ON'			=> 't.id=p.topic_id'
		),
	),
);
if ($tid > 0) $query['WHERE'] = 'p.topic_id='.$tid;
else if ($pid > 0) $query['WHERE'] = 'p.id='.$pid;
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$post_info = $DBLayer->fetch_assoc($result);

if (isset($_POST['delete_topic']))
{
	if ($topic_id > 0)
	{
		$query = array(
			'DELETE'		=> 'punbb_topics',
			'WHERE'			=> 'id='.$topic_id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		$query = array(
			'DELETE'		=> 'punbb_posts',
			'WHERE'			=> 'topic_id='.$topic_id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		$flash_message = 'Topic deleted';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('punbb_topics', $post_info['forum_id']), $flash_message);
	}
}
else if (isset($_POST['delete_post']))
{
	if ($post_id > 0)
	{
		$query = array(
			'DELETE'		=> 'punbb_posts',
			'WHERE'			=> 'id='.$post_id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		//$DBLayer->update('punbb_topics', $post_data, $id);
		//$DBLayer->update('punbb_forums', $post_data, $id);

		$flash_message = 'Post deleted';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('punbb_posts', $post_info['tid']), $flash_message);
	}
}
else if (isset($_POST['cancel']))
{
	$flash_message = 'Action has been canceled';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('punbb_posts', $post_info['tid']), $flash_message);
}

$Core->set_page_id('punbb_categories', 'punbb');
require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">

<?php if ($tid > 0): ?>
	<input type="hidden" name="topic_id" value="<?php echo $tid ?>">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="card-header">
				<h5 class="modal-title">Delete topic</h5>
			</div>
			<div class="modal-body">
				<div class="alert alert-danger" role="alert"><strong>Attention!</strong> This action will remove all posts of topic.</div>
				<h6 class=""><span><?php echo html_encode($post_info['subject']) ?></span></h6>
				<p>Posted by <?php echo html_encode($post_info['poster']) ?></p>
			</div>
			<div class="modal-footer">
				<button type="submit" name="delete_topic" class="btn btn-danger">Delete</button>
				<button type="submit" name="cancel" class="btn btn-secondary">Cancel</button>
			</div>
		</div>
	</div>
<?php elseif ($pid > 0):
	require SITE_ROOT.'apps/punbb/inc/parser.php';
?>
	<input type="hidden" name="post_id" value="<?php echo $pid ?>">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="card-header">
				<h5 class="modal-title">Delete post</h5>
			</div>
			<div class="modal-body">
				<div class="alert alert-danger" role="alert"><strong>Attention!</strong> This action will remove post.</div>
				<h6 class=""><span><?php echo html_encode($post_info['subject']) ?></span></h6>
				<p><?php echo parse_message($post_info['message'], 0) ?></p>
				<p>Posted by <?php echo html_encode($post_info['poster']) ?></p>
			</div>
			<div class="modal-footer">
				<button type="submit" name="delete_post" class="btn btn-danger">Delete</button>
				<button type="submit" name="cancel" class="btn btn-secondary">Cancel</button>
			</div>
		</div>
	</div>
<?php endif; ?>

</form>

<?php
require SITE_ROOT.'footer.php';
