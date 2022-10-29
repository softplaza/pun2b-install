<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if ($User->get('g_read_board') == '0')
	message($lang_common['No view']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (isset($_POST['send']))
{
	$post_data = [
		'poster'		=> $User->get('username'),
		'poster_id'		=> $User->get('id'),
		'message'		=> isset($_POST['message']) ? swift_trim($_POST['message']) : '',
		'hide_smilies'	=> 0,
		'posted'		=> time(),
		'topic_id'		=> $id,
	];

	if ($post_data['message'] == '')
		$Core->add_error('Message can not be empty.');

	if (empty($Core->errors))
	{
		$new_id = $DBLayer->insert('punbb_posts', $post_data);

		// Add flash message
		$flash_message = 'Message sent.';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('punbb_posts', $id).'#p'.$new_id, $flash_message);
	}
}


$search_query = [];

if ($id > 0)
	$search_query[] = 'p.topic_id='.$id;

$query = array(
	'SELECT'	=> 'COUNT(p.id)',
	'FROM'		=> 'punbb_posts AS p',
);
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

// Get topics
$query = array(
	'SELECT'	=> 'p.*, t.subject, t.first_post_id, u.username, u.email',
	'FROM'		=> 'punbb_posts AS p',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'punbb_topics AS t',
			'ON'			=> 't.id=p.topic_id'
		),
		array(
			'LEFT JOIN'	=> 'users AS u',
			'ON'			=> 'u.id=p.poster_id'
		),
	),
	'ORDER BY'	=> 'p.posted',
	'LIMIT'		=> $PagesNavigator->limit()
);
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$posts_info = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$posts_info[] = $row;
}
$PagesNavigator->num_items($posts_info);

$SwiftMenu->page_actions = false;
$Core->set_page_id('punbb_posts', 'punbb');
require SITE_ROOT.'header.php';
?>

<div class="alert alert-primary mb-2" role="alert">
	<h1 class="card-title mb-0"><?php echo html_encode($Config->get('o_board_title')) ?></h1>
	<hr class="row my-1">
	<p><?php echo html_encode($Config->get('o_board_desc')) ?></p>
</div>

<?php
if ($id > 0)
{
	// Fetch some info about the topic
	$query = array(
		'SELECT'	=> 't.subject, t.first_post_id, t.closed, t.num_replies, t.sticky, f.id AS forum_id, f.forum_name, f.moderators, fp.post_replies',
		'FROM'		=> 'punbb_topics AS t',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'punbb_forums AS f',
				'ON'			=> 'f.id=t.forum_id'
			),
			array(
				'LEFT JOIN'		=> 'punbb_forum_perms AS fp',
				'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.$User->get('g_id').')'
			)
		),
		'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$id.' AND t.moved_to IS NULL'
	);
	if (!$User->is_guest() && $Config->get('o_subscriptions') == '1')
	{
		$query['SELECT'] .= ', s.user_id AS is_subscribed';
		$query['JOINS'][] = array(
			'LEFT JOIN'	=> 'punbb_subscriptions AS s',
			'ON'		=> '(t.id=s.topic_id AND s.user_id='.$User->get('id').')'
		);
	}
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$cur_topic = $DBLayer->fetch_assoc($result);

	if (!$cur_topic)
		message($lang_common['Bad request']);
?>

<div class="card-header d-flex">
	<h5 class="mb-0 flex-grow-1"><?php echo html_encode($cur_topic['subject']) ?></h5>
	<a href="<?php echo $URL->link('punbb_new_topic', $cur_topic['forum_id']) ?>" class="badge bg-info text-white">New topic</a>
</div>

<?php
}
if (!empty($posts_info))
{
	require SITE_ROOT.'apps/punbb/inc/parser.php';

	foreach($posts_info as $cur_post)
	{
		$post_actions = [];

		$post_username = ($cur_post['username'] != '') ? '<a href="'.$URL->link('user', $cur_post['poster_id']).'">'.html_encode($cur_post['username']).'</a>' : html_encode($cur_post['poster']);

		if ($cur_post['poster_id'] == $User->get('id') || $User->is_admin())
			$post_actions[] = '<a href="'.$URL->link('punbb_edit', $cur_post['id']).'" class="badge bg-primary text-white">Edit</a>';
		if ($cur_post['poster_id'] == $User->get('id') || $User->is_admin())
			$post_actions[] = ($cur_post['first_post_id'] == $cur_post['id']) ? '<a href="'.$URL->link('punbb_delete_topic', $cur_post['topic_id']).'" class="badge bg-danger text-white">Delete topic</a>' : '<a href="'.$URL->link('punbb_delete_post', $cur_post['id']).'" class="badge bg-danger text-white">Delete</a>';
?>

<div class="mb-2" id="p<?php echo $cur_post['id'] ?>">
	<div class="row mx-0">
		<div class="col-2 px-0 border position-relative min-150">
			<div class="post-user-info">
				<p class="px-3 py-1"><i class="fas fa-user-circle fa-7x text-secondary"></i></p>
				<p class="px-3 py-1 h6"><?php echo $post_username ?></p>
			</div>
			<div class="position-absolute bottom-0">
				<p class="px-3 py-1">
					<a href="mailto:<?php echo html_encode($cur_post['email']) ?>" class="badge bg-primary text-white">@ Email</a>
				</p>
			</div>
		</div>
		<div class="col-10 px-0 border">
			<div class="post-header px-3">
				<p>
					<?php echo format_time($cur_post['posted']) ?>
					<span class="float-end text-muted"><a href="<?php echo $URL->link('punbb_posts', $cur_post['topic_id']).'#p'.$cur_post['id'] ?>">#<?php echo $cur_post['id'] ?></a></span>
				</p>
			</div>
			<div class="post-body px-3">
				<?php echo parse_message($cur_post['message'], 0) ?>
			</div>
			<div class="post-footer px-3">
				<p class="float-end"><?php echo implode("\n", $post_actions) ?></p>
			</div>
		</div>
	</div>
</div>

<?php
	}
}
// $Config->get('o_quickpost') == '1' && 
if (!$User->is_guest() &&
	($cur_topic['post_replies'] == '1' || ($cur_topic['post_replies'] == '' && $User->get('g_post_replies') == '1')) &&
	($cur_topic['closed'] == '0' || $User->is_admmod()))
{
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
	<div class="card">
		<div class="card-header">
			<h6 class="mb-0">Post new reply</h6>
		</div>
		<div class="card-body">
			<div class="mb-3">
				<?php $Hooks->get_hook('PunbbForumPostsPreMessage'); ?>
				<textarea type="text" name="message" class="form-control editor" id="fld_message" data-editor><?php echo (isset($_POST['message']) ? html_encode($_POST['message']) : '') ?></textarea>
			</div>
			<button type="submit" name="send" class="btn btn-primary">Submit reply</button>
		</div>
	</div>
</form>

<?php
}
require SITE_ROOT.'footer.php';
