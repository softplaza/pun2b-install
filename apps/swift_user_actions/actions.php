<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';


if (!$User->is_admin())
	message($lang_common['No permission']);

$search_by_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$search_by_app_id = isset($_GET['app_id']) ? swift_trim($_GET['app_id']) : '';
$search_by_a_type = isset($_GET['a_type']) ? intval($_GET['a_type']) : -1;

$search_query = [];
if ($search_by_app_id != '')
	$search_query[] = 'a.a_project_id=\''.$DBLayer->escape($search_by_app_id).'\'';

if ($search_by_user_id == 1) $search_query[] = 'a.a_user_id=1';
else if ($search_by_user_id > 1) $search_query[] = 'a.a_user_id='.$search_by_user_id;

if ($search_by_a_type > -1)
	$search_query[] = 'a.a_type='.$search_by_a_type;

$query = array(
	'SELECT'	=> 'COUNT(*)',
	'FROM'		=> 'swift_user_actions AS a',
);
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$query = array(
	'SELECT'	=> 'a.*, u.realname',
	'FROM'		=> 'swift_user_actions AS a',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'users AS u',
			'ON'			=> 'u.id=a.a_user_id'
		)
	),
	'ORDER BY'	=> 'a.a_time DESC',
	'LIMIT'		=> $PagesNavigator->limit(),
);
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$swift_user_actions = [];

while ($row = $DBLayer->fetch_assoc($result))
{
	$swift_user_actions[] = $row;
}

$PagesNavigator->num_items($swift_user_actions);

$Core->set_page_title('Actions of Users');
$Core->set_page_id('swift_user_actions', 'admin');

require SITE_ROOT.'header.php';
?>

<style>
.accordion-body {overflow-wrap: break-word;white-space: pre-wrap;}
</style>

<nav class="navbar search-bar">
	<form method="get" accept-charset="utf-8" action="">
		<div class="container-fluid justify-content-between">
			<div class="row">
				<div class="col">
					<select name="user_id" class="form-control-sm">
<?php

$query = array(
	'SELECT'	=> 'u.id, u.group_id, u.username, u.realname, u.email, g.g_id, g.g_title',
	'FROM'		=> 'groups AS g',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'g.g_id=u.group_id'
		)
	),
	'WHERE'		=> 'group_id > 2',
	'ORDER BY'	=> 'g.g_id, u.realname',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users_info = $assigned_users = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$users_info[] = $fetch_assoc;
}

$optgroup = 0;
echo '<option value="0" selected>All Users</option>';
echo '<option value="1" '.($search_by_user_id == 1 ? 'selected' : '').'>Guest only</option>';
foreach ($users_info as $cur_user)
{
	if ($cur_user['group_id'] != $optgroup) {
		if ($optgroup) {
			echo '</optgroup>';
		}
		echo '<optgroup label="'.html_encode($cur_user['g_title']).'">';
		$optgroup = $cur_user['group_id'];
	}

	if ($search_by_user_id == $cur_user['id'])
		echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'" selected>'.html_encode($cur_user['realname']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'">'.html_encode($cur_user['realname']).'</option>'."\n";
}
?>
					</select>
				</div>
				<div class="col">
					<select name="app_id" class="form-control-sm">
<?php
if (isset($Hooks->apps_info) && !empty($Hooks->apps_info))
{
	echo '<option value="" selected>All Apps</option>';
	foreach($Hooks->apps_info as $cur_info)
	{
		if ($search_by_app_id == $cur_info['id'])
			echo '<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['title']).'</option>';
		else
			echo '<option value="'.$cur_info['id'].'">'.html_encode($cur_info['title']).'</option>';
	}
}
?>
					</select>
				</div>
				<div class="col">
					<select name="a_type" class="form-control-sm">
<?php
$type_actions = [
	0 => 'Visits only',
	1 => 'Redirect',
	2 => 'Form error',
	3 => 'System message',
	4 => '404 Page not found',
	5 => 'AJAX Requests',
	6 => 'CSRF Token'
];
echo '<option value="-1" selected>Type of actions</option>';
foreach($type_actions as $key => $value)
{
	if ($search_by_a_type == $key)
		echo '<option value="'.$key.'" selected>'.$value.'</option>';
	else
		echo '<option value="'.$key.'">'.$value.'</option>';
}
?>
					</select>
				</div>
				<div class="col">
					<button class="btn btn-sm btn-outline-success" type="submit">Search</button>
				</div>
			</div>
		</div>
	</form>	
</nav>

<div class="card-header">
	<h6 class="card-title mb-0">List of Emails</h6>
</div>
<?php		
if (!empty($swift_user_actions))
{
?>
<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>Date/Time</th>
			<th>User</th>
			<th>IP</th>
			<th>APP</th>
			<th>Status</th>
			<th>Message</th>
			<th>Current URL</th>
			<th>User Agent</th>
		</tr>
	</thead>
	<tbody>
<?php
	$i = 1;
	foreach ($swift_user_actions as $cur_info)
	{
		$user_name = ($cur_info['realname'] != '') ? '<a href="'.$URL->link('user', $cur_info['a_user_id']).'">'.html_encode($cur_info['realname']).'</a>' : 'Guest';
		$projet_name = isset($Hooks->apps_info[$cur_info['a_project_id']]['id']) ? html_encode($Hooks->apps_info[$cur_info['a_project_id']]['title']) : '';

		if (in_array($cur_info['a_type'], [2,3,4]))
			$status = '<span class="badge badge-danger">'.html_encode($cur_info['a_http_code']).'</span>';
		else if (in_array($cur_info['a_type'], [1,6]))
			$status = '<span class="badge badge-warning">'.html_encode($cur_info['a_http_code']).'</span>';	
		else
			$status = '<span class="badge badge-success">'.html_encode($cur_info['a_http_code']).'</span>';

?>
		<tr>
			<td><?php echo format_time($cur_info['a_time'], 0, 'y-m-d H:i:s', false) ?></td>
			<td><?php echo $user_name ?></td>
			<td><?php echo html_encode($cur_info['a_ip']) ?></td>
			<td><?php echo $projet_name ?></td>
			<td class="ta-center"><?php echo $status ?></td>
			<td><?php echo html_encode($cur_info['a_message']) ?></td>
			<td><a href="<?php echo BASE_URL.$cur_info['a_cur_url'] ?>" target="_blank"><?php echo html_encode($cur_info['a_cur_url']) ?></a></td>
			<td><?php echo html_encode($cur_info['a_user_agent']) ?></td>
		</tr>
<?php
		++$i;
	}
?>
	</tbody>
</table>
<?php
} else {
?>
<div class="alert alert-warning my-3" role="alert">You have no items on this page or not found within your search criteria.</div>
<?php
}
require SITE_ROOT.'footer.php';
