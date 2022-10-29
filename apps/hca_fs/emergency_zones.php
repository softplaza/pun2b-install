<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_fs', 6)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$EmergencySchedule = new EmergencySchedule;

$action = isset($_GET['action']) ? $_GET['action'] : '';
$sort_by = isset($_GET['sort_by']) ? intval($_GET['sort_by']) : 1;
$zones_list = [1 => 'ZONE 1', 2 => 'ZONE 2', 3 => 'ZONE 3'];

if (isset($_POST['update']))
{
	$user_ids = $_POST['zone'];
	
	if (!empty($user_ids))
	{
		foreach($user_ids as $uid => $zone)
		{
			$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> 'hca_fs_zone='.$zone,
				'WHERE'		=> 'id='.$uid,
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
		
		// Add flash message
		$flash_message = 'Emergency zone has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

$query = array(
	'SELECT'	=> 'p.*, u.realname',
	'FROM'		=> 'sm_property_db AS p',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'	=> 'users AS u',
			'ON'		=> 'p.emergency_uid=u.id'
		),
	),
	'WHERE'		=> 'p.enabled=1 AND p.zone > 0',
	'ORDER BY'	=> 'p.pro_name',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$property_info[$fetch_assoc['id']] = $fetch_assoc;
}

$query = array(
	'SELECT'	=> 'u.id, u.realname, u.first_name, u.last_name, u.hca_fs_zone, g.g_id, g.g_user_title',
	'FROM'		=> 'users AS u',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'groups AS g',
			'ON'			=> 'g.g_id=u.group_id'
		)
	),
	'WHERE'		=> 'g.g_id='.$Config->get('o_hca_fs_maintenance'),
	'ORDER BY'	=> 'u.username',
);

if ($sort_by == 1) $query['ORDER BY'] = ($User->get('users_sort_by') == 1) ? 'last_name' : 'realname';
else if ($sort_by == 2) $query['ORDER BY'] = 'u.hca_fs_zone';

$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users_info = array();
while ($row = $DBLayer->fetch_assoc($result))
{
	$users_info[$row['id']] = $row;
	
	if ($User->get('users_sort_by') == 1);
		$users_info[$row['id']]['realname'] = $row['last_name'].' '.$row['first_name'];
}

$Core->set_page_id('hca_fs_emergency_zones', 'hca_fs');
require SITE_ROOT.'header.php';
?>


<div class="alert alert-info" role="alert">
	<p><strong>ZONE 1: </strong><?php echo implode(', ', $EmergencySchedule->getPropertiesByZone($property_info, 1)) ?></p>
	<p><strong>ZONE 2: </strong><?php echo implode(', ', $EmergencySchedule->getPropertiesByZone($property_info, 2)) ?></p>
	<p><strong>ZONE 3: </strong><?php echo implode(', ', $EmergencySchedule->getPropertiesByZone($property_info, 3)) ?></p>
</div>

<?php
if (!empty($users_info))
{
	$page_param['table_header'] = array();
	
	$page_param['table_header']['pro_name'] = '<th class="tc'.count($page_param['table_header']).'" rowspan="2" scope="col"><strong>Property Name</strong></th>';
	$page_param['table_header']['days'] = '<th class="tc'.count($page_param['table_header']).'" colspan="7" scope="col"><strong>Days of Week</strong></th>';
?>
<nav class="navbar container-fluid search-box">
	<form method="get" accept-charset="utf-8" action="">
		<div class="row">
			<div class="col">
				<select name="sort_by" class="form-select">
<?php
$search_array = array(1 => 'Sort by Employees', 2 => 'Sort by Zones', /*3 => 'Sort by Properties',*/);
foreach ($search_array as $key => $value)
{
	if ($sort_by == $key)
		echo '<option value="'.$key.'" selected="selected">'.$value.'</option>';
	else
		echo '<option value="'.$key.'">'.$value.'</option>';
}
?>
				</select>
			</div>
			<div class="col">
				<button type="submit" class="btn btn-outline-success float-none">Sort list</button>
			</div>
		</div>
	</form>
</nav>
			
<form method="post" accept-charset="utf-8" action="" id="emergency_zones">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<table class="table table-striped table-bordered">
		<thead>
			<tr class=''>
				<th class="tc1">Maintenance Name</th>
				<th class="tc1">Zone</th>
			</tr>
		</thead>
		<tbody>
<?php
	
	foreach ($users_info as $cur_info)
	{
		$page_param['td'] = array();
		$page_param['td']['css_emrg'] = ($cur_info['hca_fs_zone'] > 0) ? 'selected' : 'empty';
		
		if ($cur_info['hca_fs_zone'] == 1)
			$page_param['td']['css_zone'] = 'zone1';
		else if ($cur_info['hca_fs_zone'] == 2)
			$page_param['td']['css_zone'] = 'zone2';
		else if ($cur_info['hca_fs_zone'] == 3)
			$page_param['td']['css_zone'] = 'zone3';
		else
			$page_param['td']['css_zone'] = '';
?>
			<tr class="<?php echo $page_param['td']['css_zone'] ?>">
				<td class="td1"><?php echo $cur_info['realname'] ?></td>
				<td class="<?php echo $page_param['td']['css_emrg'] ?>">
					<select name="zone[<?php echo $cur_info['id'] ?>]">
						<option value="0" selected>SELECT ZONE</option>
<?php
	foreach ($zones_list as $key => $val)
	{
		if ($cur_info['hca_fs_zone'] == $key)
			echo '<option value="'.$key.'" selected="selected">'.$val.'</option>';
		else
			echo '<option value="'.$key.'">'.$val.'</option>';
	}
?>
					</select>
				</td>
			</tr>
<?php
	}
?>
		</tbody>
	</table>
	<button type="submit" name="update" class="btn btn-primary">Update List</button>
</form>
<?php
}
else
{
?>
	<div class="alert alert-warning" role="alert">You have no items on this page.</div>
<?php
}
require SITE_ROOT.'footer.php';