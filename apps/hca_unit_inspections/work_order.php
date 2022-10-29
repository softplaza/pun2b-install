<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$is_admin = ($User->is_admin()) ? true : false;
$access = ($User->checkAccess('hca_unit_inspections', 2)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

$HcaUnitInspections = new HcaUnitInspections;

$SwiftUploader = new SwiftUploader;
$SwiftUploader->access_view_files = true;

$query = [
	'SELECT'	=> 'ch.*, u.realname AS owned_name, u1.realname AS inspected_name, u2.realname AS completed_name, u3.realname AS updated_name, p.pro_name, un.unit_number, un.unit_type',
	'FROM'		=> 'hca_unit_inspections_checklist AS ch',
	'JOINS'		=> [
		[
			'LEFT JOIN'		=> 'users AS u',
			'ON'			=> 'u.id=ch.owned_by'
		],	
		[
			'LEFT JOIN'		=> 'users AS u1',
			'ON'			=> 'u1.id=ch.inspected_by'
		],
		[
			'LEFT JOIN'		=> 'users AS u2',
			'ON'			=> 'u2.id=ch.completed_by'
		],
		[
			'LEFT JOIN'		=> 'users AS u3',
			'ON'			=> 'u3.id=ch.updated_by'
		],
		[
			'INNER JOIN'	=> 'sm_property_db AS p',
			'ON'			=> 'p.id=ch.property_id'
		],
		[
			'INNER JOIN'	=> 'sm_property_units AS un',
			'ON'			=> 'un.id=ch.unit_id'
		],
	],
	'WHERE'		=> 'ch.id='.$id
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $DBLayer->fetch_assoc($result);

if (isset($_POST['submit']))
{
	$form_data = [
		'work_order_comment'	=> swift_trim($_POST['work_order_comment']),
		'updated_by'			=> $User->get('id'),
		'updated_time'			=> time(),
		'work_order_completed'	=> isset($_POST['work_order_completed']) ? intval($_POST['work_order_completed']) : 1,

		'datetime_completion_start'	=> isset($_POST['datetime_completion_start']) ? swift_trim($_POST['datetime_completion_start']) : '',
		'datetime_completion_end'	=> isset($_POST['datetime_completion_end']) ? swift_trim($_POST['datetime_completion_end']) : '',
	];

	$job_types = isset($_POST['job_type']) ? $_POST['job_type'] : [];

	$form_data['num_pending'] = 0; //0
	$form_data['num_replaced'] = 0; //1
	$form_data['num_repaired'] = 0; //2
	$form_data['num_reset'] = 0; //3

	$pending = false;
	if (!empty($job_types))
	{
		foreach($_POST['job_type'] as $key => $value)
		{
			if ($value == 0 || $value == 4)
			{
				++$form_data['num_pending'];
				$pending = true;
			}
			else if ($value == 1)
				++$form_data['num_replaced'];
			else if ($value == 2)
				++$form_data['num_repaired'];
			else if ($value == 3)
				++$form_data['num_reset'];
		}
	}

	// Set START date & time if did not set
	if (strtotime($main_info['datetime_completion_start']) < 0 && $form_data['datetime_completion_start'] == '')
		$form_data['datetime_completion_start'] = date('Y-m-d\TH:i:s');

	// Set COMPLETION date & time if did not set
	if ($form_data['work_order_completed'] == 2 && strtotime($main_info['datetime_completion_end']) < 0 && $form_data['datetime_completion_end'] == '')
		$form_data['datetime_completion_end'] = date('Y-m-d\TH:i:s');

/*
	if ($form_data['work_order_completed'] == 2 && $form_data['datetime_completion_start'] == $form_data['datetime_completion_end'])
		$Core->add_error('"Start Time" cannot be the same as "Completion Time".');
*/
	if ($form_data['work_order_completed'] == 2 && strtotime($form_data['datetime_completion_start']) > strtotime($form_data['datetime_completion_end']))
		$Core->add_error('"Start Time" cannot be greater than "Completion Time".');

	if ($form_data['work_order_completed'] == 2 && ($form_data['datetime_completion_start'] == '' || $form_data['datetime_completion_end'] == ''))
		$Core->add_error('You cannot close the Work Order without "Start Date" and "Completion Date".');

	if ($pending && $form_data['work_order_completed'] == 2)
		$Core->add_error('You cannot close the Work Order if at least one of the elements has "Pending" status.');

	if ($form_data['work_order_completed'] == 1 && $form_data['work_order_comment'] == '')
		$Core->add_error('If the Work Order is not completed, please provide a reason in the comment box below.');

	if (empty($Core->errors))
	{
		// If never completed set Completed By
		if ($form_data['work_order_completed'] == 2 && $main_info['completed_by'] == 0)
			$form_data['completed_by'] = $User->get('id');

		$DBLayer->update('hca_unit_inspections_checklist', $form_data, $id);

		if (!empty($job_types))
		{
			foreach($_POST['job_type'] as $key => $value)
			{
				$DBLayer->update('hca_unit_inspections_checklist_items', ['job_type' => $value], $key);
			}
		}

		// Add flash message
		if ($form_data['work_order_completed'] == 2)
			$flash_message = 'Work Order completed by '.$User->get('realname');
		else
			$flash_message = 'Work Order updated by '.$User->get('realname');

		$action_data = [
			'checklist_id'			=> $id,
			'submitted_by'			=> $User->get('id'),
			'time_submitted'		=> time(),
			'action'				=> $flash_message
		];
		$DBLayer->insert_values('hca_unit_inspections_actions', $action_data);

		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_unit_inspections_work_order', $id), $flash_message);
	}
}

else if (isset($_POST['delete']))
{
	if ($id > 0)
	{
		$DBLayer->delete('hca_unit_inspections_checklist', $id);

		$query = array(
			'DELETE'	=> 'hca_unit_inspections_checklist_items',
			'WHERE'		=> 'checklist_id='.$id
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
		$query = array(
			'DELETE'	=> 'hca_unit_inspections_actions',
			'WHERE'		=> 'checklist_id='.$id
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);

		// Add flash message
		$flash_message = 'Work Order deleted';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_unit_inspections_inspections', 0), $flash_message);
	}
}

else if (isset($_POST['update_item']))
{
	$checklist_item_id = isset($_POST['checklist_item_id']) ? intval($_POST['checklist_item_id']) : 0;
	$form_data = [
		'item_id'	=> isset($_POST['item_id']) ? intval($_POST['item_id']) : 0
	];

	if ($form_data['item_id'] == 0)
		$Core->add_error('No item selected.');

	if (empty($Core->errors))
	{
		$DBLayer->update('hca_unit_inspections_checklist_items', $form_data, $checklist_item_id);

		$flash_message = 'Item has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_unit_inspections_work_order', $id), $flash_message);
	}
}

$query = [
	'SELECT'	=> 'ci.*, i.item_name, i.location_id, i.equipment_id, i.req_appendixb',
	'FROM'		=> 'hca_unit_inspections_checklist_items AS ci',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'hca_unit_inspections_checklist AS ch',
			'ON'			=> 'ch.id=ci.checklist_id'
		],
		[
			'INNER JOIN'	=> 'hca_unit_inspections_items AS i',
			'ON'			=> 'i.id=ci.item_id'
		],
	],
	'WHERE'		=> 'ch.id='.$id,
	'ORDER BY'	=> 'i.display_position'
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$checked_items = [];
while($row = $DBLayer->fetch_assoc($result))
{
	$checked_items[] = $row;
}

