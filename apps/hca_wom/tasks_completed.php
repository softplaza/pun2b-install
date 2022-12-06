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

if (isset($_POST['accept_task']))
{
	$task_id = isset($_POST['accept_task']) ? intval(key($_POST['accept_task'])) : 0;
	$form_data = [
		'task_status'		=> 2,
	];
	$DBLayer->update('hca_wom_tasks', $form_data, $task_id);

	// Add flash message
	$flash_message = 'Task #'.$task_id.' has been accepted by '.$User->get('realname').'.';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

$search_query = [];
$search_query[] = 't.task_status > 2'; // Exclude completed
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
			'LEFT JOIN'		=> 'sm_property_units AS pu',
			'ON'			=> 'pu.id=w.unit_id'
		],
		[
			'INNER JOIN'	=> 'users AS u2',
			'ON'			=> 'u2.id=w.requested_by'
		],
		[
			'LEFT JOIN'		=> 'users AS u1',
			'ON'			=> 'u1.id=t.assigned_to'
		],
	],
];
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$property_ids = [];
$query = [
	'SELECT'	=> 't.*, w.property_id, w.unit_id, w.wo_message, p.pro_name, pu.unit_number, i.item_name, u1.realname AS assigned_name, u1.email AS assigned_email, u2.realname AS requested_name, u2.email AS requested_email',
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
			'LEFT JOIN'		=> 'sm_property_units AS pu',
			'ON'			=> 'pu.id=w.unit_id'
		],
		[
			'LEFT JOIN'		=> 'hca_wom_items AS i',
			'ON'			=> 'i.id=t.task_item'
		],
		[
			'LEFT JOIN'		=> 'users AS u1',
			'ON'			=> 'u1.id=t.assigned_to'
		],
		[
			'INNER JOIN'	=> 'users AS u2',
			'ON'			=> 'u2.id=w.requested_by'
		],
	],
	'LIMIT'		=> $PagesNavigator->limit(),
	'ORDER BY'	=> 'p.pro_name, LENGTH(pu.unit_number), pu.unit_number',
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

<div class="card-header">
	<h6 class="card-title mb-0">List of Completed Tasks</h6>
</div>
<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
	<table class="table table-striped table-bordered">
		<thead>
			<tr>
				<th class="min-100">Unit</th>
				<th>Details</th>
			</tr>
		</thead>
		<tbody>
<?php

	$property_id = 0;
	foreach ($hca_wom_work_orders as $cur_info)
	{
		$task_id_number = ($cur_info['task_status'] == 4) ? '<a href="'.$URL->link('hca_wom_task', $cur_info['id']).'" class="badge badge-secondary">Task #'.$cur_info['id'].' Closed</a>' : '<a href="'.$URL->link('hca_wom_task', $cur_info['id']).'" class="badge badge-success">Task #'.$cur_info['id'].' Completed</a>';

		$unit_number = ($cur_info['unit_id'] > 0) ? html_encode($cur_info['unit_number']) : 'Common area';

		if ($property_id != $cur_info['property_id'])
		{
			echo '<tr class="table-success"><td colspan="2" class="fw-bold">'.html_encode($cur_info['pro_name']).'</td></tr>';
			$property_id = $cur_info['property_id'];
		}
?>
			<tr>
				<td class="ta-center">
					<p class="fw-bold"><?php echo $unit_number ?></p>
					<p class="fw-bold"><?php echo $task_id_number ?></p>
				</td>
				<td>
					<p class="fw-bold"><?php echo html_encode($cur_info['item_name']) ?></p>
					<p class=""><?php echo html_encode($cur_info['task_message']) ?></p>
					<p class="float-end text-muted fst-italic"><?php echo format_time($cur_info['time_created']) ?></p>
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
