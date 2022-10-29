<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->is_admin())
	message($lang_common['No permission']);

$search_by_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$search_by_project_id = isset($_GET['project_id']) ? swift_trim($_GET['project_id']) : '';

// Load the userlist.php language file
require SITE_ROOT.'lang/'.$User->get('language').'/userlist.php';

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

$query = array(
	'SELECT'	=> 'COUNT(user_id)',
	'FROM'		=> 'sm_errors_reports',
	'WHERE'		=> 'user_id != 0'
);
if ($search_by_user_id == 0) $query['WHERE'] .= ' AND user_id > 0';
else if ($search_by_user_id == 1) $query['WHERE'] .= ' AND user_id=1';
else if ($search_by_user_id == 2) $query['WHERE'] .= ' AND user_id > 1';
else if ($search_by_user_id > 2) $query['WHERE'] .= ' AND user_id='.$search_by_user_id;

if ($search_by_project_id != '')
	$query['WHERE'] .= ' AND	project_id=\''.$DBLayer->escape($search_by_project_id).'\'';
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$query = array(
	'SELECT'	=> 'err.*, u.realname, u.last_visit',
	'FROM'		=> 'sm_errors_reports AS err',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'users AS u',
			'ON'			=> 'u.id=err.user_id'
		)
	),
	'WHERE'		=> 'err.user_id != 0',
	'ORDER BY'	=> 'err.error_time DESC',
	'LIMIT'		=> $PagesNavigator->limit(),
);
if ($search_by_user_id == 0) $query['WHERE'] .= ' AND err.user_id > 0';
else if ($search_by_user_id == 1) $query['WHERE'] .= ' AND err.user_id=1';
else if ($search_by_user_id == 2) $query['WHERE'] .= ' AND err.user_id > 1';
else if ($search_by_user_id > 2) $query['WHERE'] .= ' AND err.user_id='.$search_by_user_id;

if ($search_by_project_id != '')
	$query['WHERE'] .= ' AND err.project_id=\''.$DBLayer->escape($search_by_project_id).'\'';
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = array();
while ($row = $DBLayer->fetch_assoc($result))
{
	$main_info[] = $row;
}
$PagesNavigator->num_items($main_info);

$Core->set_page_id('sm_errors_report', 'management');
require SITE_ROOT.'header.php';
?>
<style>
.td-short {width:50px;max-width:50px;}
</style>
<?php
$page_param['item_count'] = 0;
$page_param['th'] = array();
$page_param['th'][] = '<th>User</th>';
$page_param['th'][] = '<th>IP</th>';
$page_param['th'][] = '<th>Time</th>';
$page_param['th'][] = '<th>Type</th>';
$page_param['th'][] = '<th>Project</th>';
$page_param['th'][] = '<th class="th-short">Message</th>';
$page_param['th'][] = '<th class="th-short">Error Page</th>';
$page_param['th'][] = '<th class="th-short">URL From</th>';
?>

	<nav class="navbar navbar-light" style="background-color: #e3f2fd;">
		<form method="get" accept-charset="utf-8" action="" class="d-flex">
			<div class="container-fluid justify-content-between">
				<div class="row">
					<div class="col">
						<select name="user_id" class="form-select-sm">
<?php
$optgroup = 0;


