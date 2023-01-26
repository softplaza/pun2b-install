<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_wom', 5))
	message($lang_common['No permission']);

$section = isset($_GET['section']) ? $_GET['section'] : null;
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;

$HcaWOM = new HcaWOM;

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

if ($section == 'unassigned')
	$search_query[] = 't.task_status=1';
else if ($section == 'completed')
	$search_query[] = 't.task_status>2';
else
	$search_query[] = 't.task_status < 3 AND t.task_status > 0';

if (in_array($User->get('group_id'), [3,9]))
	$search_query[] = 't.assigned_to='.$User->get('id');

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
			'INNER JOIN'	=> 'users AS u2',
			'ON'			=> 'u2.id=w.requested_by'
		],
		[
			'INNER JOIN'	=> 'users AS u1',
			'ON'			=> 'u1.id=t.assigned_to'
		],
		[
			'LEFT JOIN'		=> 'sm_property_units AS pu',
			'ON'			=> 'pu.id=w.unit_id'
		],
	],
];
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$property_ids = [];
$query = [
	'SELECT'	=> 't.*, w.property_id, w.unit_id, w.wo_message, w.priority, p.pro_name, pu.unit_number, i.item_name, pb.problem_name, u1.realname AS assigned_name, u1.email AS assigned_email, u2.realname AS requested_name, u2.email AS requested_email',
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
			'INNER JOIN'	=> 'users AS u1',
			'ON'			=> 'u1.id=t.assigned_to'
		],
		[
			'LEFT JOIN'		=> 'sm_property_units AS pu',
			'ON'			=> 'pu.id=w.unit_id'
		],
		[
			'LEFT JOIN'		=> 'hca_wom_items AS i',
			'ON'			=> 'i.id=t.item_id'
		],
		[
			'LEFT JOIN'		=> 'hca_wom_problems AS pb',
			'ON'			=> 'pb.id=t.task_action'
		],
	],
	'LIMIT'		=> $PagesNavigator->limit(),
	//'ORDER BY'	=> 'p.pro_name, w.priority DESC, LENGTH(pu.unit_number), pu.unit_number',
	'ORDER BY'	=> 'w.priority DESC, t.time_created',
];
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$hca_wom_tasks = $property_ids = [];
while ($row = $DBLayer->fetch_assoc($result))
{
	$property_ids[] = $row['property_id'];
	$hca_wom_tasks[] = $row;
}
$PagesNavigator->num_items($hca_wom_tasks);

if ($section == 'unassigned')
	$Core->set_page_id('hca_wom_tasks_unassigned', 'hca_fs');
else if ($section == 'active')
	$Core->set_page_id('hca_wom_tasks_active', 'hca_fs');
else
	$Core->set_page_id('hca_wom_tasks_completed', 'hca_fs');

require SITE_ROOT.'header.php';

$property_info = [];
$query = array(
	'SELECT'	=> 'p.*',
	'FROM'		=> 'sm_property_db AS p',
	//'WHERE'		=> 'p.id IN ('.implode(',', $property_ids).')',
	'ORDER BY'	=> 'p.pro_name'
);
if (!empty($property_ids)) 
{
	$query['WHERE'] = 'p.id IN ('.implode(',', $property_ids).')';
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		$property_info[] = $fetch_assoc;
	}
}
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
					<a href="<?php echo $URL->genLink('hca_wom_tasks', ['section' => 'active']) ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
				</div>
			</div>
		</div>
	</form>
</nav>

<?php
if ($section == 'unassigned')
	$title = 'List of Unassigned Tasks';
else if ($section == 'active')
	$title = 'To-Do List';
else
	$title = 'List of Completed Tasks';
?>
<div class="card-header">
	<h6 class="card-title mb-0"><?php echo $title ?></h6>
</div>

<?php
if (!empty($hca_wom_tasks))
{
?>
<div class="row">
	<div class="col-4 ta-center fw-bold py-1 border alert-primary">Unit</div>
	<div class="col-8 ta-center fw-bold py-1 border alert-primary">Details</div>
</div>

<?php
	$property_id = 0;
	foreach ($hca_wom_tasks as $cur_info)
	{
		$unit_number = ($cur_info['unit_id'] > 0) ? 'Unit #'.html_encode($cur_info['unit_number']) : 'Common area';

		if ($cur_info['priority'] == 4)
			$priority = '<span class="badge-danger text-danger fw-bold p-1 border border-danger">Emergency</span>';
		else if ($cur_info['priority'] == 3)
			$priority = '<span class="text-danger fw-bold">High priority</span>';
		else if ($cur_info['priority'] == 2)
			$priority = '<span class="text-warning fw-bold">Medium</span>';
		else
			$priority = '<span class="text-primary fw-bold">Low</span>';

		if ($property_id != $cur_info['property_id'])
		{
			echo '<div class="fw-bold py-1 alert-warning">'.html_encode($cur_info['pro_name']).'</div>';
			$property_id = $cur_info['property_id'];
		}
?>
	<a href="<?=$URL->link('hca_wom_task', $cur_info['id'])?>" class="row">
		<div class="col-4 border">
			<p class="h5 mb-1"><?=$unit_number?></p>
			<?=$priority?>
		</div>
		<div class="col-8 border">
			<p><span class="fw-bold"><?php echo html_encode($cur_info['item_name']) ?></span> (<?php echo html_encode($cur_info['problem_name']) ?>)</p>
			<p class=""><?php echo html_encode($cur_info['task_message']) ?></p>
			<p class="float-end text-muted fst-italic"><?php echo format_time($cur_info['time_created']) ?></p>
		</div>
	</a>
<?php
	}
?>

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
