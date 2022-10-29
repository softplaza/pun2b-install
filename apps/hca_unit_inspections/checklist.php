<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_unit_inspections', 1)) ? true : false;
$access20 = ($User->checkAccess('hca_unit_inspections', 20)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$lid = isset($_GET['lid']) ? intval($_GET['lid']) : 0;
$property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;

$HcaUnitInspections = new HcaUnitInspections;
$SwiftUploader = new SwiftUploader;

// Set permissions to view, download and delete files
$SwiftUploader->access_view_files = true;
if ($User->checkAccess('hca_unit_inspections', 18))
	$SwiftUploader->access_upload_files = true;
if ($User->checkAccess('hca_unit_inspections', 19))
	$SwiftUploader->access_delete_files = true;

if (isset($_POST['create']))
{
	$form_data = [
		'property_id'				=> isset($_POST['property_id']) ? intval($_POST['property_id']) : 0,
		'unit_id'					=> isset($_POST['unit_id']) ? intval($_POST['unit_id']) : 0,
		'owned_by'					=> $User->get('id'),
		//'created'					=> time(),
		//'date_inspected'			=> date('Y-m-d'),
		'inspected_by'				=> $User->get('id'),
		//'status'					=> 1,
		'datetime_inspection_start' => date('Y-m-d\TH:i:s'),
		'updated_by'				=> $User->get('id'),
		'updated_time'				=> time()
	];

	if ($form_data['property_id'] == 0)
		$Core->add_error('Select property.');
	if ($form_data['unit_id'] == 0)
		$Core->add_error('Select unit number.');

	if (empty($Core->errors))
	{
		// Create a New Project
		$new_id = $DBLayer->insert_values('hca_unit_inspections_checklist', $form_data);

		// Add flash message
		$flash_message = 'Checklist was created by '.$User->get('realname');

		$action_data = [
			'checklist_id'			=> $new_id,
			'submitted_by'			=> $User->get('id'),
			'time_submitted'		=> time(),
			'action'				=> $flash_message
		];
		$DBLayer->insert_values('hca_unit_inspections_actions', $action_data);

		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_unit_inspections_checklist', $new_id), $flash_message);
	}
}

else if (isset($_POST['complete']))
{
	$num_problem = isset($_POST['num_problem']) ? intval($_POST['num_problem']) : 0;

	$form_data = [
		'datetime_inspection_end'	=> date('Y-m-d\TH:i:s'),
		//'status'					=> 2,
		'updated_by'				=> $User->get('id'),
		'updated_time'				=> time(),
		'inspection_completed'		=> isset($_POST['inspection_completed']) ? intval($_POST['inspection_completed']) : 1,
		'work_order_comment'		=> isset($_POST['work_order_comment']) ? swift_trim($_POST['work_order_comment']) : ''
	];

	if ($form_data['inspection_completed'] == 1 && $form_data['work_order_comment'] == '' || $num_problem == 0 && $form_data['work_order_comment'] == '')
		$Core->add_error('Please provide a reason if you close the checklist without issues or Checklist is not completed.');

	if (empty($Core->errors))
	{
		$DBLayer->update('hca_unit_inspections_checklist', $form_data, $id);

		// Add flash message
		$flash_message = 'Checklist was completed by '.$User->get('realname');

		$action_data = [
			'checklist_id'			=> $id,
			'submitted_by'			=> $User->get('id'),
			'time_submitted'		=> time(),
			'action'				=> $flash_message
		];
		$DBLayer->insert_values('hca_unit_inspections_actions', $action_data);

		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_unit_inspections_checklist', $id), $flash_message);
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
		$flash_message = 'CheckList #'.$id.' was deleted by '.$User->get('realname');
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_unit_inspections_inspections', 0), $flash_message);
	}
}

else if (isset($_POST['add_item']))
{
	$lid = isset($_POST['lid']) ? intval($_POST['lid']) : 0;

	$form_data = [
		'checklist_id'			=> $id,
		'item_id'				=> isset($_POST['item_id']) ? intval($_POST['item_id']) : 0,
		'problem_id'			=> isset($_POST['problem_id']) ? intval($_POST['problem_id']) : 0,
		'comment'				=> swift_trim($_POST['comment'])
	];

	$problem_ids = isset($_POST['problem_ids']) ? $_POST['problem_ids'] : [];

	$problems = [];
	if (!empty($problem_ids))
	{
		foreach($problem_ids as $key => $val)
		{
			if ($val == 1)
				$problems[] = $key;
		}
	}
	$form_data['problem_ids'] = implode(',', $problems);

	if (empty($problems) && $form_data['comment'] != '')
		$form_data['problem_ids'] = '0';

	if ($form_data['item_id'] == 0)
		$Core->add_error('Select inspected item from dropdown list.');

	if ($form_data['problem_ids'] == '' && $form_data['comment'] == '')
		$Core->add_error('You have not marked any problem or left a comment.');

	if (empty($Core->errors))
	{
		$new_id = $DBLayer->insert_values('hca_unit_inspections_checklist_items', $form_data);

		$DBLayer->update('hca_unit_inspections_checklist', [
			'updated_by' => $User->get('id'),
			'updated_time'	=> time(),
			'inspection_completed' => 1
			//'status' => 1,
			//'completed' => 0
		], $id);

		$query = array(
			'UPDATE'	=> 'hca_unit_inspections_checklist',
			'SET'		=> 'num_problem=num_problem+1',
			'WHERE'		=> 'id='.$id
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);

		// Add flash message
		$flash_message = 'Item #'.$form_data['item_id'].' was added to checklist by '.$User->get('realname');

		$action_data = [
			'checklist_id'			=> $id,
			'submitted_by'			=> $User->get('id'),
			'time_submitted'		=> time(),
			'action'				=> $flash_message
		];
		$DBLayer->insert_values('hca_unit_inspections_actions', $action_data);

		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_unit_inspections_checklist', $id).'&lid='.$lid, $flash_message);
	}
}

else if (isset($_POST['update_item']))
{
	$checklist_item_id = isset($_POST['checklist_item_id']) ? intval($_POST['checklist_item_id']) : 0;
	$lid = isset($_POST['lid']) ? intval($_POST['lid']) : 0;

	$form_data = [
		'item_id'				=> isset($_POST['item_id']) ? intval($_POST['item_id']) : 0,
		'comment'				=> swift_trim($_POST['comment'])
	];

	$problem_ids = isset($_POST['problem_ids']) ? $_POST['problem_ids'] : [];

	$problems = [];
	if (!empty($problem_ids))
	{
		foreach($problem_ids as $key => $val)
		{
			if ($val == 1)
			$problems[] = $key;
		}
	}
	$form_data['problem_ids'] = implode(',', $problems);

	if ($form_data['item_id'] == 0)
		$Core->add_error('Select inspected item from dropdown list.');
	//if ($form_data['problem_id'] == 0)
	//	$Core->add_error('Select problem from dropdown list.');
	if ($form_data['problem_ids'] == '')
		$Core->add_error('Check the checkboxes for problems.');

	if (empty($Core->errors))
	{
		$DBLayer->update('hca_unit_inspections_checklist_items', $form_data, $checklist_item_id);

		$DBLayer->update('hca_unit_inspections_checklist', [
			'updated_by' => $User->get('id'),
			'updated_time'	=> time(),
			'inspection_completed' => 1
			//'status' => 1,
			//'completed' => 0
		], $id);

		// Add flash message
		$flash_message = 'Item #'.$form_data['item_id'].' has been updated by '.$User->get('realname');

		$action_data = [
			'checklist_id'			=> $id,
			'submitted_by'			=> $User->get('id'),
			'time_submitted'		=> time(),
			'action'				=> $flash_message
		];
		$DBLayer->insert_values('hca_unit_inspections_actions', $action_data);

		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_unit_inspections_checklist', $id).'&lid='.$lid, $flash_message);
	}
}

//detete_item
else if (isset($_POST['detete_item']))
{
	$item_id = intval(key($_POST['detete_item']));
	if ($item_id > 0)
	{
		$DBLayer->delete('hca_unit_inspections_checklist_items', $item_id);

		$DBLayer->update('hca_unit_inspections_checklist', [
			'updated_by' => $User->get('id'),
			'inspection_completed' => 1
			//'status' => 1,
			//'completed' => 0
		], $id);

		$query = array(
			'UPDATE'	=> 'hca_unit_inspections_checklist',
			'SET'		=> 'num_problem=num_problem-1',
			'WHERE'		=> 'id='.$id
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);

		// Add flash message
		$flash_message = 'Item #'.$item_id.' was deleted by '.$User->get('realname');

		$action_data = [
			'checklist_id'			=> $id,
			'submitted_by'			=> $User->get('id'),
			'time_submitted'		=> time(),
			'action'				=> $flash_message
		];
		$DBLayer->insert_values('hca_unit_inspections_actions', $action_data);

		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_unit_inspections_checklist', $id), $flash_message);
	}
}

$query = array(
	'SELECT'	=> 'p.*',
	'FROM'		=> 'sm_property_db AS p',
	'WHERE'		=> 'p.id!=105 AND p.id!=113 AND p.id!=115 AND p.id!=116',
	'ORDER BY'	=> 'p.pro_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $row;
}

$Core->set_page_id('hca_unit_inspections_checklist', 'hca_unit_inspections');
require SITE_ROOT.'header.php';

if ($id == 0)
{
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">New inspection</h6>
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-md-4 mb-3">
					<label class="form-label" for="property_id">Property name</label>
					<select id="property_id" class="form-select" name="property_id" onchange="getUnits()" required>
<?php
echo '<option value="" selected disabled>Select Property</option>'."\n";
foreach ($property_info as $cur_info)
{
	if (isset($_POST['property_id']) && $_POST['property_id'] == $cur_info['id'] || $property_id == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['pro_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['pro_name']).'</option>'."\n";
}
?>
					</select>
				</div>
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_unit_number">Unit number</label>
					<div id="unit_number">
						<input type="text" value="" class="form-control" id="fld_unit_number" disabled>
					</div>
				</div>
				<div class="col-md-3 mb-3">
					<label class="form-label" for="fld_key_number">Key number</label>
					<span><i id="key_number_eye" class="fas fa-eye-slash" onclick="showKey()"></i></span>
					<div id="key_number">
						<input type="password" value="" class="form-control" id="fld_key_number" disabled>
					</div>
				</div>
			</div>

			<div class="mb-3">
				<button type="submit" name="create" class="btn btn-primary">Start inspection</button>
			</div>
		</div>
	</div>
</form>

<script>
function getUnits(id){
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_unit_inspections_ajax_get_units')) ?>";
	var id = $("#property_id").val();
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_unit_inspections_ajax_get_units') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({id:id,csrf_token:csrf_token}),
		success: function(re){
			$("#unit_number").empty().html(re.unit_number);
			$("#key_number").empty().html(re.key_number);
			$("#key_number_eye").removeClass("fa-eye");
			$("#key_number_eye").addClass("fa-eye-slash");
		},
		error: function(re){
			document.getElementById("unit_number").innerHTML = re;
		}
	});
}
function getUnitKey(){
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_unit_inspections_ajax_get_units')) ?>";
	var unit_id = $("#unit_numbers").val();
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_unit_inspections_ajax_get_units') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({unit_id:unit_id,csrf_token:csrf_token}),
		success: function(re){
			$("#key_number").empty().html(re.key_number);
			$("#key_number_eye").removeClass("fa-eye");
			$("#key_number_eye").addClass("fa-eye-slash");
		},
		error: function(re){
			document.getElementById("key_number").innerHTML = re;
		}
	});
}
function showKey()
{
    if ($("#key_number_eye").hasClass('fa-eye-slash'))
	{
		$("#fld_key_number").attr("type", "text");
		$("#key_number_eye").removeClass("fa-eye-slash");
		$("#key_number_eye").addClass("fa-eye");
    } else {
		$("#fld_key_number").attr("type", "password");
		$("#key_number_eye").removeClass("fa-eye");
		$("#key_number_eye").addClass("fa-eye-slash");
    }
}
document.addEventListener("DOMContentLoaded", function() {
	getUnits();
});
</script>