if ($action != 'pdf')
	$SwiftMenu->addNavAction('<li><a class="dropdown-item" href="'.$URL->link('hca_unit_inspections_work_order', $id).'&action=pdf'.'" target="_blank"><i class="fa fa-file-pdf-o fa-1x" aria-hidden="true"></i> Print as PDF</a></li>');
else
	$SwiftMenu->page_actions = false;

$Core->set_page_id('hca_unit_inspections_work_order', 'hca_unit_inspections');
require SITE_ROOT.'header.php';

if ($action == 'pdf')
{
	$HcaUnitInspectionsPDF = new HcaUnitInspectionsPDF;
	$HcaUnitInspectionsPDF->genWorkOrder();
?>
	<style>#demo_iframe{width: 100%;height: 400px;zoom: 2;}</style>
	<div class="card-header">
		<h6 class="card-title mb-0">PDF preview</h6>
	</div>
	<iframe id="demo_iframe" src="files/work_order_<?=$id?>.pdf?v=<?=time()?>"></iframe>
<?php
	require SITE_ROOT.'footer.php';
}

?>

<div class="card mb-1">
	<div class="card-header">
		<h6 class="card-title mb-0">Work Order #<?php echo $main_info['id'] ?></h6>
	</div>
	<div class="card-body">

<?php if ($main_info['work_order_completed'] == 2): ?>
		<div class="alert alert-success mb-3" role="alert">
			<h6 class="alert-heading">The Work Order completed.</h6>
			<hr class="my-1">
			<a href="<?php echo $URL->link('hca_unit_inspections_checklist', 0).'&property_id='.$main_info['property_id'] ?>" class="badge bg-light text-primary border border-secondary mb-1">Start New Inspection</a>
			<a href="<?php echo $URL->link('hca_unit_inspections_checklist', $id) ?>" class="badge bg-light text-primary border border-secondary mb-1">Back to Checklist</a>
			<a href="<?php echo $URL->link('hca_unit_inspections_inspections', 0).'&property_id='.$main_info['property_id'] ?>" class="badge bg-light text-primary border border-secondary mb-1">Work Orders of <?php echo html_encode($main_info['pro_name']) ?></a>
		</div>
