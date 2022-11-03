<?php
/**
 * @copyright (C) 2020 SwiftManager.Org, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftManager
 */

define('SITE_ROOT', '../');
require SITE_ROOT.'include/common.php';

if (!$User->is_admmod())
	message($lang_common['No permission']);

$query = array(
	'SELECT'	=> 'o.user_id, o.ident',
	'FROM'		=> 'online AS o',
	'WHERE'		=> 'o.idle=0',
	'ORDER BY'	=> 'o.ident'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);

$page_param['num_guests'] = $page_param['num_users'] = 0;
$users = array();
while ($user_online = $DBLayer->fetch_assoc($result))
{
	if ($user_online['user_id'] > 1)
	{
		$users[] = ($User->get('g_view_users') == '1') ? '<a href="'.$URL->link('user', $user_online['user_id']).'">'.html_encode($user_online['ident']).'</a>' : html_encode($user_online['ident']);
		++$page_param['num_users'];
	}
	else
		++$page_param['num_guests'];
}

$query = array(
	'SELECT'	=> 'o.user_id, o.ident, o.logged, o.idle',
	'FROM'		=> 'online AS o',
	'WHERE'		=> 'o.user_id>1',
	'ORDER BY'	=> 'o.logged DESC'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$last_visits_users_online = array();
while ($rows = $DBLayer->fetch_assoc($result)) {
	$last_visits_users_online[$rows['user_id']] = array(
		'id'			=> $rows['user_id'],
		'username'		=> $rows['ident'],
		'last_visit'	=> $rows['logged'],
		'idle'			=> $rows['idle'],
	);
}

$query = array(
	'SELECT'	=> 'u.id, u.username, u.last_visit',
	'FROM'		=> 'users AS u',
	'WHERE'		=> 'u.last_visit>'.(time() - 604800),	//Last Week
	'ORDER BY'	=> 'u.last_visit DESC'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$last_visits_users_week = array();
while ($rows = $DBLayer->fetch_assoc($result))
{
	if (!isset($last_visits_users_online[$rows['id']]))
		$last_visits_users_week[$rows['id']] = array(
			'id'			=> $rows['id'],
			'username'		=> $rows['username'],
			'last_visit'	=> $rows['last_visit'],
			'idle'			=> 1,
	);
}

$last_visits_users = array_merge($last_visits_users_online, $last_visits_users_week);
$count_users_week = count($last_visits_users);

if ($count_users_week > 0)
{
	$last_visits = $last_visits_week = array();
	foreach ($last_visits_users as $key => $val)
	{
		if ($User->get('g_view_users') == '1')
		{
			if ($val['last_visit'] >= (time()-86400) && ($val['idle'] == 1))
				$last_visits[] = '<a href="'.$URL->link('user', $val['id']).'">'.html_encode($val['username']).'</a>';
			
			if ($val['last_visit'] < (time()-86400))
				$last_visits_week[] = '<a href="'.$URL->link('user', $val['id']).'">'.html_encode($val['username']).'</a>';
		}
		else
		{
			if ($val['last_visit'] >= (time()-86400) && ($val['idle'] == 1))
				$last_visits[] = html_encode($val['username']);
				
			if ($val['last_visit'] < (time()-86400))
				$last_visits_week[] = html_encode($val['username']);
		}
	}
}

$Core->set_page_id('admin_index', 'management');
require SITE_ROOT.'header.php';
?>

<div class="card mb-1">
	<div class="card-header">
		<h6 class="card-title mb-0">Core information</h6>
	</div>
	<div class="card-body">
		<p>SwiftManager v. <strong><?=SPM_VERSION?></strong></p>
		<p>DataBase Revision v. <strong><?=SPM_DB_REVISION?></strong></p>
	</div>
</div>

<div class="card mb-1">
	<div class="card-header">
		<h6 class="card-title mb-0">Server information</h6>
	</div>
	<div class="card-body">
		<p>PHP v. <strong><?=phpversion()?></strong></p>
		<p><?=$DBLayer->get_version()['name']?> v.<strong><?=$DBLayer->get_version()['version']?></strong></p>
	</div>
</div>

<div class="card">
	<div class="card-header">
		<h6 class="card-title mb-0">Activity</h6>
	</div>
	<div class="card-body">
		<p>Currently online: <?php echo implode(', ', $users) ?></p>
<?php if (count($last_visits) > 0) : ?>
		<p>
			<span>Last 24 hours: (<?php echo count($last_visits) ?>)</span>
			<span><?php echo implode(', ', $last_visits) ?></span>
		</p>
<?php endif; ?>
<?php if (count($last_visits_week) > 0) : ?>
		<p>
			<span>Last week: (<?php echo count($last_visits_week) ?>)</span>
			<span><?php echo implode(', ', $last_visits_week) ?></span>
		</p>
<?php endif; ?>
	</div>
</div>

<?php
require SITE_ROOT.'footer.php';