echo "\t\t\t\t\t\t".'<option value="0" '.($search_by_user_id == 0 ? 'selected' : '').'>All Visits</option>'."\n";
echo "\t\t\t\t\t\t".'<option value="1" '.($search_by_user_id == 1 ? 'selected' : '').'>Guests Only</option>'."\n";
echo "\t\t\t\t\t\t".'<option value="2" '.($search_by_user_id == 2 ? 'selected' : '').'>Members Only</option>'."\n";
foreach ($users_info as $cur_user)
{
	if ($cur_user['group_id'] != $optgroup) {
		if ($optgroup) {
			echo '</optgroup>';
		}
		echo '<optgroup label="'.html_encode($cur_user['g_title']).'">';
		$optgroup = $cur_user['group_id'];
	}
	
	if ($search_by_user_id == $cur_user['id'] && $search_by_user_id != 2)
		echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'" selected>'.html_encode($cur_user['realname']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'">'.html_encode($cur_user['realname']).'</option>'."\n";
}
?>
						</select>
					</div>
					<div class="col">
						<select name="project_id" class="form-select-sm">
<?php
$projects_list = array(
	'management'			=> 'System Administration',
	'settings'				=> 'System Settings',
	'profile'				=> 'Profile Menagement',
	'system'				=> 'System Pages',
	// Other Prijects
	'sm_special_projects'	=> 'Special Projects',
	'hca_vcr'				=> 'VCR Projects',
	'hca_5840'				=> 'Moisture Inspections',
	'hca_fs'				=> 'In-House Facilities',
	'sm_pest_control'		=> 'Pest Control',
	'hca_turn_over'			=> 'TurnOver Inspections',
	'hca_trees'				=> 'Trees Projects',
	'sm_calendar'			=> 'Calendar',
	'sm_vendors'			=> 'Vendors Menagement',
	'sm_messenger'			=> 'Messenger',
	'sm_property_management'=> 'Property Menagement',
	'sm_uploader'			=> 'Files Uploader',
	
);

echo "\t\t\t\t\t\t".'<option value="" selected="selected">All Projects</option>'."\n";
foreach ($projects_list as $key => $val)
{
	if ($search_by_project_id == $key)
		echo "\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.$val.'</option>'."\n";
	else
		echo "\t\t\t\t\t\t".'<option value="'.$key.'">'.$val.'</option>'."\n";
}
?>
						</select>
					</div>
					<div class="col">
						<button type="submit" class="btn btn-sm btn-outline-success">Search</button>
					</div>
				</div>
			</div>
		</form>
	</nav>

<?php		
if (!empty($main_info))
{
?>

	<table class="table table-striped my-0">
		<thead>
			<tr>
				<?php echo implode("\n\t\t\t\t\t\t", $page_param['th'])."\n" ?>
			</tr>
		</thead>
		<tbody>
<?php
	foreach ($main_info as $user_data)
	{
		$page_param['td'] = array();
		$page_param['td'][] = ($user_data['realname'] != '') ? '<td class="date-time"><a href="'.$URL->link('user', $user_data['user_id']).'"  target="_blank">'.html_encode($user_data['realname']).'</a></td>' : '<td class="date-time">Guest</td>';
		$page_param['td'][] = '<td class="date-time">'.html_encode($user_data['user_ip']).'</td>';
		$page_param['td'][] = '<td class="date-time">'.format_time($user_data['error_time']).'</td>';
		$page_param['td'][] = '<td class="date-time">'.html_encode($user_data['error_type']).'</td>';
		$page_param['td'][] = '<td class="date-time">'.html_encode($user_data['project_id']).'</td>';
		$page_param['td'][] = '<td class="th-short">'.html_to_text($user_data['message']).'</td>';
		$page_param['td'][] = '<td class="th-short"><a href="'.$user_data['cur_url'].'" target="_blank">'.$user_data['cur_url'].'</a></td>';
		$page_param['td'][] = '<td class="th-short"><a href="'.$user_data['url_from'].'" target="_blank">'.$user_data['url_from'].'</a></td>';
		
		++$page_param['item_count'];
?>
				<tr class="<?php echo ($page_param['item_count'] % 2 != 0) ? 'odd' : 'even' ?><?php if ($page_param['item_count'] == 1) echo ' row1'; ?>">
					<?php echo implode("\n\t\t\t\t\t\t", $page_param['td'])."\n" ?>
				</tr>
<?php
	}
?>
		</tbody>
	</table>
<?php
} else {
?>
	<div class="alert alert-warning" role="alert">No items on this page</div>
<?php
}
require SITE_ROOT.'footer.php';