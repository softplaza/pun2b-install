<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_hvac_inspections', 1)) ? true : false;
$access20 = ($User->checkAccess('hca_hvac_inspections', 20)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$unit_id = isset($_GET['unit_id']) ? intval($_GET['unit_id']) : 0;

$HcaHVACInspections = new HcaHVACInspections;

if (isset($_POST['update']))
{
	$form_data = [
		'updated_by'				=> $User->get('id'),
		'updated_time'				=> time(),
		'work_order_completed'		=> isset($_POST['work_order_completed']) ? intval($_POST['work_order_completed']) : 0,
		'work_order_comment'		=> isset($_POST['work_order_comment']) ? swift_trim($_POST['work_order_comment']) : '',
		'filter_size_id'			=> isset($_POST['filter_size_id']) ? intval($_POST['filter_size_id']) : 0,
	];

	$inspections_checklist = $DBLayer->select('hca_hvac_inspections_checklist', $id);
	if ($inspections_checklist['inspection_completed'] != 2 && $form_data['inspection_completed'] == 2)
	{
		$form_data['datetime_inspection_end'] = date('Y-m-d\TH:i:s');
		$form_data['completed_by'] = $User->get('id');
	}

	// Form Validations
	if ($form_data['work_order_completed'] == 1 && $form_data['work_order_comment'] == '')
		$Core->add_error('Please provide a reason: Why the Work Order not completed.');

	if ($form_data['work_order_completed'] == 0)
		$Core->add_error('Choose one of the options: "Is the checklist completed?".');

	$work_order_status_ident = true;
	if (isset($_POST['comment']) && !empty($_POST['comment']))
	{
		foreach($_POST['comment'] as $key => $problem)
		{
			$checklist_item = [
				//'check_type'	=> isset($_POST['check_type'][$key]) ? intval($_POST['check_type'][$key]) : 0,
				'job_type'		=> isset($_POST['job_type'][$key]) ? intval($_POST['job_type'][$key]) : 0,
				'comment'		=> isset($_POST['comment'][$key]) ? swift_trim($_POST['comment'][$key]) : '',
			];
			$DBLayer->update('hca_hvac_inspections_checklist_items', $checklist_item, $key);

			if ($checklist_item['job_type'] == 0)
				$work_order_status_ident = false;
		}
	}

	if (!$work_order_status_ident && $form_data['work_order_completed'] == 2)
		$Core->add_error('To compleete the Work Order you must check all items or leave the Work Order as not completed with your comments.');

	if (empty($Core->errors))
	{
		$DBLayer->update('hca_hvac_inspections_checklist', $form_data, $id);

		// Add flash message
		$flash_message = 'Checklist was completed by '.$User->get('realname');

		$action_data = [
			'checklist_id'			=> $id,
			'submitted_by'			=> $User->get('id'),
			'time_submitted'		=> time(),
			'action'				=> $flash_message
		];
		$DBLayer->insert_values('hca_hvac_inspections_actions', $action_data);

		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_hvac_inspections_work_order', $id), $flash_message);
	}
}

else if (isset($_POST['delete']))
{
	if ($id > 0)
	{
		$DBLayer->delete('hca_hvac_inspections_checklist', $id);

		$query = array(
			'DELETE'	=> 'hca_hvac_inspections_checklist_items',
			'WHERE'		=> 'checklist_id='.$id
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);

		$query = array(
			'DELETE'	=> 'hca_hvac_inspections_actions',
			'WHERE'		=> 'checklist_id='.$id
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);

		// Add flash message
		$flash_message = 'CheckList #'.$id.' was deleted by '.$User->get('realname');
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_hvac_inspections_inspections', 0), $flash_message);
	}
}

$query = array(
	'SELECT'	=> 'p.*',
	'FROM'		=> 'sm_property_db AS p',
	'WHERE'		=> 'p.enabled=1',
	'ORDER BY'	=> 'p.pro_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$sm_property_db = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$sm_property_db[] = $row;
}

$Core->set_page_id('hca_hvac_inspections_work_order', 'hca_hvac_inspections');
require SITE_ROOT.'header.php';

$query = [
	'SELECT'	=> 'ch.*, u1.realname AS inspected_name, u2.realname AS completed_name, p.pro_name, un.unit_number, un.unit_type, un.mbath, un.hbath',
	'FROM'		=> 'hca_hvac_inspections_checklist AS ch',
	'JOINS'		=> [
		[
			'LEFT JOIN'		=> 'users AS u1',
			'ON'			=> 'u1.id=ch.inspected_by'
		],
		[
			'LEFT JOIN'		=> 'users AS u2',
			'ON'			=> 'u2.id=ch.completed_by'
		],
		[
			'LEFT JOIN'		=> 'sm_property_db AS p',
			'ON'			=> 'p.id=ch.property_id'
		],
		[
			'LEFT JOIN'		=> 'sm_property_units AS un',
			'ON'			=> 'un.id=ch.unit_id'
		],
	],
	'WHERE'		=> 'ch.id='.$id
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $DBLayer->fetch_assoc($result);

$query = [
	'SELECT'	=> 'ci.*, i.item_name, i.equipment_id, i.req_appendixb, i.item_type, i.job_actions, i.comment_required',
	'FROM'		=> 'hca_hvac_inspections_items AS i',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'hca_hvac_inspections_checklist_items AS ci',
			'ON'			=> 'i.id=ci.item_id'
		],
	],
	'WHERE'		=> 'ci.checklist_id='.$id,
	'ORDER BY'	=> 'i.equipment_id, i.display_position'
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$checked_items = [];
while($row = $DBLayer->fetch_assoc($result))
{
	$checked_items[] = $row;
}
?>

<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">HVAC Work Order #<?=$main_info['id']?></h6>
		</div>
		<div class="card-body pb-0">
			<div class="row">
				<div class="col-md-3">
					<label class="form-label">Property name</label>
					<h5><?php echo html_encode($main_info['pro_name']) ?><h5>
				</div>
				<div class="col-md-3">
					<label class="form-label">Unit number</label>
					<h5><?php echo html_encode($main_info['unit_number']) ?><h5>
				</div>
				<div class="col-md-3">
					<label class="form-label">Unit size</label>
					<h5><?php echo html_encode($main_info['unit_type']) ?><h5>
				</div>
			</div>
			<div class="row">
				<div class="col-md-3">
					<label class="form-label">Date ispected:</label>
					<h5><?php echo format_date($main_info['datetime_inspection_start'], 'n/j/Y H:i') ?></h5>
				</div>
				<div class="col-md-3">
					<label class="form-label">Inspected by:</label>
					<h5><?php echo html_encode($main_info['inspected_name']) ?><h5>
				</div>
				<div class="col-md-3">
					<label class="form-label">Date completed:</label>
					<h5><?php echo format_date($main_info['datetime_inspection_end'], 'n/j/Y H:i') ?></h5>
				</div>
			</div>
		</div>

		<div class="card-body pb-0">

<?php if ($main_info['work_order_completed'] == 2) : ?>
			<div class="alert alert-success" role="alert">The Work Order already was completed.</div>
<?php elseif ($main_info['work_order_completed'] == 1): ?>
			<div class="alert alert-warning" role="alert">Complete the Work Order and press "Submit".</div>
<?php else: ?>
			<div class="alert alert-danger" role="alert">Work Order not created yet. Checklist was completed with no issues.</div>
<?php endif; ?>

<?php
$query = array(
	'SELECT'	=> 'f.*, p.pro_name',
	'FROM'		=> 'hca_hvac_inspections_filters AS f',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'sm_property_db AS p',
			'ON'			=> 'p.id=f.property_id'
		],
	],
	'WHERE'		=> 'p.id='.$main_info['property_id'],
	'ORDER BY'	=> 'p.pro_name',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$hca_hvac_inspections_filters = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$hca_hvac_inspections_filters[] = $row;
}

