<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_inventory', 3)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$SwiftUploader = new SwiftUploader;

if (isset($_POST['sign_back_in']))
{
	$equipment_id = intval(key($_POST['sign_back_in']));

	if ($equipment_id > 0)
	{
		$equipment_info = $DBLayer->select('hca_inventory_equipments', 'id='.$equipment_id);

		$record_data = array(
			'sign_back_in_date'		=> date('Y-m-d'),
			'sign_back_in_time'		=> date('H:i:s'),
			'returned'				=> 1
		);
		$DBLayer->update('hca_inventory_records', $record_data, $equipment_info['last_record_id']);
		
		$equipment_data = array(
			'uid'					=> 0,
			'last_record_id'		=> 0,
		);
		$DBLayer->update('hca_inventory_equipments', $equipment_data, $equipment_id);
	
		$flash_message = 'Equipment has been returned.';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_inventory_warehouse', $equipment_id), $flash_message);
	}
}

$query = array(
	'SELECT'	=> 'COUNT(e.id)',
	'FROM'		=> 'hca_inventory_equipments AS e',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$query = [
	'SELECT'	=> 'e.*, p.pro_name, u.realname, g.g_title, r.sign_out_date',
	'FROM'		=> 'hca_inventory_equipments AS e',
	'JOINS'		=> [
		[
			'LEFT JOIN'		=> 'sm_property_db AS p',
			'ON'			=> 'p.id=e.pid'
		],
		[
			'LEFT JOIN'		=> 'users AS u',
			'ON'			=> 'u.id=e.uid'
		],
		[
			'LEFT JOIN'	=> 'groups AS g',
			'ON'			=> 'g.g_id=u.group_id'
		],
		[
			'LEFT JOIN'		=> 'hca_inventory_records AS r',
			'ON'			=> 'r.id=e.last_record_id'
		],
	],
	'LIMIT'		=> $PagesNavigator->limit(),
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$equipments_info = $item_ids = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$equipments_info[] = $row;
	$item_ids[] = $row['id'];
}
$PagesNavigator->num_items($equipments_info);

$PagesNavigator->pages_navi_top = false;

$Core->set_page_id('hca_inventory_warehouse', 'hca_inventory');
require SITE_ROOT.'header.php';

if (!empty($equipments_info))
{
?>
<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<table class="table table-striped">
		<thead>
			<tr class="table-primary">
				<th>Image</th>
				<th>Equipment name</th>
				<th>Property</th>
				<th>Pick Up Location</th>
				<th>Signed-Out to</th>
				<th>Signed-Out Date</th>
			</tr>
		</thead>
		<tbody>
<?php
	$SwiftUploader->getProjectFiles('hca_inventory_equipments', $item_ids);
	foreach ($equipments_info as $cur_info)
	{
		$image = $SwiftUploader->getCurProjectFiles($cur_info['id']);

		$actions = [];
		if ($cur_info['uid'] > 0)
		{
			if ($User->checkAccess('hca_inventory', 14))
				$Core->add_dropdown_item('<a href="'.$URL->link('hca_inventory_reassign', $cur_info['id']).'" class="btn btn-sm btn-info">Reassign to</a>');

			if ($User->checkAccess('hca_inventory', 13))
				$Core->add_dropdown_item('<button type="submit" name="sign_back_in['.$cur_info['id'].']" class="btn btn-sm btn-primary" onclick="return confirm(\'Are you sure you want to return it now?\')">Return back</button>');

			$actions[] = '<span class="fw-bold">'.$cur_info['realname'].'</span>';
			$actions[] = '<p>'.html_encode($cur_info['g_title']).'</p>';
			$actions[] = '<p>'.$Core->get_dropdown_menu($cur_info['id']).'</p>';
		}
		else
		{
			if ($User->checkAccess('hca_inventory', 15))
				$actions[] = '<a href="'.$URL->link('hca_inventory_sign_out', $cur_info['id']).'" class="btn btn-sm btn-success text-white">Sign-Out to</a>';
		}

		$btn_actions = [];
		if ($User->checkAccess('hca_inventory', 11))
			$btn_actions[] = '<p><a href="'.$URL->link('hca_inventory_edit', $cur_info['id']).'" class="badge bg-primary text-white">Edit</a></p>';
?>
			<tr id="row<?php echo $cur_info['id'] ?>" class="<?php echo ($id == $cur_info['id'] ? 'anchor' : '') ?>">
				<td><?php echo $image ?></td>
				<td>
					<span class="fw-bold"><?php echo html_encode($cur_info['item_name']) ?></span>
					<p>s/n: <?php echo html_encode($cur_info['item_number']) ?></p>
<?php
	if (!empty($btn_actions))
		echo implode("\n", $btn_actions);
?>
				</td>
				<td><?php echo html_encode($cur_info['pro_name']) ?></td>
				<td><?php echo html_encode($cur_info['pick_up_location']) ?></td>
				<td><?php echo implode("\n", $actions) ?></td>
				<td><?php echo html_encode($cur_info['sign_out_date']) ?></td>
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