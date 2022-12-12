<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access4 = ($User->checkAccess('hca_wom', 4)) ? true : false;
$access15 = ($User->checkAccess('hca_wom', 15)) ? true : false;

$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;

if (isset($_POST['approve_task']))
{
	$task_id = intval(key($_POST['approve_task']));

	if ($task_id > 0)
	{
		// Update task of Work Order
		$DBLayer->update('hca_wom_tasks', ['task_status' => 1], $task_id);

		// Send Email if needed



		// Add flash message
		$flash_message = 'Task #'.$task_id.' has been approved.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['reject_task']))
{
	$task_id = intval(key($_POST['reject_task']));

	if ($task_id > 0)
	{
		// Update task of Work Order
		$DBLayer->delete('hca_wom_tasks', $task_id);

		$query = array(
			'DELETE'	=> 'hca_wom_work_orders',
			'WHERE'		=> 'last_task_id='.$task_id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		// Send Email if needed


		// Add flash message
		$flash_message = 'Task #'.$task_id.' has been rejected and deleted.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

$HcaWOM = new HcaWOM;

// 3 - Maintenance, 9 - Painters
$is_technician = in_array($User->get('group_id'), [3,9]) ? true : false;
$is_manager = ($User->get('property_access') != '' && $User->get('property_access') != 0) ? true : false;

$search_query = [];
$search_query[] = 't.task_status=0';

if ($is_manager)
{
	$property_ids = explode(',', $User->get('property_access'));
	$search_query[] = 'w.property_id IN ('.implode(',', $property_ids).')';
}

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
			'ON'			=> 'u1.id=w.requested_by'
		],
	],
];
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$query = [
	'SELECT'	=> 't.*, w.priority, w.property_id, p.pro_name, pu.unit_number, u1.realname AS requested_name, u1.email AS requested_email, i.item_name', // u1.realname AS assigned_name, u1.email AS assigned_email,
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
			'ON'			=> 'u1.id=w.requested_by'
		],
		[
			'LEFT JOIN'		=> 'hca_wom_items AS i',
			'ON'			=> 'i.id=t.item_id'
		],
	],
	'LIMIT'		=> $PagesNavigator->limit(),
	'ORDER BY'	=> 'p.pro_name, LENGTH(pu.unit_number), pu.unit_number',
];
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$hca_wom_tasks = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$hca_wom_tasks[] = $row;
}
$PagesNavigator->num_items($hca_wom_tasks);

$query = array(
	'SELECT'	=> 'p.*',
	'FROM'		=> 'sm_property_db AS p',
	'WHERE'		=> 'p.id!=105 AND p.id!=113 AND p.id!=115 AND p.id!=116',
	'ORDER BY'	=> 'p.pro_name'
);
if ($User->get('property_access') != '' && $User->get('property_access') != 0)
{
	$property_ids = explode(',', $User->get('property_access'));
	$query['WHERE'] .= ' AND p.id IN ('.implode(',', $property_ids).')';
}
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = [];
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $fetch_assoc;
}

$Core->set_page_id('hca_wom_work_orders_suggested', 'hca_wom');
require SITE_ROOT.'header.php';
?>
<nav class="navbar search-bar">
	<form method="get" accept-charset="utf-8" action="" class="d-flex">
		<div class="container-fluid justify-content-between">
			<div class="row">

				<div class="col-md-auto pe-0 mb-1">
					<select name="property_id" class="form-select form-select-sm">
						<option value="">Properties</option>
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
					<a href="<?php echo $URL->link('hca_wom_work_orders') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
				</div>
			</div>
		</div>
	</form>
</nav>

<div class="card-header">
	<h6 class="card-title mb-0">List of Suggested Work Orders</h6>
</div>
<?php
if (!empty($hca_wom_tasks))
{
?>
<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
	<table class="table table-striped table-bordered">
		<thead>
			<tr>
				<th>Location</th>
				<th>Priority</th>
				<th>Item</th>
				<th>Comments</th>
				<th>Submitted by</th>
				<th>Date</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
<?php
	$property_id = 0;
	foreach ($hca_wom_tasks as $cur_info)
	{
		if ($cur_info['priority'] == 2)
			$priority = '<span class="text-danger fw-bold">High</span>';
		else if ($cur_info['priority'] == 1)
			$priority = '<span class="text-warning fw-bold">Medium</span>';
		else
			$priority = '<span class="text-warning fw-secondary">Low</span>';

		$task_title = [];
		$task_title[] = '<span class="fw-bold">'.html_encode($cur_info['item_name']).'</span>';
		$task_title[] = isset($HcaWOM->task_actions[$cur_info['task_action']]) ? ' (<span class="">'.$HcaWOM->task_actions[$cur_info['task_action']].'</span>)' : '';

		if ($property_id != $cur_info['property_id'])
		{
			echo '<tr class="table-warning"><td colspan="7" class="fw-bold">'.html_encode($cur_info['pro_name']).'</td></tr>';
			$property_id = $cur_info['property_id'];
		}
?>
			<tr id="row<?php echo $cur_info['id'] ?>" class="<?php echo ($id == $cur_info['id'] ? ' anchor' : '') ?>">
				<td><span class="fw-bold"><?php echo html_encode($cur_info['unit_number']) ?></span></td>
				<td class="min-100 ta-center"><?php echo $priority ?></td>
				<td class="min-100"><p><?php echo implode("\n", $task_title) ?></p></td>
				<td class=""><?php echo html_encode($cur_info['task_message']) ?></td>
				<td class="min-100 ta-center"><?php echo html_encode($cur_info['requested_name']) ?></td>
				<td class="min-100 ta-center"><?php echo format_time($cur_info['time_created'], 1) ?></td>
				<td class="ta-center">
<?php if ($access4) : ?>
					<button type="submit" name="approve_task[<?=$cur_info['id']?>]" class="badge bg-success">Approve</button>
					<button type="submit" name="reject_task[<?=$cur_info['id']?>]" class="badge bg-danger" onclick="return confirm('Are you sure you want to reject it?')">Reject</button>
<?php endif; ?>
				</td>
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
<div class="card">
	<div class="card-body">
		<div class="alert alert-warning" role="alert">You have no items on this page.</div>
	</div>
</div>
<?php
}
require SITE_ROOT.'footer.php';