$req_appendixb = false;
$equipment_id = 0;
foreach($checked_items as $cur_info)
{
	$item_body = [];

	if ($equipment_id != $cur_info['equipment_id'])
	{
		$equipment_name = isset($HcaHVACInspections->equipments[$cur_info['equipment_id']]) ? $HcaHVACInspections->equipments[$cur_info['equipment_id']] : '';

		$item_body[] = '<div class="row mt-1">';
		$item_body[] = '<div class="col-3">';
		$item_body[] = '<span class="fw-bold">'.html_encode($equipment_name).'</span>';
		$item_body[] = '</div>';
		$item_body[] = '<div class="col-2 ta-center">';
		$item_body[] = '<span class="fw-bold"></span>';
		$item_body[] = '</div>';
		$item_body[] = '<div class="col-2 ta-center">';
		$item_body[] = '<span class="fw-bold">Action</span>';
		$item_body[] = '</div>';
		$item_body[] = '<div class="col-5"></div>';
		$item_body[] = '</div>';
	}

	if ($cur_info['item_id'] == 1 && in_array($main_info['property_id'], [100, 111]) || $cur_info['item_id'] > 1)
	{
		$filter_sizes = [];
		if (!empty($hca_hvac_inspections_filters) && $cur_info['item_id'] == 10)
		{
			$filter_sizes[] = '<span>Filter size: </span>';
			$filter_sizes[] = '<select name="filter_size_id" required class="form-select form-select-sm alert-warning fw-bold fld-required">';
			$filter_sizes[] = '<option value="">Select one</option>';
			foreach($hca_hvac_inspections_filters as $filters)
			{
				if ($main_info['filter_size_id'] == $filters['id'])
					$filter_sizes[] =  '<option value="'.$filters['id'].'" selected>'.html_encode($filters['filter_size']).'</option>';
				else
					$filter_sizes[] =  '<option value="'.$filters['id'].'">'.html_encode($filters['filter_size']).'</option>';
			}
			$filter_sizes[] = '</select>';
			$filter_sizes[] = '<div class="invalid-tooltip">Please select filter size from dropdown.</div>';
		}

		$item_type1 = ($cur_info['item_type'] == 2) ? 1 : 2;
		$item_type2 = ($cur_info['item_type'] == 2) ? 2 : 1;

		$check_type_param1 = $check_type_param2 = [];

		$check_type_param1[] = ($cur_info['item_type'] == 2) ? 1 : 2;
		$check_type_param2[] = ($cur_info['item_type'] == 2) ? 2 : 1;

		$check_type_param1[] = $check_type_param2[] = $cur_info['id'];
		$check_type_param1[] = $check_type_param2[] = $cur_info['comment_required'];

		$item_body[] = '<div class="row">';
		$item_body[] = '<div class="col-3">';
		$item_body[] = '<span class="">'.html_encode($cur_info['item_name']).'</span>';
		$item_body[] = '<p><?php echo implode("\n", $filter_sizes) ?></p>';
		$item_body[] = '</div>';
		$item_body[] = '<input type="hidden" name="check_type['.$cur_info['id'].']" value="0">';
		$item_body[] = '<div class="col-2 alert-info ta-center">';
		$item_body[] = '<span class="fw-bold">'.($cur_info['check_type'] == 2 ? 'Yes' : 'No').'</span>';
		$item_body[] = '</div>';

		$job_actions = [];
		$item_job_actions = explode(',', $cur_info['job_actions']);
		foreach($HcaHVACInspections->actions as $key => $value)
		{
			if (in_array($key, $item_job_actions))
				$job_actions[$key] = $value;
		}

		if (!empty($job_actions))
		{
			$class_job_type = '';
			$col_job_type_param = $fld_job_type_param = [];

/*
			if ($cur_info['check_type'] == 1 && $cur_info['item_type'] == 1) // YES & Problem
				$col_job_type_param[] = 'style="display:none"';
			else if ($cur_info['check_type'] == 2 && $cur_info['item_type'] == 2) // YES & Work Order
				$col_job_type_param[] = 'style="display:none"';
			else
			{
				$fld_job_type_param[] = 'required';
				$class_job_type = 'fld-required';
			}
*/

			$css_job_type = ($cur_info['job_type'] == 0) ? 'alert-warning' : 'alert-success';

			$item_body[] = '<div class="col-2" id="col_job_type'.$cur_info['id'].'" '.implode(' ', $col_job_type_param).'>';
			$item_body[] = '<select name="job_type['.$cur_info['id'].']" class="form-select form-select-sm '.$css_job_type.'" id="fld_job_type'.$cur_info['id'].'" '.implode(' ', $fld_job_type_param).'>';

			$item_body[] = '<option value="" selected>Choose action</option>'."\n";
			foreach ($job_actions as $key => $value)
			{
				if (isset($_POST['job_type']) && $_POST['job_type'] == $key || $cur_info['job_type'] == $key)
					$item_body[] = "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.$value.'</option>'."\n";
				else
					$item_body[] = "\t\t\t\t\t\t\t".'<option value="'.$key.'">'.$value.'</option>'."\n";
			}

			$item_body[] = '</select>';
			$item_body[] = '</div>';
		}

		$comment_param = [];
/*
		if ($cur_info['comment_required'] == 1 && $cur_info['check_type'] < 2)
		{
			$comment_param[] = 'class="form-control fld-required"';
			$comment_param[] = 'required';
		}
*/
		$item_body[] = '<div class="col">';
		$item_body[] = '<input type="text" name="comment['.$cur_info['id'].']" value="'.html_encode($cur_info['comment']).'" id="fld_comment'.$cur_info['id'].'" '.(implode(' ', $comment_param)).'>';
		$item_body[] = '</div>';
		$item_body[] = '</div>';

		if ($cur_info['req_appendixb'] == 1 && $cur_info['check_type'] == 2)
			$req_appendixb = true;

		// Need to setup these two options to display items all time
		
		if ($cur_info['check_type'] == 1 && $cur_info['item_type'] == 2 || $cur_info['check_type'] == 2 && $cur_info['item_type'] == 1)
			echo implode('', $item_body);
	}

	$equipment_id = $cur_info['equipment_id'];
}
?>
		</div>

		<div class="card-body">
			<label class="form-label mb-1">Is the checklist completed?</label>
			<div class="mb-3">
				<div class="form-check form-check-inline">
					<input class="form-check-input" type="radio" name="work_order_completed" id="fld_work_order_completed2" value="2" <?php echo ($main_info['work_order_completed'] == 2) ? 'checked' : '' ?> onclick="checkRadioBox(2)">
					<label class="form-check-label" for="fld_work_order_completed2">YES</label>
				</div>
				<div class="form-check form-check-inline">
					<input class="form-check-input" type="radio" name="work_order_completed" id="fld_work_order_completed1" value="1" <?php echo ($main_info['work_order_completed'] == 1) ? 'checked' : '' ?> required onclick="checkRadioBox(1)">
					<label class="form-check-label" for="fld_work_order_completed1">NO</label>
				</div>
			</div>

			<div class="mb-3">
				<label class="form-label text-danger" for="fld_work_order_comment">Remarks. If checklist is not copleted - Why?</label>
				<textarea class="form-control" id="fld_work_order_comment" name="work_order_comment" placeholder="Leave your comments" <?php echo ($main_info['work_order_completed'] == 1) ? 'required' : '' ?>><?php echo isset($_POST['work_order_comment']) ? html_encode($_POST['work_order_comment']) : html_encode($main_info['work_order_comment']) ?></textarea>
			</div>

			<div class="mb-3">
				<button type="submit" name="update" class="btn btn-primary">Submit</button>

