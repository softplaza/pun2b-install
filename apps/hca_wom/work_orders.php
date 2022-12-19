<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_wom', 1))
	message($lang_common['No permission']);

$access6 = ($User->checkAccess('hca_wom', 6)) ? true : false;

$HcaWOM = new HcaWOM;

$is_manager = ($User->get('property_access') != '' && $User->get('property_access') != 0) ? true : false;

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$search_by_unit_number = isset($_GET['unit_number']) ? swift_trim($_GET['unit_number']) : '';
$search_by_assigned_to = isset($_GET['assigned_to']) ? intval($_GET['assigned_to']) : 0;

$search_query = [];
$search_query[] = 't.task_status!=4'; // Exclude completed

if ($is_manager)
{
	$property_ids = explode(',', $User->get('property_access'));
	$search_query[] = 'w.property_id IN ('.implode(',', $property_ids).')';
}

if ($search_by_property_id > 0)
	$search_query[] = 'w.property_id='.$search_by_property_id;

if ($search_by_unit_number != '')
	$search_query[] = 'pu.unit_number=\''.$DBLayer->escape($search_by_unit_number).'\'';

if ($search_by_assigned_to > 0)
	$search_query[] = 't.assigned_to='.$search_by_assigned_to;

$query = [
	'SELECT'	=> 'COUNT(w.id)',
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
			'INNER JOIN'	=> 'users AS u2',
			'ON'			=> 'u2.id=w.requested_by'
		],
		[
			'LEFT JOIN'		=> 'sm_property_units AS pu',
			'ON'			=> 'pu.id=w.unit_id'
		],
/*
		[
			'INNER JOIN'	=> 'users AS u1',
			'ON'			=> 'u1.id=w.assigned_to'
		],
*/

	],
];
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$query = [
	'SELECT'	=> 'w.*, p.pro_name, pu.unit_number, u2.realname AS requested_name, u2.email AS requested_email', // u1.realname AS assigned_name, u1.email AS assigned_email,
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
			'INNER JOIN'	=> 'users AS u2',
			'ON'			=> 'u2.id=w.requested_by'
		],
		[
			'LEFT JOIN'		=> 'sm_property_units AS pu',
			'ON'			=> 'pu.id=w.unit_id'
		],
/*
		[
			'INNER JOIN'	=> 'users AS u1',
			'ON'			=> 'u1.id=w.assigned_to'
		],
*/

	],
	'LIMIT'		=> $PagesNavigator->limit(),
	'ORDER BY'	=> 't.task_status DESC, p.pro_name, LENGTH(pu.unit_number), pu.unit_number',
];
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$hca_wom_work_orders = $hca_wom_wo_ids = $tasks = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$hca_wom_work_orders[$row['id']] = $row;
	$hca_wom_wo_ids[$row['id']] = $row['id'];
	$tasks[] = $row['id'];
}
$PagesNavigator->num_items($tasks);

$hca_wom_tasks = [];
if (!empty($hca_wom_wo_ids))
{
	$query = [
		'SELECT'	=> 't.*, i.item_name, pb.problem_name',
		'FROM'		=> 'hca_wom_tasks AS t',
		'JOINS'		=> [
			[
				'LEFT JOIN'		=> 'hca_wom_items AS i',
				'ON'			=> 'i.id=t.item_id'
			],
			[
				'LEFT JOIN'		=> 'hca_wom_problems AS pb',
				'ON'			=> 'pb.id=t.task_action'
			],
		],
		'WHERE'		=> 't.work_order_id IN ('.implode(',', $hca_wom_wo_ids).')',
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result))
	{
		$hca_wom_tasks[] = $row;
	}
}

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

$query = array(
	'SELECT'	=> 'u.id, u.group_id, u.username, u.realname, u.email, g.g_id, g.g_title',
	'FROM'		=> 'groups AS g',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'g.g_id=u.group_id'
		)
	),
	'WHERE'		=> 'u.group_id=3',
	'ORDER BY'	=> 'g.g_id, u.realname',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users = [];
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$users[] = $fetch_assoc;
}

