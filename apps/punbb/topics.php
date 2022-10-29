<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if ($User->get('g_read_board') == '0')
	message($lang_common['No view']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$search_query = [];

if ($id > 0)
	$search_query[] = 't.forum_id='.$id;

$query = array(
	'SELECT'	=> 'COUNT(t.id)',
	'FROM'		=> 'punbb_topics AS t',
);
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

// Get topics
$query = array(
	'SELECT'	=> 't.*, f.forum_name',
	'FROM'		=> 'punbb_topics AS t',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'punbb_forums AS f',
			'ON'			=> 'f.id=t.forum_id'
		),
	),
	'ORDER BY'	=> 't.last_post',
	'LIMIT'		=> $PagesNavigator->limit()
);
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$topics_info = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$topics_info[] = $row;
}
$PagesNavigator->num_items($topics_info);

$Core->set_page_id('punbb_categories', 'punbb');
require SITE_ROOT.'header.php';
?>

<div class="alert alert-primary mb-2" role="alert">
	<h1 class="card-title mb-0"><?php echo html_encode($Config->get('o_board_title')) ?></h1>
	<hr class="row my-1">
	<p><?php echo html_encode($Config->get('o_board_desc')) ?></p>
</div>

<?php
$topics_header = 'List of all topics';
if ($id > 0)
{
	$query = array(
		'SELECT'	=> 'f.*, c.*',
		'FROM'		=> 'punbb_forums AS f',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'punbb_categories AS c',
				'ON'			=> 'c.id=f.cat_id'
			),
		),
		'WHERE'		=> 'f.id='.$id
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$forum_info = $DBLayer->fetch_assoc($result);
	$topics_header = html_encode($forum_info['forum_name']);
}
?>

<div class="card-header">
	<h5 class="card-title mb-0"><?php echo $topics_header ?></h5>
</div>
<div class="sub-header">
	<div class="row">
		<div class="col-6">
			<p class="px-3 mb-0">Topics</p>
		</div>
		<div class="col-1">
			<p class="mb-0 min-100">Replies</p>
		</div>
		<div class="col-1">
			<p class="mb-0 min-100">Views</p>
		</div>
		<div class="col-3">
			<p class="mb-0 min-200">Last post</p>
		</div>
	</div>
</div>

<?php
if (!empty($topics_info))
{
	foreach($topics_info as $cur_topic)
	{
?>

<div class="border">
	<div class="row px-2 py-1">
		<div class="col-6">
			<h6 class="card-title mb-0"><a href="<?php echo $URL->link('punbb_posts', $cur_topic['id']) ?>"><?php echo html_encode($cur_topic['subject']) ?></a></h6>
			<p class="card-text pb-0">by <?php echo html_encode($cur_topic['poster']) ?></p>
		</div>
		<div class="col-1 min-100">
			<p class="align-middle"><?php echo $cur_topic['num_replies'] ?></p>
		</div>
		<div class="col-1 min-100">
			<p><?php echo $cur_topic['num_views'] ?></p>
		</div>
		<div class="col-3 min-200">
			<a href=""><?php echo format_time($cur_topic['last_post']) ?></a>
			<p>by <?php echo html_encode($cur_topic['last_poster']) ?></p>
		</div>	
	</div>
</div>

<?php
	}
}
require SITE_ROOT.'footer.php';