<?php else: ?>
		<div class="alert alert-warning mb-3" role="alert">
			<h6 class="alert-heading">The Work Order has not been completed.</h6>
			<hr class="my-1">
			<a href="<?php echo $URL->link('hca_unit_inspections_checklist', 0).'&property_id='.$main_info['property_id'] ?>" class="badge bg-light text-primary border border-secondary mb-1">Start New Inspection</a>
			<a href="<?php echo $URL->link('hca_unit_inspections_checklist', $id) ?>" class="badge bg-light text-primary border border-secondary mb-1">Back to Checklist</a>
			<a href="<?php echo $URL->link('hca_unit_inspections_inspections', 0).'&property_id='.$main_info['property_id'] ?>" class="badge bg-light text-primary border border-secondary mb-1">View all Work Orders of <?php echo html_encode($main_info['pro_name']) ?></a>
		</div>
<?php endif; ?>

		<div class="row">
			<div class="col-md-3">
				<label class="form-label">Property name</label>
				<h5><?php echo html_encode($main_info['pro_name']) ?></h5>
			</div>
			<div class="col-md-3">
				<label class="form-label">Unit number</label>
				<h5><?php echo html_encode($main_info['unit_number']) ?></h5>
			</div>
			<div class="col-md-3">
				<label class="form-label">Unit Size</label>
				<h5><?php echo html_encode($main_info['unit_type']) ?><h5>
			</div>
		</div>
	</div>

<?php if ($main_info['inspection_completed'] == 1): ?>
	<div class="alert alert-danger" role="alert">
		<h4 class="alert-heading">Warning!</h4>
		<hr class="my-1">
		<p class="mb-1">You cannot work with a Work Order until you have completed an inspection. Go to the Checklist and complete the inspection, then you can continue to work on the Work Order.</p>
		<a href="<?php echo $URL->link('hca_unit_inspections_checklist', $id) ?>" class="btn btn-secondary text-white mb-1">Go to Checklist</a>
	</div>