<?php
} else {

	$query = [
		'SELECT'	=> 'ch.*, u1.realname AS inspected_name, u2.realname AS completed_name, p.pro_name, un.unit_number, un.unit_type, un.mbath, un.hbath',
		'FROM'		=> 'hca_unit_inspections_checklist AS ch',
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
		'SELECT'	=> 'ci.*, i.item_name, i.location_id, i.equipment_id',
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
?>

<form method="post" accept-charset="utf-8" action="" enctype="multipart/form-data">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Unit Inspection CheckList</h6>
		</div>

		<div class="card-body">

<?php if ($main_info['inspection_completed'] == 2): ?>
			<div class="alert alert-success mb-3" role="alert">
				<h6 class="alert-heading">The Checklist completed.</h6>
				<hr class="my-1">
				<a href="<?php echo $URL->link('hca_unit_inspections_checklist', 0).'&property_id='.$main_info['property_id'] ?>" class="badge bg-light text-primary border border-secondary mb-1">Start New Inspection</a>
	<?php if ($main_info['num_problem'] > 0): ?>
				<a href="<?php echo $URL->link('hca_unit_inspections_work_order', $id) ?>" class="badge bg-light text-primary border border-secondary mb-1">Go to Work Order</a>
	<?php endif; ?>
			</div>
<?php else: ?>
			<div class="alert alert-warning mb-3" role="alert">The Checklist has not been completed. To proceed to the Work Order, first complete the CheckList.</div>
<?php endif; ?>

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
					<label class="form-label">Unit Size</label>
					<h5><?php echo html_encode($main_info['unit_type']) ?><h5>
				</div>
			</div>
			<div class="row">
				<div class="col-md-3">
					<label class="form-label">Date ispected:</label>
					<h5><?php echo format_date($main_info['datetime_inspection_start'], 'n/j/Y') ?></h5>
				</div>
				<div class="col-md-3">
					<label class="form-label">Time In:</label>
					<h5><?php echo format_date($main_info['datetime_inspection_start'], 'H:i') ?></h5>
				</div>

<?php if (strtotime($main_info['datetime_inspection_end']) > 0): ?>
				<div class="col-md-3">
					<label class="form-label">Time Out:</label>
					<h5><?php echo format_date($main_info['datetime_inspection_end'], 'H:i') ?></h5>
				</div>
<?php endif; ?>

				<div class="col-md-3">
					<label class="form-label">Performed by:</label>
					<h5><?php echo html_encode($main_info['inspected_name']) ?><h5>
				</div>
			</div>
		</div>
		
		<div class="accordion mb-3" id="accordionExample">

<?php
$num_problem = 0;
foreach($HcaUnitInspections->locations as $location_id => $location_name)
{
	if ($location_id < 3 || $location_id > 4 || ($location_id == 3 && $main_info['mbath'] == 1) || ($location_id == 4 && $main_info['hbath'] == 1))
	{
		$class_show = [];
		$class_show[] = ($main_info['num_problem'] > 0 && $lid == 0 || $lid == $location_id) ? 'show' : '';
		$class_show[] = ($lid == $location_id) ? 'anchor' : '';

?>
			<div class="accordion-item" id="accordionExample">
				<h2 class="accordion-header" id="heading<?=$location_id?>">
					<button class="accordion-button text-uppercase" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?=$location_id?>" aria-expanded="true" aria-controls="collapse<?=$location_id?>"><?php echo html_encode($location_name) ?></button>
				</h2>
				<div id="collapse<?=$location_id?>" class="accordion-collapse collapse <?php echo implode(' ', $class_show) ?>" aria-labelledby="heading<?=$location_id?>" data-bs-parent="#accordionExample">
					<div class="accordion-body">
						<div class="mb-3">
<?php
		if (!empty($checked_items))
		{
			foreach($checked_items as $cur_info)
			{
				$cur_item = [];
				if ($cur_info['location_id'] == $location_id)
				{
					//$problem_name = isset($problems_info[$cur_info['problem_id']]) ? html_encode($problems_info[$cur_info['problem_id']]) : 'n/a';

					$problem_names = $HcaUnitInspections->getItemProblems($cur_info['problem_ids']);
					$element_name = $HcaUnitInspections->getEquipment($cur_info['equipment_id']).' -> '.html_encode($cur_info['item_name']);

					$cur_item[] = '<div class="row callout bd-callout-danger mb-1">';

					$cur_item[] = '<div class="col-md-10 px-0">';
					$cur_item[] = '<span class="fw-bold text-primary">'.$element_name.'</span>:';
					$cur_item[] = '<span class="text-danger">'.$problem_names.'</span>';

					if ($cur_info['comment'] != '')
						$cur_item[] = '<p class="fst-italic"><span class="text-info">Comment:</span>'.html_encode($cur_info['comment']).'</p>';
					$cur_item[] = '</div>';

					$cur_item[] = '<div class="col-md-2">';
					$cur_item[] = '<div class="float-end">';

					if ($access20)
						$cur_item[] = '<button type="button" class="badge bg-primary" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="editChecklistItem('.$cur_info['id'].');">Edit</button>';

					$cur_item[] = '<button type="submit" name="detete_item['.$cur_info['id'].']" class="badge bg-danger" onclick="return confirm(\'Are you sure you want to delete it?\')">Delete</button>';
					$cur_item[] = '</div>';
					$cur_item[] = '</div>';

					$cur_item[] = '</div>';

					++$num_problem;
				}

				echo implode("\n", $cur_item);
			}
		}
?>
						</div>
						<div class="mb-3">
							<button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="getChecklistItems(<?=$location_id?>);">+ Add item</button>
						</div>
<?php
						// Disabled until project will started
						//$SwiftUploader->ajaxImages('hca_unit_inspections_checklist'.$location_id, $id);
?>
					</div>
				</div>
			</div>
<?php
	}
}
?>
		<input type="hidden" name="num_problem" value="<?=$num_problem?>">

		<div class="card-body">

			<label class="form-label mb-1">Has the checklist been completed?</label>
			<div class="mb-3">
				<div class="form-check form-check-inline">
					<input class="form-check-input" type="radio" name="inspection_completed" id="fld_inspection_completed1" value="1" <?php echo ($main_info['inspection_completed'] == 1) ? 'checked' : '' ?>>
					<label class="form-check-label" for="fld_inspection_completed1">NO</label>
				</div>
				<div class="form-check form-check-inline">
					<input class="form-check-input" type="radio" name="inspection_completed" id="fld_inspection_completed2" value="2" <?php echo ($main_info['inspection_completed'] == 2) ? 'checked' : '' ?>>
					<label class="form-check-label" for="fld_inspection_completed2">YES</label>
				</div>
			</div>

			<div class="mb-3">
				<label class="form-label text-danger" for="fld_work_order_comment">Provide a reason if you close the checklist without issues or Checklist is not completed.</label>
				<textarea class="form-control" id="fld_work_order_comment" name="work_order_comment" placeholder="Leave your comments"><?php echo isset($_POST['work_order_comment']) ? html_encode($_POST['work_order_comment']) : html_encode($main_info['work_order_comment']) ?></textarea>
			</div>

			<div class="mb-3">
<?php if ($main_info['inspection_completed'] == 1): ?>
				<button type="submit" name="complete" class="btn btn-primary">Submit</button>
<?php else: ?>
				<button type="submit" name="complete" class="btn btn-primary">Update</button>
<?php endif; ?>

<?php if ($User->checkAccess('hca_unit_inspections', 13)): ?>
				<button type="submit" name="delete" class="btn btn-danger" formnovalidate onclick="return confirm('Are you sure you want to delete it?')">Delete</button>
<?php endif; ?>
			</div>
		</div>

	</div>
</form>

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
function getChecklistItems(lid)
{
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_unit_inspections_ajax_get_checklist_items')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_unit_inspections_ajax_get_checklist_items') ?>",
		type:	"POST",
		dataType: "json",
		data: ({lid:lid,csrf_token:csrf_token}),
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
function getChecklistItemId()
{
	var item_id = $("#checklist_items").val();
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_unit_inspections_ajax_get_checklist_items')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_unit_inspections_ajax_get_checklist_items') ?>",
		type:	"POST",
		dataType: "json",
		data: ({item_id:item_id,csrf_token:csrf_token}),
		success: function(re){
			$('#checklist_problems').empty().html(re.checklist_problems);
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

function editChecklistItem(id)
{
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_unit_inspections_ajax_edit_checklist_item')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_unit_inspections_ajax_edit_checklist_item') ?>",
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
function closeModalWindow(){
	$('.modal .modal-title').empty().html('');
	$('.modal .modal-body').empty().html('');
	$('.modal .modal-footer').empty().html('');
}
</script>

<?php

$SwiftUploader->addJS();

}
require SITE_ROOT.'footer.php';
