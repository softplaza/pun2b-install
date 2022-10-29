<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_inventory', 4)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$SwiftUploader = new SwiftUploader;

if (isset($_POST['sign_back_in']))
{
	$item_id = intval(key($_POST['sign_back_in']));

	$form_data = array(
		'sign_back_in_date'		=> date('Y-m-d'),
		'sign_back_in_time'		=> date('H:i:s'),
		'returned'				=> 1
	);
	$DBLayer->update('hca_inventory_records', $form_data, $item_id);

	$flash_message = 'Equipment has been returned.';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

$query = array(
	'SELECT'	=> 'COUNT(r.id)',
	'FROM'		=> 'hca_inventory_records AS r',
//	'WHERE'		=> 'r.returned=0'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$query = [
	'SELECT'	=> 'r.*, u.realname, e.item_name, e.item_number, p.pro_name',
	'FROM'		=> 'hca_inventory_records AS r',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'u.id=r.sign_out_to'
		],
		[
			'INNER JOIN'	=> 'hca_inventory_equipments AS e',
			'ON'			=> 'e.id=r.equipment_id'
		],
		[
			'LEFT JOIN'		=> 'sm_property_db AS p',
			'ON'			=> 'p.id=e.pid'
		],
	],
	'LIMIT'		=> $PagesNavigator->limit(),
//	'WHERE'		=> 'r.returned=0'
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $item_ids = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$main_info[] = $row;
	$item_ids[] = $row['equipment_id'];
}
$PagesNavigator->num_items($main_info);

$Core->set_page_id('hca_inventory_report', 'hca_inventory');
require SITE_ROOT.'header.php';

if (!empty($main_info))
{
?>
<div class="card-header">
	<h6 class="card-title mb-0">Equipment signed-out report</h6>
</div>
<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<table class="table table-striped">
		<thead>
			<tr class="table-primary">
				<th>Equipment name</th>
				<th>Serial #</th>
				<th>Property name</th>
				<th>Technician</th>
				<th>Signed-Out Date</th>
				<th>Signed-Back-In Date</th>
			</tr>
		</thead>
		<tbody>
<?php
	$SwiftUploader->getProjectFiles('hca_inventory_equipments', $item_ids);
	foreach ($main_info as $cur_info)
	{
		$image = $SwiftUploader->getCurProjectFiles($cur_info['equipment_id']);
		
		if ($cur_info['returned'] > 0)
			$sign_back_in_date = html_encode($cur_info['sign_back_in_date']);
		else
			$sign_back_in_date = '<span class="badge bg-warning">On hand</span>';
?>
				<tr id="row<?php echo $cur_info['id'] ?>">
					<td><?php echo html_encode($cur_info['item_name']) ?></td>
					<td><?php echo html_encode($cur_info['item_number']) ?></td>
					<td><?php echo html_encode($cur_info['pro_name']) ?></td>
					<td><?php echo html_encode($cur_info['realname']) ?></td>
					<td><?php echo html_encode($cur_info['sign_out_date']) ?></td>
					<td><?php echo $sign_back_in_date ?></td>
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