<?php else: ?>

	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">

		<div class="card-body">
			<div class="row">
	<?php if ($main_info['work_order_completed'] == 2): ?>
				<div class="col-md-3">
					<label class="form-label">Completed by:</label>
					<h5><?php echo html_encode($main_info['completed_name']) ?></h5>
				</div>
	<?php else: ?>
				<div class="col-md-3">
					<label class="form-label">Submitted by:</label>
					<h5><?php echo html_encode($main_info['updated_name']) ?></h5>
				</div>
	<?php endif; ?>

<?php

$datetime_completion_start = isset($_POST['datetime_completion_start']) ? $_POST['datetime_completion_start'] : (strtotime($main_info['datetime_completion_start']) > 0 ? format_date($main_info['datetime_completion_start'], 'Y-m-d\TH:i') : date('Y-m-d\TH:i'));

//$datetime_completion_end = isset($_POST['datetime_completion_end']) ? $_POST['datetime_completion_end'] : (strtotime($main_info['datetime_completion_end']) > 0 ? format_date($main_info['datetime_completion_end'], 'Y-m-d\TH:i') : date('Y-m-d\TH:i'));
$datetime_completion_end = isset($_POST['datetime_completion_end']) ? $_POST['datetime_completion_end'] : (strtotime($main_info['datetime_completion_end']) > 0 ? format_date($main_info['datetime_completion_end'], 'Y-m-d\TH:i') : '');

?>

				<div class="col-md-3">
					<label class="form-label" for="fld_datetime_completion_start">Start Date & Time:</label>
					<input type="datetime-local" name="datetime_completion_start" value="<?php echo $datetime_completion_start ?>" class="form-control" id="fld_datetime_completion_start" required>
				</div>
				
				<div class="col-md-3">
					<label class="form-label" for="fld_datetime_completion_end">Completion Date & Time:</label>
					<input type="datetime-local" name="datetime_completion_end" value="<?php echo $datetime_completion_end ?>" class="form-control" id="fld_datetime_completion_end">
				</div>

			</div>
		</div>
	</div>

	<div class="card mb-1">
		<div class="card-body">

<?php
$job_types = [
	0 => 'Pending',
	1 => 'Replaced',
	2 => 'Repaired',
	3 => 'Reset',
	//4 => 'Pending',
];