$Core->set_page_id('hca_wom_work_orders', 'hca_wom');
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
				<div class="col-md-auto pe-0 mb-1">
					<input name="unit_number" type="text" value="<?php echo isset($_GET['unit_number']) ? $_GET['unit_number'] : '' ?>" placeholder="Unit #" class="form-control form-control-sm" size="5">
				</div>

				<div class="col-md-auto pe-0 mb-1">
					<select name="assigned_to" class="form-select form-select-sm">
						<option value="0">Technician</option>
<?php
$optgroup = 0;
foreach ($users as $cur_user)
{
	if ($cur_user['group_id'] != $optgroup) {
		if ($optgroup) {
			echo '</optgroup>';
		}
		echo '<optgroup label="'.html_encode($cur_user['g_title']).'">';
		$optgroup = $cur_user['group_id'];
	}

	if ($search_by_assigned_to == $cur_user['id'])
		echo '<option value="'.$cur_user['id'].'" selected>'.$cur_user['realname'].'</option>';
	else
		echo '<option value="'.$cur_user['id'].'">'.$cur_user['realname'].'</option>';
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
	<h6 class="card-title mb-0">List of Work Orders</h6>
</div>

<?php
if (!empty($hca_wom_work_orders))
{
?>

<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>WO #</th>
			<th>Property/Unit</th>
			<th>Details</th>
			<th>Priority</th>
			<th>Status</th>
			<th>Submitted on</th>
			<th>Tasks</th>
		</tr>
	</thead>
	<tbody>
<?php
	foreach ($hca_wom_work_orders as $cur_info)
	{
		$cur_info['unit_number'] = ($cur_info['unit_number'] != '') ? $cur_info['unit_number'] : 'Common area';

		$manage_link = ($access6) ? '<span class="badge bg-primary float-end"><a href="'.$URL->link('hca_wom_work_order', $cur_info['id']).'" class="text-white"><i class="fas fa-edit"></i> view</span>' : '';

		if ($cur_info['priority'] == 2)
			$priority = '<span class="text-danger fw-bold">High</span>';
		else if ($cur_info['priority'] == 1)
			$priority = '<span class="text-warning fw-bold">Medium</span>';
		else
			$priority = '<span class="text-primary fw-bold">Low</span>';

		$status = '<span class="badge badge-warning">Open</span>';

		$task_title = [];
		if (!empty($hca_wom_tasks))
		{
			//$i = 1;
			foreach($hca_wom_tasks as $cur_task)
			{
				if ($cur_info['id'] == $cur_task['work_order_id'])
				{
					$task_title[] = '<p>'.html_encode($cur_task['item_name']).' ('.html_encode($cur_task['problem_name']).')</p>';

					if ($cur_task['task_status'] == 3)
						$status = '<span class="badge badge-success">Ready for review</span>';

					//++$i;
				}
			}
		}
?>
		<tr id="row<?php echo $cur_info['id'] ?>" class="<?php echo ($id == $cur_info['id'] ? ' anchor' : '') ?>">
			<td class="ta-center">#<?php echo $cur_info['id'] ?></td>
			<td>
				<span class="fw-bold"><?php echo html_encode($cur_info['pro_name']) ?>, <?php echo html_encode($cur_info['unit_number']) ?></span>
				<?=$manage_link?>
			</td>
			<td class="min-100"><?php echo implode("\n", $task_title) ?></td>
			<td class="min-100 ta-center"><?php echo $priority ?></td>
			<td class="min-100 ta-center"><?php echo $status ?></td>
			<td class="min-100 ta-center"><?php echo format_date($cur_info['dt_created'], 'm/d/Y') ?></td>
			<td class="ta-center"><?php echo $cur_info['num_tasks'] ?></td>
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
