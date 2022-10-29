<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->is_admin())
	message($lang_common['No permission']);

$search_by_sent_from = isset($_GET['sent_from']) ? intval($_GET['sent_from']) : 0;
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
	'SELECT'	=> 'COUNT(sent_from)',
	'FROM'		=> 'sm_sent_emails',
	'WHERE'		=> 'sent_from > 0'
);

if ($search_by_sent_from == 1) $query['WHERE'] .= ' AND sent_from=1';
else if ($search_by_sent_from == 2) $query['WHERE'] .= ' AND sent_from > 1';
else if ($search_by_sent_from > 2) $query['WHERE'] .= ' AND sent_from='.$search_by_sent_from;

if ($search_by_project_id != '')
	$query['WHERE'] .= ' AND	project_id=\''.$DBLayer->escape($search_by_project_id).'\'';
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$query = array(
	'SELECT'	=> 'u.realname, u.last_visit, a.*',
	'FROM'		=> 'sm_sent_emails AS a',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'users AS u',
			'ON'			=> 'u.id=a.sent_from'
		)
	),
	'WHERE'		=> 'a.sent_from > 0',
	'ORDER BY'	=> 'a.sent_time DESC',
	'LIMIT'		=> $PagesNavigator->limit(),
);

if ($search_by_sent_from == 1) $query['WHERE'] .= ' AND a.sent_from=1';
else if ($search_by_sent_from == 2) $query['WHERE'] .= ' AND a.sent_from > 1';
else if ($search_by_sent_from > 2) $query['WHERE'] .= ' AND a.sent_from='.$search_by_sent_from;

if ($search_by_project_id != '')
	$query['WHERE'] .= ' AND a.project_id=\''.$DBLayer->escape($search_by_project_id).'\'';
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = array();
while ($user_data = $DBLayer->fetch_assoc($result))
{
	$main_info[] = $user_data;
}
$PagesNavigator->num_items($main_info);

$page_param['item_count'] = 0;

//$Loader->addCSS(BASE_URL.'/admin/css/admin.css');
//$Loader->addJS(BASE_URL.'/admin/js/admin.js', array('type' => 'url', 'async' => false, 'group' => -100 , 'weight' => 75));

$Core->set_page_title('List of sent emails');
$Core->set_page_id('sm_sent_emails', 'management');

//$SwiftTemplator->startContent('admin_table');
require SITE_ROOT.'header.php';
?>

<style>
table td {overflow-wrap: break-word;white-space: pre-wrap;}
</style>

<nav class="navbar navbar-light" style="background-color: #e3f2fd;">
	<form method="get" accept-charset="utf-8" action="" class="d-flex">
		<div class="container-fluid justify-content-between">
			<div class="row">
				<div class="col">
					<select name="sent_from" class="form-control-sm">
<?php
$optgroup = 0;
echo "\t\t\t\t\t\t".'<option value="0" '.($search_by_sent_from == 0 ? 'selected' : '').'>All Visits</option>'."\n";
echo "\t\t\t\t\t\t".'<option value="1" '.($search_by_sent_from == 1 ? 'selected' : '').'>Guests Only</option>'."\n";
echo "\t\t\t\t\t\t".'<option value="2" '.($search_by_sent_from == 2 ? 'selected' : '').'>Members Only</option>'."\n";
foreach ($users_info as $cur_user)
{
	if ($cur_user['group_id'] != $optgroup) {
		if ($optgroup) {
			echo '</optgroup>';
		}
		echo '<optgroup label="'.html_encode($cur_user['g_title']).'">';
		$optgroup = $cur_user['group_id'];
	}
	
	if ($search_by_sent_from == $cur_user['id'] && $search_by_sent_from != 2)
		echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'" selected>'.html_encode($cur_user['realname']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'">'.html_encode($cur_user['realname']).'</option>'."\n";
}
?>
					</select>
				</div>
				<div class="col">
					<select name="project_id" class="form-control-sm">
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
					<button class="btn btn-sm btn-outline-success" type="submit">Search</button>
				</div>
			</div>
		</div>
	</form>	
</nav>

<?php		
if (!empty($main_info))
{
?>
<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th class="min-w-5">Time</th>
			<th>Sender</th>
			<th>Email from</th>
			<th class="max-w-5">Recipients</th>
			<th>Reply to</th>
			<th>Status</th>
			<th>Subject</th>
			<th class="max-w-5">Message</th>
			<th>Type</th>
		</tr>
	</thead>
	<tbody>
<?php
	foreach ($main_info as $user_data)
	{
?>
				<tr>
					<td><?php echo format_time($user_data['sent_time']) ?></td>
					<td><a href="<?php echo $URL->link('user', $user_data['sent_from']) ?>"><?php echo html_encode($user_data['realname']) ?></a></td>
					<td><?php echo html_encode($user_data['from_email']) ?></td>
					<td class="max-w-20"><?php echo html_encode($user_data['sent_to']) ?></td>
					<td><?php echo html_encode($user_data['reply_to']) ?></td>
					<td class="max-w-10"><?php echo html_encode($user_data['response']) ?></td>
					<td><?php echo html_encode($user_data['subject']) ?></td>
					<td class="max-w-20"><?php echo html_to_text($user_data['message']) ?></td>
					<td><?php echo html_encode($user_data['email_type']) ?></td>
				</tr>
<?php
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
//$SwiftTemplator->endContent();