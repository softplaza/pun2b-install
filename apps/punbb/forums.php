<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if ($User->get('g_read_board') == '0')
	message($lang_common['No view']);

$query = [
	'SELECT'	=> 'c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.forum_desc, f.redirect_url, f.moderators, f.num_topics, f.num_posts, f.last_post, f.last_post_id, f.last_poster',
	'FROM'		=> 'punbb_categories AS c',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'punbb_forums AS f',
			'ON'			=> 'c.id=f.cat_id'
		],
		[
			'LEFT JOIN'		=> 'punbb_forum_perms AS fp',
			'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.$User->get('g_id').')'
		]
	],
	'WHERE'		=> 'fp.read_forum IS NULL OR fp.read_forum=1',
	'ORDER BY'	=> 'c.disp_position, c.id, f.disp_position'
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$categories = $forums = [];
while ($row = $DBLayer->fetch_assoc($result))
{
	if (!isset($categories[$row['cid']]))
		$categories[$row['cid']] = $row;

	$forums[] = $row;
}

$Core->set_page_id('punbb_categories', 'punbb');
require SITE_ROOT.'header.php';
?>

<div class="alert alert-primary mb-2" role="alert">
	<h1 class="card-title mb-0"><?php echo html_encode($Config->get('o_board_title')) ?></h1>
	<hr class="row my-1">
	<p><?php echo html_encode($Config->get('o_board_desc')) ?></p>
</div>

<?php
if (!empty($categories))
{
	foreach($categories as $cur_category)
	{
?>

<div class="card-header">
	<h5 class="card-title mb-0"><?php echo html_encode($cur_category['cat_name']) ?></h5>
</div>
<div class="sub-header">
	<div class="row">
		<div class="col-6">
			<p class="px-3 mb-0">Forums</p>
		</div>
		<div class="col-1">
			<p class="mb-0 min-100">Topics</p>
		</div>
		<div class="col-1">
			<p class="mb-0 min-100">Posts</p>
		</div>
		<div class="col-3">
			<p class="mb-0 min-200">Last post</p>
		</div>
	</div>
</div>

<?php
		if (!empty($forums))
		{
			foreach($forums as $cur_forum)
			{
				if ($cur_category['cid'] == $cur_forum['cid'])
				{
?>

<div class="card-body border py-1">
	<div class="row">
		<div class="col-6">
			<h6 class="card-title mb-1"><a href="<?php echo $URL->link('punbb_topics', $cur_forum['fid']) ?>"><?php echo html_encode($cur_forum['forum_name']) ?></a></h6>
			<p class="card-text py-0"><?php echo $cur_forum['forum_desc'] ?></p>
		</div>
		<div class="col-1 min-100">
			<p class="align-middle"><?php echo $cur_forum['num_topics'] ?></p>
		</div>
		<div class="col-1 min-100">
			<p><?php echo $cur_forum['num_posts'] ?></p>
		</div>
		<div class="col-3 min-200">
			<p><?php echo format_time($cur_forum['last_post']) ?></p>
		</div>	
	</div>
</div>

<?php
				}
			}
		}
	}
}
require SITE_ROOT.'footer.php';
