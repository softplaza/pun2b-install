<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access3 = ($User->checkAccess('hca_wom', 3)) ? true : false;
if (!$access3)
	message($lang_common['No permission']);

$HcaWOM = new HcaWOM;

// 3 - Maintenance, 9 - Painters
$is_technician = in_array($User->get('group_id'), [3,9]) ? true : false;
$is_manager = ($User->get('property_access') != '' && $User->get('property_access') != 0) ? true : false;

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;

$search_query = [];
$search_query[] = 'w.wo_status!=4'; // Exclude completed
if ($is_technician)
	$search_query[] = 'w.assigned_to='.$User->get('id');

if ($search_by_property_id > 0)
	$search_query[] = 'w.property_id='.$search_by_property_id;

$query = [
	'SELECT'	=> 'COUNT(t.id)',
	'FROM'		=> 'hca_wom_tasks AS t',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'hca_wom_work_orders AS w',
			'ON'			=> 'w.id=t.work_order_id'
		],
		[
			'INNER JOIN'	=> 'sm_property_db AS p',
			'ON'			=> 'p.id=w.property_id'
		],
		[
			'INNER JOIN'	=> 'sm_property_units AS pu',
			'ON'			=> 'pu.id=w.unit_id'
		],
		[
			'INNER JOIN'	=> 'users AS u1',
			'ON'			=> 'u1.id=w.assigned_to'
		],
		[
			'INNER JOIN'	=> 'users AS u2',
			'ON'			=> 'u2.id=w.requested_by'
		],
	],
];
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$query = [
	'SELECT'	=> 't.*, w.property_id, w.unit_id, w.wo_message, p.pro_name, pu.unit_number, u1.realname AS assigned_name, u1.email AS assigned_email, u2.realname AS requested_name, u2.email AS requested_email',
	'FROM'		=> 'hca_wom_tasks AS t',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'hca_wom_work_orders AS w',
			'ON'			=> 'w.id=t.work_order_id'
		],
		[
			'INNER JOIN'	=> 'sm_property_db AS p',
			'ON'			=> 'p.id=w.property_id'
		],
		[
			'INNER JOIN'	=> 'sm_property_units AS pu',
			'ON'			=> 'pu.id=w.unit_id'
		],
		[
			'INNER JOIN'	=> 'users AS u1',
			'ON'			=> 'u1.id=w.assigned_to'
		],
		[
			'INNER JOIN'	=> 'users AS u2',
			'ON'			=> 'u2.id=w.requested_by'
		],
	],
	'LIMIT'		=> $PagesNavigator->limit(),
	'ORDER BY'	=> 'w.wo_status DESC, p.pro_name, LENGTH(pu.unit_number), pu.unit_number',
];
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$hca_wom_work_orders = [];
while ($row = $DBLayer->fetch_assoc($result))
{
	$property_ids[] = $row['property_id'];
	$hca_wom_work_orders[] = $row;
}
$PagesNavigator->num_items($hca_wom_work_orders);

$Core->set_page_id('hca_wom_tasks', 'hca_wom');
require SITE_ROOT.'header.php';


$query = array(
	'SELECT'	=> 'p.*',
	'FROM'		=> 'sm_property_db AS p',
	'WHERE'		=> 'p.id IN ('.implode(',', $property_ids).')',
	'ORDER BY'	=> 'p.pro_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = [];
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $fetch_assoc;
}
?>
<nav class="navbar search-bar">
	<form method="get" accept-charset="utf-8" action="" class="d-flex">
		<div class="container-fluid justify-content-between">
			<div class="row">
				<div class="col-md-auto pe-0 mb-1">
					<select name="property_id" class="form-select form-select-sm">
						<option value="">All My Properties</option>
<?php
foreach ($property_info as $val)
{
	if ($search_by_property_id == $val['id'])
		echo '<option value="'.$val['id'].'" selected>'.$val['pro_name'].'</option>';
	else
		echo '<option value="'.$val['id'].'">'.$val['pro_name'].'</option>';
}
?>
					</select>
				</div>
				<div class="col-md-auto">
					<button type="submit" class="btn btn-sm btn-outline-success">Search</button>
					<a href="<?php echo $URL->link('hca_wom_tasks') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
				</div>
			</div>
		</div>
	</form>
</nav>

<?php
if (!empty($hca_wom_work_orders))
{
?>

<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>WO#</th>
			<th>Unit#</th>
			<th>Task</th>
			<th>Details</th>
			<th>Submitted</th>
		</tr>
	</thead>
	<tbody>
<?php
	foreach ($hca_wom_work_orders as $cur_info)
	{
		$unit_number = ($cur_info['unit_id'] > 0) ? html_encode($cur_info['unit_number']) : 'Common area';
?>
		<tr>
			<td class="fw-bold ta-center"><?php echo $cur_info['work_order_id'] ?></td>
			<td class="ta-center"><?php echo $unit_number ?></td>
			<td class=""><?php echo html_encode($cur_info['wo_message']) ?></td>
			<td class=""><?php echo html_encode($cur_info['task_message']) ?></td>
			<td class="ta-center"></td>
		</tr>
<?php
	}
?>
	</tbody>
</table>
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