$location_id1 = 0;
$appendixb = false;
foreach($HcaUnitInspections->locations as $location_id => $location_name)
{
	if (!empty($checked_items))
	{
		$output = $cur_item = $cur_location = [];
		if ($location_id1 == 0)
		{
			$cur_location[] = '<h5 class="text-uppercase text-danger mb-1">'.html_encode($location_name).'</h5>';
		}
		else if ($location_id1 != $location_id)
		{
			$cur_location[] = '<h5 class="text-uppercase text-danger mb-1 mt-3">'.html_encode($location_name).'</h5>';
		}

		$output[] = '<div class="mb-3">';
		foreach($checked_items as $cur_info)
		{
			if ($cur_info['location_id'] == $location_id)
			{
				$element_name = $HcaUnitInspections->getEquipment($cur_info['equipment_id']).' -> '.html_encode($cur_info['item_name']);
				$problem_names = $HcaUnitInspections->getItemProblems($cur_info['problem_ids']);

				$cur_item[] = '<div class="row callout bd-callout-info mb-1">';

				$cur_item[] = '<div class="col-md-3 px-0">';
				$cur_item[] = '<span class="fw-bold text-primary">'.$element_name.'</span>:';
				$cur_item[] = '<span class="text-danger">'.$problem_names.'</span>';

				if ($cur_info['comment'] != '')
					$cur_item[] = '<p class="fst-italic"><span class="text-info">Comment:</span> '.html_encode($cur_info['comment']).'</p>';

				$cur_item[] = '</div>';

				$css_job_type = ($cur_info['job_type'] == 0 || $cur_info['job_type'] == 4) ? 'alert-warning' : 'alert-success';
				$cur_item[] = '<div class="col-md-3 px-0">';
				$cur_item[] = '<label class="form-label">Choose an action</label>';
				$cur_item[] = '<select name="job_type['.$cur_info['id'].']" class="form-select form-select-sm '.$css_job_type.'">';
				foreach($job_types as $key => $val)
				{
					if ($cur_info['job_type'] == $key)
						$cur_item[] = '<option value="'.$key.'" selected>'.$val.'</option>';
					else
						$cur_item[] = '<option value="'.$key.'">'.$val.'</option>';
				}
				$cur_item[] = '</select>';
				$cur_item[] = '</div>';

				if ($is_admin)
				{
					$cur_item[] = '<div class="col-md-3 px-0">';
					$cur_item[] = '<button type="button" class="badge bg-primary" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="editChecklistItem('.$cur_info['id'].');">Edit</button>';
					$cur_item[] = '</div>';
				}
					

				$cur_item[] = '</div>';

				//$problem_ids = explode(',', $cur_info['problem_ids']);
				// 5 - Discolored, 13 - Wet
				//if (in_array(5, $problem_ids) || in_array(13, $problem_ids))
				if ($cur_info['req_appendixb'] == 1)
					$appendixb = true;
			}
		}

		if (!empty($cur_item))
		{
			$output[] = implode("\n", $cur_location);
			$output[] = implode("\n", $cur_item);
		}
		$output[] = '</div>';

		$location_id1 = $location_id;

		$files = $SwiftUploader->displayFiles('hca_unit_inspections_checklist'.$location_id, $id);

		if (!empty($files))
		{
			$output[] = '<div class="accordion" id="accordionWO">';
			$output[] = '<div class="accordion-item">';
			$output[] = '<h2 class="accordion-header" id="heading'.$location_id.'">';
			$output[] = '<button class="accordion-button collapsed alert-info" type="button" data-bs-toggle="collapse" data-bs-target="#collapse'.$location_id.'" aria-expanded="true" aria-controls="collapse'.$location_id.'"><i class="fas fa-image fa-lg"></i><span class="fw-bold ms-2">Uploaded Images</span>. (Click to show/hide files)</button>';
			$output[] = '</h2>';
			$output[] = '<div id="collapse'.$location_id.'" class="accordion-collapse collapse" aria-labelledby="heading'.$location_id.'" data-bs-parent="#accordionWO">';
			$output[] = '<div class="accordion-body">';
			$output[] = $files;
			$output[] = '</div>';
			$output[] = '</div>';
			$output[] = '</div>';
			$output[] = '</div>';
		}

		echo implode("\n", $output);
	}
}
?>
		</div>
	</div>

<?php
// Set access
$SwiftUploader->access_view_files = true;
if ($User->checkAccess('hca_unit_inspections', 18))
	$SwiftUploader->access_upload_files = true;
if ($User->checkAccess('hca_unit_inspections', 19))
	$SwiftUploader->access_delete_files = true;

// Display files
$SwiftUploader->ajaxImages('hca_unit_inspections_checklist', $id);