<?php if ($req_appendixb) : ?>
				<a href="<?php echo $URL->link('hca_hvac_inspections_appendixb', $id) ?>" class="btn btn-info text-white">Create Appendix-B</a>
<?php endif; ?>

<?php if ($User->checkAccess('hca_hvac_inspections', 13)): ?>
				<button type="submit" name="delete" class="btn btn-danger" formnovalidate onclick="return confirm('Are you sure you want to delete it?')">Delete</button>
<?php endif; ?>
			</div>
		</div>

	</div>
</form>

<?php

$hca_hvac_inspections_checklist = $unispected_units = [];
	
$query = array(
	'SELECT'	=> 'ch.unit_id',
	'FROM'		=> 'hca_hvac_inspections_checklist AS ch',
	'WHERE'		=> 'ch.property_id='.$main_info['property_id'], // Add filter by period
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
while ($row = $DBLayer->fetch_assoc($result))
{
	$hca_hvac_inspections_checklist[$row['unit_id']] = $row['unit_id'];
}

$query = array(
	'SELECT'	=> 'un.id, un.unit_number',
	'FROM'		=> 'sm_property_units AS un',
	'WHERE'		=> 'un.property_id='.$main_info['property_id'],
	'ORDER BY'	=> 'LENGTH(un.unit_number), un.unit_number',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
while ($row = $DBLayer->fetch_assoc($result))
{
	if (!in_array($row['id'], $hca_hvac_inspections_checklist))
		$unispected_units[] = '<a href="'.$URL->link('hca_hvac_inspections_checklist2', 0).'&property_id='.$main_info['property_id'].'&unit_id='.$row['id'].'" class="badge bg-primary text-white me-2 mb-2">'.$row['unit_number'].'</a>';
}
?>
<div class="card-header">
	<h6 class="card-title mb-0">List of never inspected units (<?php echo count($unispected_units) ?>)</h6>
</div>
<div class="mb-3">
	<div class="alert alert-info mb-0 py-2" role="alert">
		<p class="text-muted">This unit list displays never inspected units of <?=html_encode($main_info['pro_name'])?> property. Click on the link below to start a new inspection.</p>
	</div>
	<div class="alert alert-warning" role="alert">
		<p class="fw-bold"><?php echo implode(' ', $unispected_units) ?></p>
	</div>
</div>

<?php
if ($User->checkAccess('hca_hvac_inspections', 17)) 
{
	$query = [
		'SELECT'	=> 'a.*, u.realname',
		'FROM'		=> 'hca_hvac_inspections_actions AS a',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'users AS u',
				'ON'			=> 'u.id=a.submitted_by'
			],
		],
		'WHERE'		=> 'a.checklist_id='.$id,
		'ORDER BY'	=> 'a.time_submitted'
	];
	//if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$actions_info = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$actions_info[] = $row;
	}

	if (!empty($actions_info)) 
	{
?>

<div class="card-header">
	<h6 class="card-title mb-0">Project's actions</h6>
</div>
<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>Date/Time</th>
			<th>Submitted by</th>
			<th>Action</th>
		</tr>
	</thead>
	<tbody>

<?php
		foreach($actions_info as $cur_info)
		{
?>
		<tr>
			<td class="ta-center"><?php echo format_time($cur_info['time_submitted']) ?></td>
			<td class="ta-center"><?php echo html_encode($cur_info['realname']) ?></td>
			<td><?php echo html_encode($cur_info['action']) ?></td>
		</tr>
<?php
		}
?>
	</tbody>
</table>

<?php
	}
}
?>
<script>
function showHideItemActions(key,id,com)
{
	if (key === 2)
	{
		$('#col_job_type'+id).css("display", "block");
		$('#fld_job_type'+id).prop('required', true);

		if (com === 1)
			$('#fld_comment'+id).prop('required', true);
	}
	else
	{
		$('#col_job_type'+id).css("display", "none");
		$('#fld_job_type'+id).prop('required', false);
		$('#fld_job_type'+id+' option[value=""]').prop('selected', true);

		if (com === 1)
			$('#fld_comment'+id).prop('required', false);
	}
}

