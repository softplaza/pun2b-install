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
	'WHERE'		=> 'group_id != 2',
	'ORDER BY'	=> 'g.g_id, u.realname',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users_info = $assigned_users = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$users_info[] = $fetch_assoc;
}

$query = array(
	'SELECT'	=> 'COUNT(*)',
	'FROM'		=> 'sm_user_actions',
	//'WHERE'		=> 'user_id != 2'
);

if ($search_by_user_id == 0) $query['WHERE'] = 'user_id > 0';
else if ($search_by_user_id == 1) $query['WHERE'] = 'user_id=1';
else if ($search_by_user_id == 2) $query['WHERE'] = 'user_id > 1';
else if ($search_by_user_id > 2) $query['WHERE'] = 'user_id='.$search_by_user_id;

if ($search_by_project_id != '')
	$query['WHERE'] .= ' AND project_id=\''.$DBLayer->escape($search_by_project_id).'\'';
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$query = array(
	'SELECT'	=> 'u.realname, u.last_visit, a.*',
	'FROM'		=> 'sm_user_actions AS a',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'users AS u',
			'ON'			=> 'u.id=a.user_id'
		)
	),
	//'WHERE'		=> 'a.user_id != 2',
	'ORDER BY'	=> 'a.visit_time DESC',
	'LIMIT'		=> $PagesNavigator->limit(),
);

if ($search_by_user_id == 0) $query['WHERE'] = 'a.user_id > 0';
else if ($search_by_user_id == 1) $query['WHERE'] = 'a.user_id=1';
else if ($search_by_user_id == 2) $query['WHERE'] = 'a.user_id > 1';
else if ($search_by_user_id > 2) $query['WHERE'] = 'a.user_id='.$search_by_user_id;

if ($search_by_project_id != '')
	$query['WHERE'] .= ' AND a.project_id=\''.$DBLayer->escape($search_by_project_id).'\'';
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = array();
while ($user_data = $DBLayer->fetch_assoc($result))
{
	$main_info[] = $user_data;
}
$PagesNavigator->num_items($main_info);

//$Loader->addCSS(BASE_URL.'/admin/css/admin.css?'.time());
//$Loader->addJS(BASE_URL.'/admin/js/admin.js', array('type' => 'url', 'async' => false, 'group' => -100 , 'weight' => 75));

$Core->set_page_title('Actions of Users');
$Core->set_page_id('sm_user_actions', 'admin');

//$SwiftTemplator->startContent('admin_table');

require SITE_ROOT.'header.php';
?>
<style>
.th5 {width:50px;max-width:50px;}
</style>
<?php
$page_param['item_count'] = 0;
$page_param['th'] = array();
$page_param['th'][] = '<th>User name</th>';
$page_param['th'][] = '<th>Last visit</th>';
$page_param['th'][] = '<th>IP</th>';
$page_param['th'][] = '<th>URL</th>';
$page_param['th'][] = '<th>Code</th>';
$page_param['th'][] = '<th>Project ID</th>';
$page_param['th'][] = '<th>Message</th>';
?>
	<nav class="navbar search-bar navbar-light mb-1">
		<form method="get" accept-charset="utf-8" action="" class="d-flex">
			<div class="container-fluid justify-content-between">
				<div class="row">
					<div class="col">
						<select name="user_id" class="form-control-sm">
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
	'profile'				=> 'Profile',
	'system'				=> 'System Pages',
	// Other Prijects
	
	'sm_calendar'			=> 'Calendar',
	'hca_cc'				=> 'Compliance Calendar',
	'sm_uploader'			=> 'Files Uploader',
	'hca_fs'				=> 'Facility Schedule',
	'sm_messenger'			=> 'Messenger',
	'hca_5840'				=> 'Moisture Inspections',
	'sm_pest_control'		=> 'Pest Control',
	'hca_ui'				=> 'Plumbing Inspections',
	'sm_special_projects'	=> 'Projects & Construction',
	'sm_property_management'=> 'Property Menagement',
	'hca_turn_over'			=> 'TurnOver Inspections',
	'hca_trees'				=> 'Trees Projects',
	'hca_vcr'				=> 'VCR Projects',
	'sm_vendors'			=> 'Vendors Menagement',
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
	echo $PagesNavigator->getNavi();
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
		$page_param['td']['realname'] = ($user_data['user_id'] == 1) ? '<td>Guest</td>' : '<td><a href="'.$URL->link('user', $user_data['user_id']).'">'.html_encode($user_data['realname']).'</a></td>';
		$page_param['td']['visit_time'] = '<td>'.format_time($user_data['visit_time'], 1, 'F, d H:i:s').'</td>';
		$page_param['td']['ip'] = '<td>'.html_encode($user_data['ip']).'</td>';
		$page_param['td']['cur_url'] = '<td><a href="'.$user_data['cur_url'].'" target="_blank">'.$user_data['cur_url'].'</a></td>';
		$page_param['td']['http_code'] = '<td>'.($user_data['http_code'] == 200 ? '<span class="badge bg-success">'.$user_data['http_code'].'</span>' : '<span class="badge bg-warning">'.$user_data['http_code'].'</span>').'</td>';
		$page_param['td']['project_id'] = '<td><strong>'.html_encode($user_data['project_id']).'</strong></td>';
		$page_param['td']['message'] = '<td>'.html_encode($user_data['message']).'</td>';
		
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

	echo $PagesNavigator->getNavi();

} else {
?>
	<div class="alert alert-warning" role="alert">No items on this page</div>
<?php
}
require SITE_ROOT.'footer.php';