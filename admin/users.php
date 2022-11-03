<?php
/**
 * @copyright (C) 2020 SwiftManager.Org, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package SwiftManager
 */

define('SITE_ROOT', '../');
require SITE_ROOT.'include/common.php';

if ($User->get('g_view_users') != '1')
	message($lang_common['No permission']);

$search_by_realname = (isset($_GET['realname']) && is_string($_GET['realname'])) ? swift_trim($_GET['realname']) : '';
$search_by_group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
$sort_by = isset($_GET['sort_by']) ? intval($_GET['sort_by']) : 0;

$where_sql = $order_by = [];
$like_command = ($db_type == 'pgsql') ? 'ILIKE' : 'LIKE';

if (!$User->is_admin())
	$where_sql[] = 'u.id > 2';
if ($search_by_realname != '')
	$where_sql[] = 'u.realname '.$like_command.' \''.$DBLayer->escape('%'.$search_by_realname.'%').'\'';
if ($search_by_group_id > 0)
	$where_sql[] = 'u.group_id='.$search_by_group_id;

if ($sort_by == 1)
	$order_by[] = 'u.username DESC';
else if ($sort_by == 2)
	$order_by[] = 'u.last_visit';
else if ($sort_by == 3)
	$order_by[] = 'u.last_visit DESC';

// Fetch user count
$query = array(
	'SELECT'	=> 'COUNT(u.id)',
	'FROM'		=> 'users AS u',
);
if (!empty($where_sql))
	$query['WHERE'] = implode(' AND ', $where_sql);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$Core->set_page_id('admin_users', 'users');
require SITE_ROOT.'header.php';

// Grab the users
$query = array(
	'SELECT'	=> 'u.*, g.g_id, g.g_user_title',
	'FROM'		=> 'users AS u',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'groups AS g',
			'ON'			=> 'g.g_id=u.group_id'
		)
	),
	'ORDER BY'	=> 'u.username',
	'LIMIT'		=> $PagesNavigator->limit(),
);

if (!empty($where_sql))
	$query['WHERE'] = implode(' AND ', $where_sql);

if (!empty($order_by))
	$query['ORDER BY'] = implode(', ', $order_by);

$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$founded_user_datas = array();
while ($user_data = $DBLayer->fetch_assoc($result))
{
	$founded_user_datas[] = $user_data;
}
$PagesNavigator->num_items($founded_user_datas);

$query = array(
	'SELECT'	=> 'g.g_id, g.g_title',
	'FROM'		=> 'groups AS g',
//	'WHERE'		=> 'g.g_id > 2',
	'ORDER BY'	=> 'g.g_title'
);

if (!$User->is_admin())
	$query['WHERE'] = 'g.g_id > 2';

$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$groups_info = array();
while ($cur_group = $DBLayer->fetch_assoc($result)) {
	$groups_info[] = $cur_group;
}

?>
<nav class="navbar search-bar">
	<form method="get" accept-charset="utf-8" action="" class="d-flex">
		<div class="container-fluid justify-content-between">
			<div class="row">
				<div class="col">
					<select name="group_id" class="form-select-sm">
						<option value="">Show all grous</option>
<?php
foreach ($groups_info as $cur_group)
{
	if ($cur_group['g_id'] == $search_by_group_id)
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.html_encode($cur_group['g_title']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.html_encode($cur_group['g_title']).'</option>'."\n";
}
?>
					</select>
				</div>
				<div class="col">
					<input name="realname" type="text" value="<?php echo isset($_GET['realname']) ? html_encode($_GET['realname']) : '' ?>" placeholder="Username" class="form-control-sm"/>
				</div>
				<div class="col">
					<select name="sort_by" class="form-select-sm">
						<option value="">Sort by...</option>
<?php
$sort_by_options = [0 => 'Username A-Z', 1 => 'Username Z-A', 2 => 'Last visit 0-9', 3 => 'Last visit 9-0',];
foreach ($sort_by_options as $key => $val)
{
	if ($key == $sort_by)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected="selected">'.$val.'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$val.'</option>'."\n";
}
?>
					</select>
				</div>
				<div class="col">
					<button type="submit" class="btn btn-sm btn-outline-success">Search</button>
					<a href="<?php echo $URL->link('admin_users') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
				</div>
			</div>
		</div>
	</form>	
</nav>

<?php
if (!empty($founded_user_datas))
{
?>
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<table class="table table-striped my-0">
			<thead>
				<tr>
					<th>Print name</th>
					<th>Email</th>
					<th>Phones</th>
					<th>Group</th>
					<th>Last visit</th>
					<th>Registered</th>
				</tr>
			</thead>
			<tbody>
<?php
	foreach ($founded_user_datas as $user_data)
	{
		$td = [];
		$td[] = '<td>';
		$td[] = ($user_data['realname'] != '') ? '<a href="'.$URL->link('user', $user_data['id']).'" class="fw-bold">'.html_encode($user_data['realname']).'</a>' : '<a href="'.$URL->link('user', $user_data['id']).'" class="fw-bold">'.html_encode($user_data['username']).'</a>';

		if ($User->is_admmod())
			$td[] = '<a href="'.$URL->link('profile_invite', $user_data['id']).'" class="badge bg-primary text-white float-end">Invite</a>';

		$td[] = '</td>';

		$td[] = '<td>'.html_encode($user_data['email']).'</td>';

		if ($user_data['work_phone'] != '')
			$td[] = '<td>Work: <strong><a href="tel:'.html_encode($user_data['work_phone']).'">'.html_encode($user_data['work_phone']).'</a></strong></td>';
		else if ($user_data['cell_phone'] != '')
			$td[] = '<td>Cell: <strong><a href="tel:'.html_encode($user_data['cell_phone']).'">'.html_encode($user_data['cell_phone']).'</a></strong></td>';
		else if ($user_data['home_phone'] != '')
			$td[] = '<td>Home: <strong><a href="tel:'.html_encode($user_data['home_phone']).'">'.html_encode($user_data['home_phone']).'</a></strong></td>';
		else
			$td[] = '<td></td>';

		$td[] = '<td>'.get_title($user_data).'</td>';

		$td[] = '<td>'.($user_data['last_visit'] > $user_data['registered'] ? format_time($user_data['last_visit'], 1) : '').'</td>';

		$td[] = '<td>'.format_time($user_data['registered'], 1).'</td>';
?>
				<tr>
					<?php echo implode("\n\t\t\t\t\t\t", $td)."\n" ?>
				</tr>
<?php
	}
?>
			</tbody>
		</table>
	</form>
<?php
}
else
{
?>
	<div class="alert alert-warning" role="alert">No items on this page</div>
<?php
}
require SITE_ROOT.'footer.php';