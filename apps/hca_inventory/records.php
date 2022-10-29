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
	$item_id = intval(key($_POST['sign_back_in']));
	$record_info = $DBLayer->select('hca_inventory_records', 'id='.$item_id);
	
	if ($record_info['equipment_id'] > 0)
	{
		$form_data = array(
			'sign_back_in_date'		=> date('Y-m-d'),
			'sign_back_in_time'		=> date('H:i:s'),
			'returned'				=> 1
		);
		$DBLayer->update('hca_inventory_records', $form_data, $item_id);
		
		$query = [
			'UPDATE'	=> 'hca_inventory_equipments',
			'SET'		=> 'total_quantity=total_quantity+1',
			'WHERE'		=> 'id='.$record_info['equipment_id']
		];
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	
		$flash_message = 'Equipment has been returned.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

$query = array(
	'SELECT'	=> 'COUNT(r.id)',
	'FROM'		=> 'hca_inventory_records AS r',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'u.id=r.sign_out_to'
		],
	],
	'WHERE'		=> 'r.returned=0'
);

if (in_array($User->get('group_id'), [3,9]))
	$query['WHERE'] .= ' AND r.sign_out_to='.$User->get('id');

$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$query = [
	'SELECT'	=> 'r.*, u.realname, g.g_title, e.item_name, e.item_number, e.pick_up_location, p.pro_name',
	'FROM'		=> 'hca_inventory_records AS r',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'u.id=r.sign_out_to'
		],
		[
			'INNER JOIN'	=> 'groups AS g',
			'ON'			=> 'g.g_id=u.group_id'
		],
		[
			'INNER JOIN'	=> 'hca_inventory_equipments AS e',
			'ON'			=> 'e.id=r.equipment_id'
		],
		[
			'LEFT JOIN'		=> 'sm_property_db AS p',
			'ON'			=> 'p.id=r.property_id'
		],
	],
	'LIMIT'		=> $PagesNavigator->limit(),
	'WHERE'		=> 'r.returned=0'
];

if (in_array($User->get('group_id'), [3,9]))
	$query['WHERE'] .= ' AND r.sign_out_to='.$User->get('id');

$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $item_ids = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$main_info[] = $row;
	$item_ids[] = $row['equipment_id'];
}
$PagesNavigator->num_items($main_info);

$Core->set_page_id('hca_inventory_records', 'hca_inventory');
require SITE_ROOT.'header.php';

if (!empty($main_info))
{
	//$SwiftUploader->getProjectFiles('swift_inventory_management', $item_ids);
?>
<div class="card-header">
	<h6 class="card-title mb-0">Signed-out equipments</h6>
</div>
<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<table class="table table-striped">
		<thead>
			<tr class="table-primary">
				<th>Image</th>
				<th>Equipment name</th>
				<th>Technician</th>
				<th>Signed-Out Date</th>
				<th>Actions</th>
			</tr>
		</thead>
		<tbody>
<?php
	$SwiftUploader->getProjectFiles('hca_inventory_equipments', $item_ids);
	foreach ($main_info as $cur_info)
	{
		$image = $SwiftUploader->getCurProjectFiles($cur_info['equipment_id']);

		$actions = [];
		if ($User->check_perms('hca_inventory', 3))
		{
			$actions[] = '<p><button type="submit" name="sign_back_in['.$cur_info['id'].']" class="btn btn-sm btn-primary" onclick="return confirm(\'Are you sure you want to return it now?\')">Return back</button></p>';
			$actions[] = '<p><a href="'.$URL->link('hca_inventory_reassign', $cur_info['id']).'" class="btn btn-sm btn-info">Reassign to</a></p>';
		}
?>
				<tr id="row<?php echo $cur_info['id'] ?>">
					<td><?php echo $image ?></td>
					<td>
						<p style="font-weight:bold"><?php echo html_encode($cur_info['item_name']) ?></p>
						<p>s/n: <?php echo html_encode($cur_info['item_number']) ?></p>
					</td>
					<td>
						<p style="font-weight:bold"><?php echo html_encode($cur_info['realname']) ?></p>
						<p><?php echo html_encode($cur_info['g_title']) ?></p>
					</td>
					<td><?php echo html_encode($cur_info['sign_out_date']) ?></td>
					<td><?php echo implode("\n", $actions) ?></td>
				</tr>
<?php
	}
?>
			</tbody>
		</table>
	</form>
<?php
} else {
?>
		<div class="alert alert-warning" role="alert">You have no items on this page.</div>
<?php
}
require SITE_ROOT.'footer.php';