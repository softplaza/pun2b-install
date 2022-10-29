<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_fs', 6)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$action = isset($_GET['action']) ? $_GET['action'] : '';
$sort_by = isset($_GET['sort_by']) ? intval($_GET['sort_by']) : 1;

if (isset($_POST['update']))
{
	if (isset($_POST['user_id']) && !empty($_POST['user_id']))
	{
		foreach($_POST['user_id'] as $pid => $uid)
		{
			$query = array(
				'UPDATE'	=> 'sm_property_db',
				'SET'		=> 'emergency_uid='.$uid,
				'WHERE'		=> 'id='.$pid,
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
		
		// Add flash message
		$flash_message = 'Emergency weekdays has been updated';
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
if ($sort_by == 2) $query['ORDER BY'] = 'u.realname';
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$property_info[$fetch_assoc['id']] = $fetch_assoc;
}

$query = array(
	'SELECT'	=> 'u.id, u.realname, g.g_id, g.g_user_title',
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
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$founded_user_datas = array();
while ($user_data = $DBLayer->fetch_assoc($result)) {
	$founded_user_datas[] = $user_data;
}

$Core->set_page_title('Covering weekdays');
$Core->set_page_id('hca_fs_emergency_property', 'hca_fs');
require SITE_ROOT.'header.php';

if (!empty($property_info))
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
$search_array = array(1 => 'Sort by Properties', 2 => 'Sort by Employees');
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
			
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<table class="table table-striped table-bordered">
			<thead>
				<tr>
					<th class="tc1">Property Name</th>
					<th class="tc1">Maintenance Name</th>
				</tr>
			</thead>
			<tbody>
<?php
	$zone_array = array(1 => 'Zone 1', 2 => 'Zone 2', 3 => 'Zone 3');
	foreach ($property_info as $cur_info)
	{
		$cur_zone = isset($zone_array[$cur_info['zone']]) ? $zone_array[$cur_info['zone']] : '';
		
		$page_param['td'] = array();
		$page_param['td']['css_emrg'] = ($cur_info['emergency_uid'] > 0) ? 'selected' : 'empty';
			
?>
				<tr>
					<td class="td1"><?php echo $cur_info['pro_name'] ?></td>
					<td class="td3 <?php echo $page_param['td']['css_emrg'] ?>">
						<select name="user_id[<?php echo $cur_info['id'] ?>]">
							<option value="0" selected>Select Employee</option>
<?php
	foreach ($founded_user_datas as $val)
	{
		if ($cur_info['emergency_uid'] == $val['id'])
			echo '<option value="'.$val['id'].'" selected="selected">'.$val['realname'].'</option>';
		else
			echo '<option value="'.$val['id'].'">'.$val['realname'].'</option>';
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
		<div class="card">
			<div class="card-body">
				<button type="submit" name="update" class="btn btn-primary">Update List</button>
			</div>
		</div>
	</form>
<?php
}
else
{
?>
	<div class="card">
		<div class="card-body">
			<div class="alert alert-warning" role="alert">You have no items on this page.</div>
		</div>
	</div>
<?php
}
require SITE_ROOT.'footer.php';