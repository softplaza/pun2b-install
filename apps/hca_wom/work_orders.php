<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_wom', 1))
	message($lang_common['No permission']);

$access6 = ($User->checkAccess('hca_wom', 6)) ? true : false; // View

$HcaWOM = new HcaWOM;

$is_manager = ($User->get('property_access') != '' && $User->get('property_access') != 0) ? true : false;

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$search_by_unit_number = isset($_GET['unit_number']) ? swift_trim($_GET['unit_number']) : '';
$search_by_assigned_to = isset($_GET['assigned_to']) ? intval($_GET['assigned_to']) : 0;
$sort_by = isset($_GET['sort_by']) ? intval($_GET['sort_by']) : 0;

if (isset($_POST['add_task']))
{
	$work_order_id = isset($_POST['work_order_id']) ? intval($_POST['work_order_id']) : 0;

	$form_data = array(
		'work_order_id' 	=> $work_order_id,
		'item_id'			=> isset($_POST['item_id']) ? intval($_POST['item_id']) : 0,
		'task_action'		=> isset($_POST['task_action']) ? intval($_POST['task_action']) : 0,
		'assigned_to'		=> isset($_POST['assigned_to']) ? intval($_POST['assigned_to']) : 0,
		'task_message'		=> isset($_POST['task_message']) ? swift_trim($_POST['task_message']) : '',
		'task_init_created'	=> isset($_POST['task_init_created']) ? swift_trim($_POST['task_init_created']) : '',
		'time_created'		=> time(),
		'dt_created'		=> date('Y-m-d\TH:i:s'),
		'task_status'		=> 2 // set 2 as already accepted
	);

	if ($work_order_id == 0)
		$Core->add_error('Wrong Work Order ID number.');

	if (empty($Core->errors))
	{
		// Create task of Work Order
		$new_tid = $DBLayer->insert_values('hca_wom_tasks', $form_data);

		$query = array(
			'UPDATE'	=> 'hca_wom_work_orders',
			'SET'		=> 'num_tasks=num_tasks+1, last_task_id='.$new_tid,
			'WHERE'		=> 'id='.$work_order_id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		// notify when task assigned
		if ($form_data['assigned_to'] > 0 && $Config->get('o_hca_wom_notify_technician') == 1)
		{
			$task_info = $HcaWOM->getTaskInfo($new_tid);

			if (isset($task_info['assigned_email']) && $task_info['assigned_email'] != '')
			{
				$SwiftMailer = new SwiftMailer;
				$SwiftMailer->addReplyTo($User->get('email'), $User->get('realname')); //email, name
				//$SwiftMailer->isHTML();

				$mail_subject = 'Property Task #'.$task_info['id'];
				$mail_message = [];
				//$mail_message[] = 'Hello '.$task_info['assigned_name'];
				$mail_message[] = 'You have been assigned to a new task.';
				$mail_message[] = 'Property: '.$task_info['pro_name'];
				$mail_message[] = 'Unit: '.$task_info['unit_number'];
				
				if ($task_info['task_message'] != '')
					$mail_message[] = 'Details: '.$task_info['task_message']."\n";

				$mail_message[] = 'To complete the task follow the link:';
				$mail_message[] = $URL->link('hca_wom_task', $task_info['id']);

				$SwiftMailer->send($task_info['assigned_email'], $mail_subject, implode("\n", $mail_message));
			}
		}

		// Add flash message
		$flash_message = 'Task #'.$new_tid.' created.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['update_task']))
{
	$task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;

	$form_data = [];
	if (isset($_POST['task_message'])) $form_data['task_message'] = swift_trim($_POST['task_message']);
	if (isset($_POST['task_closing_comment'])) $form_data['task_closing_comment'] = swift_trim($_POST['task_closing_comment']);
	if (isset($_POST['item_id'])) $form_data['item_id'] = intval($_POST['item_id']);
	if (isset($_POST['task_action'])) $form_data['task_action'] = intval($_POST['task_action']);
	if (isset($_POST['assigned_to'])) $form_data['assigned_to'] = intval($_POST['assigned_to']);

	if (isset($_POST['task_init_closed']) && $_POST['task_init_closed'] != '') 
		$form_data['task_init_closed'] = swift_trim($_POST['task_init_closed']);

	if (empty($Core->errors) && $task_id > 0 && !empty($form_data))
	{
		// Update task of Work Order
		$DBLayer->update('hca_wom_tasks', $form_data, $task_id);

		// notify when task assigned
		if (isset($form_data['assigned_to']) && $form_data['assigned_to'] > 0 && $Config->get('o_hca_wom_notify_technician') == 1)
		{
			$task_info = $HcaWOM->getTaskInfo($task_id);

			$SwiftMailer = new SwiftMailer;
			$SwiftMailer->addReplyTo($User->get('email'), $User->get('realname')); //email, name
			//$SwiftMailer->isHTML();

			$mail_subject = 'Property Task #'.$task_info['id'];
			$mail_message = [];
			//$mail_message[] = 'Hello '.$task_info['assigned_name'];
			$mail_message[] = 'Task #'.$task_info['id'].' updated. See changes bellow.';
			$mail_message[] = 'Property: '.$task_info['pro_name'];
			$mail_message[] = 'Unit: '.$task_info['unit_number'];
			
			if (isset($task_info['task_message']) && $task_info['task_message'] != '')
				$mail_message[] = 'Details: '.$task_info['task_message']."\n";

			$mail_message[] = 'To complete the task follow the link:';
			$mail_message[] = $URL->link('hca_wom_task', $task_info['id']);

			$SwiftMailer->send($task_info['assigned_email'], $mail_subject, implode("\n", $mail_message));
		}
		
		// Add flash message
		$flash_message = 'Task #'.$task_id.' updated.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['cancel_task']))
{
	$work_order_id = isset($_POST['work_order_id']) ? intval($_POST['work_order_id']) : 0;
	$task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;

	$form_data = [
		'task_message'	=> isset($_POST['task_message']) ? swift_trim($_POST['task_message']) : '',
		'task_status'	=> 0, // canceled
	];

	if (isset($_POST['task_init_closed']) && $_POST['task_init_closed'] != '') 
		$form_data['task_init_closed'] = swift_trim($_POST['task_init_closed']);

	if (empty($Core->errors) && $task_id > 0 && $work_order_id > 0)
	{
		// Update task of Work Order
		$DBLayer->update('hca_wom_tasks', $form_data, $task_id);

		// Check if WO still has opened tasks, if not close WO automatically
		// Count only Opened task, NOT Canceled and NOT completed
		$num_rows = $DBLayer->getNumRows('hca_wom_tasks', 'work_order_id='.$work_order_id.' AND task_status < 4 AND task_status > 0');

		if ($num_rows == 0)
		{
			$DBLayer->update('hca_wom_work_orders', ['wo_status' => 2], $work_order_id); // set status as Closed

			$flash_message = 'Task #'.$task_id.' canceled and WO #'.$work_order_id.' closed.';
			$FlashMessenger->add_info($flash_message);
			redirect('', $flash_message);
		}

		// Add flash message
		$flash_message = 'Task #'.$task_id.' canceled.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['close_task']))
{
	$work_order_id = isset($_POST['work_order_id']) ? intval($_POST['work_order_id']) : 0;
	$task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;

	$form_data = [
		//'task_message'	=> isset($_POST['task_message']) ? swift_trim($_POST['task_message']) : '',
		'task_closing_comment'	=> isset($_POST['task_closing_comment']) ? swift_trim($_POST['task_closing_comment']) : '',
		'task_status'			=> 4, // closed
	];

	if (isset($_POST['task_init_closed']) && $_POST['task_init_closed'] != '') 
		$form_data['task_init_closed'] = swift_trim($_POST['task_init_closed']);

	if (empty($Core->errors) && $task_id > 0)
	{
		// Update task of Work Order
		$DBLayer->update('hca_wom_tasks', $form_data, $task_id);

		// Check if WO still has opened tasks, if not close WO automatically
		$num_rows = $DBLayer->getNumRows('hca_wom_tasks', 'work_order_id='.$work_order_id.' AND task_status < 4 AND task_status > 0');

		if ($num_rows == 0)
		{
			$DBLayer->update('hca_wom_work_orders', ['wo_status' => 2], $work_order_id); // set status as Closed

			$flash_message = 'WO #'.$work_order_id.' closed.';
			$FlashMessenger->add_info($flash_message);
			redirect('', $flash_message);
		}

		// Add flash message
		$flash_message = 'Task #'.$task_id.' closed.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

$search_query = $sort_by_query = [];
$search_query[] = 't.task_status!=0'; // Exclude canceled and On Hold
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

if ($sort_by == 1)
	$sort_by_query[] = 'p.pro_name, LENGTH(pu.unit_number), pu.unit_number';
else if ($sort_by == 2)
	$sort_by_query[] = 'p.pro_name, LENGTH(pu.unit_number) DESC, pu.unit_number DESC';
else if ($sort_by == 3)
	$sort_by_query[] = 'w.priority';
else if ($sort_by == 4)
	$sort_by_query[] = 'w.priority DESC';
else if ($sort_by == 5)
	$sort_by_query[] = 'w.dt_created';
else if ($sort_by == 6)
	$sort_by_query[] = 'w.dt_created DESC';

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
	//'ORDER BY'	=> 'p.pro_name, LENGTH(pu.unit_number), pu.unit_number, t.task_status DESC',
	'ORDER BY'	=> 'p.pro_name, t.task_status DESC, LENGTH(pu.unit_number), pu.unit_number',
];

if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
if (!empty($sort_by_query)) $query['ORDER BY'] = implode(', ', $sort_by_query);

$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$hca_wom_work_orders = $hca_wom_wo_ids = $tasks = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$hca_wom_work_orders[$row['id']] = $row;
	$hca_wom_wo_ids[$row['id']] = $row['id'];
	$tasks[] = $row['id'];
}
$PagesNavigator->num_items($hca_wom_work_orders);

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

$Core->set_page_id('hca_wom_work_orders', 'hca_fs');
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

<?php
$query = [
	'SELECT'	=> 't.*, w.dt_created, w.priority, p.pro_name, tp.type_name, pb.problem_name',
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
			'LEFT JOIN'		=> 'hca_wom_items AS i',
			'ON'			=> 'i.id=t.item_id'
		],
		[
			'LEFT JOIN'		=> 'hca_wom_types AS tp',
			'ON'			=> 'tp.id=i.item_type'
		],
		[
			'LEFT JOIN'		=> 'hca_wom_problems AS pb',
			'ON'			=> 'pb.id=t.task_action'
		],
	],
	'ORDER BY'	=> 't.dt_completed, t.time_created',
];
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$num_opened = $num_rfr = $num_total = 0;
$num_priority = [
	1 => 0,
	2 => 0,
	3 => 0,
	4 => 0
];
while ($row = $DBLayer->fetch_assoc($result))
{
	++$num_total;
	if ($row['task_status'] == 1 || $row['task_status'] == 2)
		++$num_opened;
	else if ($row['task_status'] == 3)
		++$num_rfr;

	++$num_priority[$row['priority']];

}
?>

<div class="alert d-flex justify-content-between row my-0 pt-0 pb-1">
	<div class="col-6">
		<div class="row">
			<div class="col badge badge-info mx-1 mb-1 border">
				<h3><?php echo $num_total ?></h3>
				<h6>Total tasks</h6>
			</div>
			<div class="col badge badge-warning mx-1 mb-1 border">
				<h3><?php echo $num_opened ?></h3>
				<h6>Open tasks</h6>
			</div>
			<div class="col badge badge-success mx-1 mb-1 border">
				<h3><?php echo $num_rfr ?></h3>
				<h6>Ready for Review</h6>
			</div>
		</div>
	</div>
	<div class="col-6">
		<div id="chart_1"></div>
	</div>
</div>

<div class="card-header d-flex justify-content-between">
	<h6 class="card-title mb-0">List of Work Orders</h6>
	<a href="<?=$URL->link('hca_wom_work_order_new', '')?>" class="badge bg-primary text-white" title="Create a new work order"><i class="fas fa-plus-circle text-white"></i> Work Order</a>
</div>

<?php
if (!empty($hca_wom_work_orders))
{
	$hca_wom_tasks = [];
	$query = [
		'SELECT'	=> 't.*, i.item_name, tp.type_name, pb.problem_name, u1.realname',
		'FROM'		=> 'hca_wom_tasks AS t',
		'JOINS'		=> [
			[
				'LEFT JOIN'		=> 'hca_wom_items AS i',
				'ON'			=> 'i.id=t.item_id'
			],
			[
				'LEFT JOIN'		=> 'hca_wom_types AS tp',
				'ON'			=> 'tp.id=i.item_type'
			],
			[
				'LEFT JOIN'		=> 'hca_wom_problems AS pb',
				'ON'			=> 'pb.id=t.task_action'
			],
			[
				'INNER JOIN'	=> 'users AS u1',
				'ON'			=> 'u1.id=t.assigned_to'
			],
		],
		'WHERE'		=> 't.task_status > 0 AND t.task_status < 4 AND t.work_order_id IN ('.implode(',', $hca_wom_wo_ids).')',
		'ORDER BY'	=> 't.task_status DESC',
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result))
	{
		$hca_wom_tasks[] = $row;
	}
?>

<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>WO #</th>
			<th><?php echo $URL->sortBy('Unit #', 1, 2) ?></th>
			<th>Task Information</th>
			<th><?php echo $URL->sortBy('Priority', 3, 4) ?></th>
			<th>Status</th>
			<th class="min-w-10"><?php echo $URL->sortBy('Submitted on', 5, 6) ?></th>
			<th>Tasks</th>
			<th>Print</th>
		</tr>
	</thead>
	<tbody>
<?php

	$property_id = 0;
	foreach ($hca_wom_work_orders as $cur_info)
	{
		$cur_info['unit_number'] = ($cur_info['unit_number'] != '') ? $cur_info['unit_number'] : 'Common area';

		if ($cur_info['priority'] == 4)
			$priority = '<span class="badge-danger text-danger fw-bold p-1 border border-danger">Emergency</span>';
		else if ($cur_info['priority'] == 3)
			$priority = '<span class="fw-bold" style="color:#fb4100">High</span>';
		else if ($cur_info['priority'] == 2)
			$priority = '<span class="text-warning fw-bold">Medium</span>';
		else
			$priority = '<span class="text-primary fw-bold">Low</span>';

		$status = '<span class="badge badge-warning">Open</span>';

		$task_info = [];
		if (!empty($hca_wom_tasks))
		{
			$i = 1;
			foreach($hca_wom_tasks as $cur_task)
			{
				if ($cur_info['id'] == $cur_task['work_order_id'])
				{
					$items = [];

					if ($cur_task['task_status'] == 3)
						$task_info[] = '<div class="callout callout-success rounded px-1 mb-1 min-w-15 position-relative">';
					else if ($cur_task['task_status'] == 0)
						$task_info[] = '<div class="callout callout-danger rounded px-1 mb-1 min-w-15 position-relative">';
					else
						$task_info[] = '<div class="callout callout-warning rounded px-1 mb-1 min-w-15 min-h-4 position-relative">';

					$task_info[] = '<p>';
					$task_info[] = '<span class="float-end" onclick="manageTask('.$cur_task['work_order_id'].','.$cur_task['id'].')" data-bs-toggle="modal" data-bs-target="#modalWindow"><i class="fas fa-edit fa-lg"></i></span>';
					$task_info[] = '<span class="fw-bold">'.html_encode($cur_task['type_name']).', </span>';
					$task_info[] = '<span class="fw-bold">'.html_encode($cur_task['item_name']).'</span>';
					$task_info[] = ' ('.html_encode($cur_task['problem_name']).')';
					$task_info[] = '</p>';

					if ($cur_task['task_status'] == 3)
						$task_info[] = '<span class="fw-bold text-success small position-absolute bottom-0 end-0">Ready for review</span>';
					else if ($cur_task['task_status'] == 0)
						$task_info[] = '<span class="fw-bold text-danger small position-absolute bottom-0 end-0">Canceled</span>';				
					else
						$task_info[] = '<span class="fw-bold text-warning small position-absolute bottom-0 end-0">Open</span>';

					if ($cur_task['task_message'] != '')
					{
						$task_info[] = '<p>';
						$task_info[] = html_encode($cur_task['task_message']);
						$task_info[] = ($cur_task['task_init_created'] != '') ? ' <span class="text-muted">['.html_encode($cur_task['task_init_created']).']</span>' : '';
						$task_info[] = '</p>';
					}

					if ($cur_task['task_status'] == 3 && $cur_task['tech_comment'] != '')
						$task_info[] = '<p class="text-muted">'.html_encode($cur_task['realname']).': ['.html_encode($cur_task['tech_comment']).']</p>';
					else
						$task_info[] = '<p class="text-muted">Assigned to: '.html_encode($cur_task['realname']).'</p>';

					$task_info[] = '</div>';

					if ($cur_task['task_status'] == 3)
						$status = '<span class="badge badge-success">Ready for review</span>';

					++$i;
				}
			}
		}

		if ($property_id != $cur_info['property_id'])
		{
			echo '<tr class="table-primary"><td colspan="8" class="fw-bold">'.html_encode($cur_info['pro_name']).'</td></tr>';
			$property_id = $cur_info['property_id'];
		}

		$view_wo = ($access6) ? '<p><a href="'.$URL->link('hca_wom_work_order', $cur_info['id']).'" class="badge bg-primary text-white">view</a></p>' : '';
?>
		<tr id="row<?php echo $cur_info['id'] ?>" class="<?php echo ($id == $cur_info['id'] ? ' anchor' : '') ?>">
			<td class="min-100">
				#<?php echo $cur_info['id'] ?>
				<?php echo $view_wo ?>
			</td>
			<td class="min-100 ta-center fw-bold"><?php echo html_encode($cur_info['unit_number']) ?></td>
			<td class="min-100">
				<?php echo implode("\n", $task_info) ?>
				<span class="float-end px-1" onclick="manageTask(<?php echo $cur_info['id'] ?>, 0)" data-bs-toggle="modal" data-bs-target="#modalWindow"><i class="fas fa-plus-circle fa-lg text-secondary"></i></span>
			</td>
			<td class="min-100 ta-center"><p><?php echo $priority ?></p></td>
			<td class="min-100 ta-center"><?php echo $status ?></td>
			<td class="min-100 ta-center"><?php echo format_date($cur_info['dt_created'], 'm/d/Y') ?></td>
			<td class="ta-center"><?php echo $cur_info['num_tasks'] ?></td>
			<td class="ta-center">
				<a href="<?=$URL->genLink('hca_wom_print', ['section' => 
'work_order', 'id' => $cur_info['id']])?>" target="_blank"><i class="fas fa-print fa-lg" aria-hidden="true"></i></a>
			</td>
		</tr>
<?php
	}
?>
	</tbody>
</table>

<div class="modal fade" id="modalWindow" tabindex="-1" aria-labelledby="modalWindowLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method="post" accept-charset="utf-8" action="">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
				<div class="modal-header">
					<h5 class="modal-title">Follow Up Date</h5>
					<button type="button" class="btn-close bg-danger" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<!--modal_fields-->
				</div>
				<div class="modal-footer">
					<!--modal_buttons-->
				</div>
			</form>
		</div>
	</div>
</div>

<script>
function manageTask(work_order_id,task_id)
{
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_wom_ajax_manage_task')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_wom_ajax_manage_task') ?>",
		type:	"POST",
		dataType: "json",
		data: ({work_order_id:work_order_id,task_id:task_id,csrf_token:csrf_token}),
		success: function(re){
			$(".modal-title").empty().html(re.modal_title);
			$(".modal-body").empty().html(re.modal_body);
			$(".modal-footer").empty().html(re.modal_footer);
		},
		error: function(re){
			document.getElementById("#brd-messages").innerHTML = re;
		}
	});
}
function getTaskTypeID(id)
{
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_wom_ajax_get_items')) ?>";
	var type_id = $("#fld_type_id_"+id).val();
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_wom_ajax_get_items') ?>",
		type:	"POST",
		dataType: "json",
		data: ({type_id:type_id,csrf_token:csrf_token}),
		success: function(re){
			$("#fld_item_id_"+id).empty().html(re.item_list);
			$("#fld_task_action_"+id).empty().html(re.actions);
		},
		error: function(re){
			document.getElementById("#fld_item_id_"+id).innerHTML = re;
		}
	});
}
function getTaskItemID(id)
{
	var item_id = $("#fld_item_id_"+id).val();
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_wom_ajax_get_items')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_wom_ajax_get_items') ?>",
		type:	"POST",
		dataType: "json",
		data: ({item_id:item_id,csrf_token:csrf_token}),
		success: function(re){
			$("#fld_task_action_"+id).empty().html(re.actions);
		},
		error: function(re){
			document.getElementById("#fld_task_action_"+id).innerHTML = re;
		}
	});
}
</script>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
var options1 = {
	series: [{
		data: [
			{
				x: 'Emergency',
				y: <?=$num_priority[4]?>
			},
			{
				x: 'High',
				y: <?=$num_priority[3]?>
			},
			{
				x: 'Medium',
				y: <?=$num_priority[2]?>
			},
			{
				x: 'Low',
				y: <?=$num_priority[1]?>
			},
		]
	}],
		legend: {
		show: false
	},
	chart: {
		height: 78,
		type: 'treemap',
		sparkline: {
			enabled: true // remove padding
		},
	},
	colors: ['#bd1525', '#fb4100', '#f0ad4e', '#2d7cef'],
	plotOptions: {
		treemap: {
		distributed: true,
		enableShades: false
		}
	},
	dataLabels: {
		enabled: true,
		formatter: function(text, opt) {
            return [opt.value, text]
        },
		style: {
			colors: ["#c9feff"],
			fontSize: '16px',
		},
		offsetY: -7
	},
};
var chart = new ApexCharts(document.querySelector("#chart_1"), options1);
chart.render();
</script>

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