function checkRadioBox(key){
	if (key === 1){
		$('#fld_work_order_comment').prop('required', true);
	}else{
		$('#fld_work_order_comment').prop('required', false);
	}
}
</script>

<?php if ($req_appendixb && $main_info['appendixb'] == 0) { ?>
<!-- Modal -->
<div class="modal fade" id="modalLive" tabindex="-1" aria-labelledby="modalLiveLabel" aria-modal="true" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Appendix-B required</h5>
      </div>
      <div class="modal-body">
        <p class="text-danger fw-bold">To start filling out the Appendix-B, click button below.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="closeModalLive()">I will do it later</button>
        <a href="<?php echo $URL->link('hca_hvac_inspections_appendixb', $id) ?>" class="btn btn-primary text-white">Create Appendix-B</a>
      </div>
    </div>
  </div>
</div>
<script>
function closeModalLive(){
	$('body').removeClass('modal-open');
	$('#modalLive').removeClass('show');
	$('#modalLive').css("display", "none");
	$("div.modal-backdrop").remove();
}
document.addEventListener("DOMContentLoaded", function() {
	$('body').addClass('modal-open');
	$('#modalLive').addClass('show');
	$('#modalLive').css("display", "block");
	$('body').append('<div class="modal-backdrop fade show"></div>');
});
</script>
<?php
}

require SITE_ROOT.'footer.php';
