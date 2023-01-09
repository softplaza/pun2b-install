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

if (isset($_POST['add_task']))
{
	$work_order_id = isset($_POST['work_order_id']) ? intval($_POST['work_order_id']) : 0;

	$form_data = array(
		'work_order_id' => $work_order_id,
		'item_id'		=> isset($_POST['item_id']) ? intval($_POST['item_id']) : 0,
		'task_action'	=> isset($_POST['task_action']) ? intval($_POST['task_action']) : 0,
		'assigned_to'	=> isset($_POST['assigned_to']) ? intval($_POST['assigned_to']) : 0,
		'task_message'	=> isset($_POST['task_message']) ? swift_trim($_POST['task_message']) : '',
		'time_created'	=> time(),
		'task_status'	=> 2 // set 2 as already accepted
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
				//$SwiftMailer->isHTML();

				$mail_subject = 'Property Task #'.$task_info['id'];
				$mail_message = [];
				$mail_message[] = 'Hello '.$task_info['assigned_name'];
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
		$flash_message = 'Task #'.$new_tid.' was created.';
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

	//if ($form_data['assigned_to'] == 0)
	//	$Core->add_error('Select technician.');

	if (empty($Core->errors) && $task_id > 0 && !empty($form_data))
	{
		// Update task of Work Order
		$DBLayer->update('hca_wom_tasks', $form_data, $task_id);

		// notify when task assigned
		if (isset($form_data['assigned_to']) && $form_data['assigned_to'] > 0 && $Config->get('o_hca_wom_notify_technician') == 1)
		{
			$task_info = $HcaWOM->getTaskInfo($task_id);

			$SwiftMailer = new SwiftMailer;
			//$SwiftMailer->isHTML();

			$mail_subject = 'Property Task #'.$task_info['id'];
			$mail_message = [];
			$mail_message[] = 'Hello '.$task_info['assigned_name'];
			$mail_message[] = 'You have been assigned to a new task.';
			$mail_message[] = 'Property: '.$task_info['pro_name'];
			$mail_message[] = 'Unit: '.$task_info['unit_number'];
			
			if (isset($task_info['task_message']) && $task_info['task_message'] != '')
				$mail_message[] = 'Details: '.$task_info['task_message']."\n";

			$mail_message[] = 'To complete the task follow the link:';
			$mail_message[] = $URL->link('hca_wom_task', $task_info['id']);

			$SwiftMailer->send($task_info['assigned_email'], $mail_subject, implode("\n", $mail_message));
		}
		
		// Add flash message
		$flash_message = 'Task #'.$task_id.' was updated.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}
else if (isset($_POST['close_task']))
{
	$task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;

	$form_data = [
		//'task_message'	=> isset($_POST['task_message']) ? swift_trim($_POST['task_message']) : '',
		'task_closing_comment'	=> isset($_POST['task_closing_comment']) ? swift_trim($_POST['task_closing_comment']) : '',
		'task_status'			=> 4,
	];

	if (empty($Core->errors) && $task_id > 0)
	{
		// Update task of Work Order
		$DBLayer->update('hca_wom_tasks', $form_data, $task_id);

		// Add flash message
		$flash_message = 'Task #'.$task_id.' has been closed.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

$search_query = [];
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

<div class="card-header ">
	<h6 class="card-title mb-0">List of Work Orders</h6>
</div>

<?php
if (!empty($hca_wom_work_orders))
{
	$hca_wom_tasks = [];
	$query = [
		'SELECT'	=> 't.*, i.item_name, tp.type_name, pb.problem_name',
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
		],
		'WHERE'		=> 't.task_status < 4 AND t.work_order_id IN ('.implode(',', $hca_wom_wo_ids).')',
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
			<th>Unit #</th>
			<th>Task Information</th>
			<th>Priority</th>
			<th>Status</th>
			<th>Submitted on</th>
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

		if ($cur_info['priority'] == 2)
			$priority = '<span class="text-danger fw-bold">High</span>';
		else if ($cur_info['priority'] == 1)
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
					else
						$task_info[] = '<div class="callout callout-warning rounded px-1 mb-1 min-w-15 position-relative">';

					$task_info[] = '<p>';
					$task_info[] = '<span class="float-end" onclick="quickManageTask(0,'.$cur_task['id'].')" data-bs-toggle="modal" data-bs-target="#modalWindow"><i class="fas fa-edit fa-lg"></i></span>';
					$task_info[] = '<span class="fw-bold">'.html_encode($cur_task['type_name']).', </span>';
					$task_info[] = '<span class="fw-bold">'.html_encode($cur_task['item_name']).'</span>';
					$task_info[] = ' ('.html_encode($cur_task['problem_name']).')';
					$task_info[] = '</p>';

					if ($cur_task['task_status'] == 3)
						$task_info[] = '<span class="fw-bold text-success small position-absolute bottom-0 end-0">Ready for review</span>';
					else
						$task_info[] = '<span class="fw-bold text-warning small position-absolute bottom-0 end-0">Open</span>';

					$task_info[] = '<p>'.html_encode($cur_task['task_message']).'</p>';

					if ($cur_task['task_status'] == 3 && $cur_task['tech_comment'] != '')
						$task_info[] = '<p class="text-muted">[ '.html_encode($cur_task['tech_comment']).' ]</p>';

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
				<span class="float-end px-1" onclick="quickManageTask(<?=$cur_info['id']?>,0)" data-bs-toggle="modal" data-bs-target="#modalWindow"><i class="fas fa-plus-circle fa-lg text-secondary"></i></span>
			</td>
			<td class="min-100 ta-center"><?php echo $priority ?></td>
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
					<button type="button" class="btn-close bg-danger" data-bs-dismiss="modal" aria-label="Close" onclick="clearModalFields()"></button>
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
function quickManageTask(work_order_id,task_id)
{
	$(".modal-body").empty().html('');
	$(".modal-footer").empty().html('');
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_wom_ajax_quick_manage_task')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_wom_ajax_quick_manage_task') ?>",
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
function getActions(){
	var item_id = $("#fld_item_id").val();
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_wom_ajax_get_items')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_wom_ajax_get_items') ?>",
		type:	"POST",
		dataType: "json",
		data: ({item_id:item_id,csrf_token:csrf_token}),
		success: function(re){
			$("#fld_task_action").empty().html(re.item_actions);

		},
		error: function(re){
			document.getElementById("#fld_task_action").innerHTML = re;
		}
	});
}
function clearModalFields(){}
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