// Include JS
$SwiftUploader->addJS();
?>

	<div class="card">
		<div class="card-body">
			<label class="form-label mb-1">Work Order Completed?</label>
			<div class="mb-3">
				<div class="form-check form-check-inline">
					<input class="form-check-input" type="radio" name="work_order_completed" id="fld_work_order_completed1" value="1" <?php echo ($main_info['work_order_completed'] == 1) ? 'checked' : '' ?>>
					<label class="form-check-label" for="fld_work_order_completed1">NO</label>
				</div>
				<div class="form-check form-check-inline">
					<input class="form-check-input" type="radio" name="work_order_completed" id="fld_work_order_completed2" value="2" <?php echo ($main_info['work_order_completed'] == 2) ? 'checked' : '' ?>>
					<label class="form-check-label" for="fld_work_order_completed2">YES</label>
				</div>
			</div>

			<div class="mb-3">
				<label class="form-label" for="fld_work_order_comment">If not completed, why?</label>
				<textarea class="form-control" id="fld_work_order_comment" name="work_order_comment" placeholder="Leave your comments"><?php echo isset($_POST['work_order_comment']) ? html_encode($_POST['work_order_comment']) : html_encode($main_info['work_order_comment']) ?></textarea>
			</div>

			<div class="mb-3">
<?php if ($main_info['work_order_completed'] == 2) : ?>
				<button type="submit" name="submit" class="btn btn-primary mb-1">Update</button>
<?php else : ?>
				<button type="submit" name="submit" class="btn btn-primary mb-1">Submit</button>			
<?php endif; ?>

<?php if ($appendixb) : ?>
			<a href="<?php echo $URL->link('hca_unit_inspections_appendixb', $id) ?>" class="btn btn-info text-white mb-1 hidden">Create Appendix-B</a>
<?php endif; ?>

<?php if ($User->checkAccess('hca_unit_inspections', 14)) : ?>
				<button type="submit" name="delete" class="btn btn-danger mb-1" onclick="return confirm('Are you sure you want to delete it?')">Delete</button>
<?php endif; ?>
			</div>

		</div>
	</div>
</form>

<?php endif; ?>

<?php
if ($User->checkAccess('hca_unit_inspections', 17)) 
{
	$query = [
		'SELECT'	=> 'a.*, u.realname',
		'FROM'		=> 'hca_unit_inspections_actions AS a',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'users AS u',
				'ON'			=> 'u.id=a.submitted_by'
			],
		],
		'WHERE'		=> 'a.checklist_id='.$id,
		'ORDER BY'	=> 'a.time_submitted'
	];
	if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
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

<div class="modal fade" id="modalWindow" tabindex="-1" aria-labelledby="modalWindowLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method="post" accept-charset="utf-8" action="">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
				<div class="modal-header">
					<h5 class="modal-title">Edit information</h5>
					<button type="button" class="btn-close bg-danger" data-bs-dismiss="modal" aria-label="Close" onclick="closeModalWindow()"></button>
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
function editChecklistItem(id)
{
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_unit_inspections_ajax_edit_work_order_item')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_unit_inspections_ajax_edit_work_order_item') ?>",
		type:	"POST",
		dataType: "json",
		data: ({id:id,csrf_token:csrf_token}),
		success: function(re){
			$('.modal .modal-title').empty().html(re.modal_title);
			$('.modal .modal-body').empty().html(re.modal_body);
			$('.modal .modal-footer').empty().html(re.modal_footer);
			checkCheckBoxes();
		},
		error: function(re){
			$('.msg-section').empty().html('Error: No data.');
		}
	});
}
function checkCheckBoxes()
{
	$('#modalWindow input[type="checkbox"]').click(function(){
		var anyBoxesChecked = false;
		$('input[type="checkbox"]').each(function(){
			if ($(this).is(":checked")){
				anyBoxesChecked = true;
			}
		});
		
		if (anyBoxesChecked == true)
			$('#btn_add_item').prop('disabled', false);
		else
			$('#btn_add_item').prop('disabled', true);
	});
}
function closeModalWindow(){
	$('.modal .modal-title').empty().html('');
	$('.modal .modal-body').empty().html('');
	$('.modal .modal-footer').empty().html('');
}
function hideField(id,v)
{
	if (v == 3)
		$('#'+id+'_comment').css('display', 'block');
	else
		$('#'+id+'_comment').css('display', 'none');
}
</script>

<?php
require SITE_ROOT.'footer.php';
