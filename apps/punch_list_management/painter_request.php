<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

//$access = $User->checkAccess('punch_list_management', 8) ? true : false;
$technician_access = ($User->get('group_id') == $Config->get('o_hca_fs_painters')) ? true : false;
//if (!$access)
//	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
//if ($id < 1)
//	message($lang_common['Bad request']);

$HcaPainterPunchList = new HcaPainterPunchList;

if (isset($_POST['create']))
{
	$hash_key = random_key(5, true, true);
	$form_data = [
		'date_requested'	=> isset($_POST['date_requested']) ? strtotime($_POST['date_requested']) : 0,
		'form_type'			=> 2,
		'property_id'		=> isset($_POST['property_id']) ? intval($_POST['property_id']) : 0,
		'unit_number'		=> isset($_POST['unit_number']) ? swift_trim($_POST['unit_number']) : '',
		'technician_id'		=> isset($_POST['technician_id']) ? intval($_POST['technician_id']) : 0,
		'hash_key'			=> $hash_key,
	];

	if ($form_data['property_id'] == 0)
		$Core->add_error('Property name cannot be empty.');
	if ($form_data['technician_id'] == 0)
		$Core->add_error('Technician name cannot be empty.');

	if (empty($Core->errors))
	{
		// Create a new
		$form_id = $DBLayer->insert_values('punch_list_management_maint_request_form', $form_data);

		if ($form_id > 0)
		{
			$query = array(
				'SELECT'	=> 'f.id, f.unit_number, f.date_requested, u.realname, u.email, p.pro_name',
				'FROM'		=> 'punch_list_management_maint_request_form AS f',
				'JOINS'		=> array(
					array(
						'INNER JOIN'	=> 'users AS u',
						'ON'			=> 'u.id=f.technician_id'
					),
					array(
						'INNER JOIN'	=> 'sm_property_db AS p',
						'ON'			=> 'p.id=f.property_id'
					),
				),
				'WHERE'		=> 'f.id='.$form_id
			);
			$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
			$form_info = $DBLayer->fetch_assoc($result);

			$mail_subject = 'Appartment Punch List';
			$mail_message = [];
			$mail_message[] = 'Hello '.$form_info['realname'].'.'."\n";
			$mail_message[] = 'The property manager has sent you Apartment Punch List. See details below.';
			$mail_message[] = 'Date requested: '.format_time($form_info['date_requested'], 1);
			$mail_message[] = 'Property name: '.$form_info['pro_name'];
			if ($form_info['unit_number'] != '')
				$mail_message[] = 'Unit number: '.$form_info['unit_number'];
			$mail_message[] = 'To complete Punch List follow this link: ';
			$mail_message[] = $URL->link('punch_list_management_painter_request', [$form_id, $hash_key]);

			if ($user_info['email'] != '')
			{
				$SwiftMailer = new SwiftMailer;
				$SwiftMailer->send($form_info['email'], $mail_subject, implode("\n", $mail_message));
			}
		}

		// Add flash message
		$flash_message = 'Form has been created';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('punch_list_management_painter_request', [$form_id, $hash_key]), $flash_message);
	}
}

else if (isset($_POST['add_material']))
{
	$form_data = [
		'part_description'	=> isset($_POST['part_description']) ? swift_trim($_POST['part_description']) : '',
		'part_quantity'		=> isset($_POST['quantity']) ? intval($_POST['quantity']) : '',
		'form_id'			=> $id,
	];
	
	if ($form_data['part_description'] == '')
		$Core->add_error('Enter part description or select from dropdown list.');

	if (empty($Core->errors))
	{
		// Create a new
		$new_id = $DBLayer->insert_values('punch_list_painter_materials', $form_data);
		
		// Add flash message
		$flash_message = 'Material has been added';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('punch_list_management_painter_request', [$id, $hash]).'&mid='.$new_id, $flash_message);
	}
}

else if (isset($_POST['delete_material']))
{
	$item_id = intval(key($_POST['delete_material']));
	$DBLayer->delete('punch_list_painter_materials', $item_id);

	// Add flash message
	$flash_message = 'Item has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('punch_list_management_painter_request', [$id, $hash]).'#material_list', $flash_message);
}

else if (isset($_POST['update_form']) || isset($_POST['submit_form']))
{
	// Update form Info
	$form_data = [
		'start_time'		=> isset($_POST['start_time']) ? swift_trim($_POST['start_time']) : '00:00:00',
		'end_time'			=> isset($_POST['end_time']) ? swift_trim($_POST['end_time']) : '00:00:00',
		'date_submitted'	=> isset($_POST['date_submitted']) ? strtotime($_POST['date_submitted']) : time(),
		'completed'			=> isset($_POST['completed']) ? intval($_POST['completed']) : 0,
		'remarks'			=> isset($_POST['remarks']) ? swift_trim($_POST['remarks']) : '',
		'materials_comment'	=> isset($_POST['materials_comment']) ? swift_trim($_POST['materials_comment']) : '',
	];

	if ($form_data['start_time'] != '00:00:00' && $form_data['end_time'] != '00:00:00')
	{
		$a = new DateTime($form_data['start_time']);
		$b = new DateTime($form_data['end_time']);
		$interval = $a->diff($b);
		$form_data['time_spent'] = $interval->format('%H:%i');
	}
	$DBLayer->update('punch_list_management_maint_request_form', $form_data, $id);

	// Update Check List
	//'punch_list_painter_check_list'
	//new_status
	if (isset($_POST['new_status']) && !empty($_POST['new_status']))
	{
		foreach($_POST['new_status'] as $equipment_id => $status)
		{
			$form = [
				'form_id'			=> $id,
				'equipment_id'		=> $equipment_id,
				'item_status'		=> isset($_POST['item_status'][$equipment_id]) ? intval($_POST['item_status'][$equipment_id]) : 0,
				'replaced'			=> isset($_POST['replaced'][$equipment_id]) ? intval($_POST['replaced'][$equipment_id]) : 0,
			];
			$new_id = $DBLayer->insert_values('punch_list_painter_check_list', $form);
		}
	}
	if (isset($_POST['existing_status']) && !empty($_POST['existing_status']))
	{
		foreach($_POST['existing_status'] as $row_id => $status)
		{
			$form = [
				'item_status'		=> isset($_POST['item_status'][$row_id]) ? intval($_POST['item_status'][$row_id]) : 0,
				'replaced'			=> isset($_POST['replaced'][$row_id]) ? intval($_POST['replaced'][$row_id]) : 0,
			];
			$DBLayer->update('punch_list_painter_check_list', $form, $row_id);
		}
	}

	// Update Check List Comments
	if (isset($_POST['new_comment']) && !empty($_POST['new_comment']))
	{
		foreach($_POST['new_comment'] as $location_id => $comment)
		{
			$comment_form = [
				'form_id'			=> $id,
				'location_id'		=> $location_id,
				'comment'			=> swift_trim($comment),
			];

			if ($comment_form['comment'] != '')
				$form_id = $DBLayer->insert_values('punch_list_painter_check_list_comments', $comment_form);
		}
	}
	if (isset($_POST['existing_comment']) && !empty($_POST['existing_comment']))
	{
		foreach($_POST['existing_comment'] as $row_id => $comment)
		{
			$existing_comment_form = [
				'comment'			=> swift_trim($comment),
			];
			$DBLayer->update('punch_list_painter_check_list_comments', $existing_comment_form, $row_id);
		}
	}

	if (isset($_POST['part_quantity']) && !empty($_POST['part_quantity']))
	{
		$total_cost = 0;
		foreach($_POST['part_quantity'] as $key => $value)
		{
			$part_quantity = isset($_POST['part_quantity'][$key]) ? intval($_POST['part_quantity'][$key]) : 0;
			$cost_per = isset($_POST['cost_per'][$key]) ? $_POST['cost_per'][$key] : '0.00';
			$cost_per2 = is_numeric($cost_per) ? number_format($cost_per, 2, '.', '') : 0;

			$materials_data = [
				'part_quantity'		=> $part_quantity,
				'cost_per'			=> $cost_per,
				'cost_total'		=> $part_quantity * $cost_per2,
			];
			$DBLayer->update('punch_list_painter_materials', $materials_data, $key);

			$total_cost = $total_cost + $materials_data['cost_total'];
		}
		$DBLayer->update('punch_list_management_maint_request_form', ['total_cost' => $total_cost], $id);
	}

	if (isset($_POST['submit_form']))
	{
		if ($form_data['completed'] == 0 && $form_data['remarks'] == '')
			$Core->add_error('Comment cannot be empty if job is not completed. Please, leave your comment.');

		//if ($User->get('group_id') != '9')
		//	$Core->add_error('You cannot submit the form as a painter because you are not a member of this group.');

		if (empty($Core->errors))
		{
			$query = array(
				'SELECT'	=> 'f.*, u.realname, u.email, p.pro_name, p.manager_email',
				'FROM'		=> 'punch_list_management_maint_request_form AS f',
				'JOINS'		=> array(
					array(
						'INNER JOIN'	=> 'users AS u',
						'ON'			=> 'u.id=f.technician_id'
					),
					array(
						'INNER JOIN'	=> 'sm_property_db AS p',
						'ON'			=> 'p.id=f.property_id'
					),
				),
				'WHERE'		=> 'f.id='.$id
			);
			$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
			$form_info = $DBLayer->fetch_assoc($result);

			$submited_form_data = ['submitted_by_technician' => time()];
			if ($form_info['date_requested'] == 0)
				$submited_form_data['date_requested'] = time();

			$DBLayer->update('punch_list_management_maint_request_form', $submited_form_data, $id);

			// Send link by email to maintenance for continue
			$mail_subject = 'Apartment Punch List';
			$mail_message = [];
			$mail_message[] = 'Apartment Punch List has been completed by Technician. See details below.';
			$mail_message[] = 'Property name: '.$form_info['pro_name'];
			$mail_message[] = 'Unit number: '.$form_info['unit_number'];
			$mail_message[] = 'Technician: '.$form_info['realname']."\n";
			$mail_message[] = 'To view more information of Apartment Punch List follow this link.';
			$mail_message[] = $URL->link('punch_list_management_painter_request', [$id, $form_info['hash_key']]);
			
			$SwiftMailer = new SwiftMailer;
			$SwiftMailer->send($form_info['manager_email'], $mail_subject, implode("\n", $mail_message));
	
			$HcaPainterPunchList->genPDF($id);
			$flash_message = 'Punch List #'.$id.' has been submitted';
			redirect('', $flash_message);
		}

	}
	else
	{
		$HcaPainterPunchList->genPDF($id);

		// Add flash message
		$flash_message = 'Punch List #'.$id.' has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

if ($id > 0)
{
	$Core->warnings_collapsed = false;
	$Core->set_page_title('Painter Punch List');
	$Core->set_page_id('punch_list_management_forms', 'hca_fs');
	require SITE_ROOT.'header.php';

	// Get form info
	$query = [
		'SELECT'	=> 'f.*, u.realname, p.pro_name',
		'FROM'		=> 'punch_list_management_maint_request_form AS f',
		'JOINS'		=> [
			[
				'LEFT JOIN'		=> 'users AS u',
				'ON'			=> 'u.id=f.technician_id'
			],
			[
				'LEFT JOIN'		=> 'sm_property_db AS p',
				'ON'			=> 'p.id=f.property_id'
			],
		],
		'WHERE'		=> 'f.id='.$id
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$form_info = $DBLayer->fetch_assoc($result);
	
	if ($form_info['start_time'] == '00:00:00')
		$Core->add_warning('No Time In has been set. Set the Time In.".');
	if ($form_info['end_time'] == '00:00:00')
		$Core->add_warning('No Time Out has been set. Set the Time Out.".');
	if ($form_info['completed'] == 0 && $form_info['remarks'] == '')
		$Core->add_warning('If the job isn\'t completed, don\'t forget to leave a comment below "If not, why?".');
?>

<style>
.form-check-input {width: 15px;height: 15px;margin-right:10px;}
</style>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />

	<!--PAGE 1 START-->
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Apartment Punch List</h6>
		</div>
<?php
	if ($form_info['completed'] == 1)
		echo '<div class="alert alert-success fw-bold" role="alert">Apartment Punch List has been completed.</div>';
?>
		<div class="card-body">
			<div class="row mb-3">
				<div class="col-md-3">
					<label class="form-label">Date requested:</label>
					<h6><?php echo format_time($form_info['date_requested'], 1, 'm/d/Y') ?><h6>
				</div>
				<div class="col-md-3">
					<label class="form-label">Property:</label>
					<h6><?php echo html_encode($form_info['pro_name']) ?><h6>
				</div>
				<div class="col-md-3">
					<label class="form-label">Unit #</label>
					<h6><?php echo html_encode($form_info['unit_number']) ?><h6>
				</div>
				<div class="col-md-3">
					<label class="form-label">Date performed:</label>
					<h6><?php echo ($form_info['submitted_by_technician'] > 0) ? date('m/d/Y', $form_info['submitted_by_technician']) : '<span class="text-danger">Not submitted</span>' ?><h6>
				</div>
			</div>
			<div class="row mb-3">
				<div class="col-md-3">
					<label for="field_time_in" class="form-label">Time In:</label>
					<input type="time" name="start_time" value="<?php echo (isset($_POST['time_in']) ? html_encode($_POST['time_in']) : html_encode($form_info['start_time'])) ?>" class="form-control" id="field_time_in">
				</div>
				<div class="col-md-3">
					<label for="field_time_out" class="form-label">Time Out:</label>
					<input type="time" name="end_time" value="<?php echo (isset($_POST['time_out']) ? html_encode($_POST['time_out']) : html_encode($form_info['end_time'])) ?>" class="form-control" id="field_time_out">
				</div>
				<div class="col-md-3">
					<label class="form-label">Total Time:</label>
					<h6><?php echo html_encode($form_info['time_spent']) ?><h6>
				</div>
				<div class="col-md-3">
					<label class="form-label">Performed By:</label>
					<h6><?php echo html_encode($form_info['realname']) ?><h6>
				</div>
			</div>
		</div>
	</div>

<?php

	$query = array(
		'SELECT'	=> 'l.*',
		'FROM'		=> 'punch_list_painter_locations AS l',
		'ORDER BY'	=> 'l.position',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$locations = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$locations[] = $row;
	}

	$query = array(
		'SELECT'	=> 'e.*, l.location_name',
		'FROM'		=> 'punch_list_painter_equipments AS e',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'punch_list_painter_locations AS l',
				'ON'			=> 'l.id=e.location_id'
			),
		),
		'ORDER BY'	=> 'l.location_name, e.equipment_name',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$equipments_info = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$equipments_info[] = $row;
	}

	$query = array(
		'SELECT'	=> 'ch.*',
		'FROM'		=> 'punch_list_painter_check_list AS ch',
		'WHERE'		=> 'ch.form_id='.$id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$check_list = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$check_list[] = $row;
	}

	$query = array(
		'SELECT'	=> 'c.*',
		'FROM'		=> 'punch_list_painter_check_list_comments AS c',
		'WHERE'		=> 'c.form_id='.$id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$check_list_comments = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$check_list_comments[] = $row;
	}
?>
	<div class="card">
		<div class="card-header bg-info">
			<h6 class="card-title mb-0">Check List</h6>
		</div>
<?php

	foreach($locations as $location)
	{
		$radio_count = 0;
		$cur_info = [];

		$cur_info[] = '<div class="card-body border">';
		$cur_info[] = '<h6 class="text-danger">'.html_encode($location['location_name']).'</h6>';

		foreach($equipments_info as $cur_item)
		{
			if ($location['id'] == $cur_item['location_id'])
			{
				$cur_info[] = '<div class="row border pt-2">';

				$cur_info[] = '<div class="col col-md-2">';
				$cur_info[] = '<strong>'.html_encode($cur_item['equipment_name']).'</strong>';
				$cur_info[] = '</div>';

				$cur_info[] = '<div class="col col-md-auto">';

				$cur_info[] = $HcaPainterPunchList->rowExists($check_list, $cur_item['id']);

				if ($cur_item['replaced_action'] == 1)
				{
					$cur_info[] = '<div class="form-check form-check-inline">';
					$cur_info[] = $HcaPainterPunchList->getReplaced($check_list, $cur_item['id']);
					$cur_info[] = '<label class="form-check-label" for="field'.$HcaPainterPunchList->field_count.'">Replaced</label>';
					$cur_info[] = '</div>';
				}
				else
				{
					$job_actions = explode(',', $cur_item['job_actions']);

					if (in_array(1, $job_actions))
					{
						$cur_info[] = '<div class="form-check form-check-inline">';
						$cur_info[] = $HcaPainterPunchList->getCheckListField(1, $check_list, $cur_item['id']);
						$cur_info[] = '<label class="form-check-label" for="field'.$HcaPainterPunchList->field_count.'">Partial</label>';
						$cur_info[] = '</div>';
					}

					if (in_array(2, $job_actions))
					{
						$cur_info[] = '<div class="form-check form-check-inline">';
						$cur_info[] = $HcaPainterPunchList->getCheckListField(2, $check_list, $cur_item['id']);
						$cur_info[] = '<label class="form-check-label" for="field'.$HcaPainterPunchList->field_count.'">Complete</label>';
						$cur_info[] = '</div>';
					}

					if (in_array(3, $job_actions))
					{
						$cur_info[] = '<div class="form-check form-check-inline">';
						$cur_info[] = $HcaPainterPunchList->getCheckListField(3, $check_list, $cur_item['id']);
						$cur_info[] = '<label class="form-check-label" for="field'.$HcaPainterPunchList->field_count.'">Not Painted</label>';
						$cur_info[] = '</div>';
					}

					if (in_array(4, $job_actions))
					{
						$cur_info[] = '<div class="form-check form-check-inline">';
						$cur_info[] = $HcaPainterPunchList->getCheckListField(4, $check_list, $cur_item['id']);
						$cur_info[] = '<label class="form-check-label" for="field'.$HcaPainterPunchList->field_count.'">YES</label>';
						$cur_info[] = '</div>';
					}

					if (in_array(5, $job_actions))
					{
						$cur_info[] = '<div class="form-check form-check-inline">';
						$cur_info[] = $HcaPainterPunchList->getCheckListField(5, $check_list, $cur_item['id']);
						$cur_info[] = '<label class="form-check-label" for="field'.$HcaPainterPunchList->field_count.'">NO</label>';
						$cur_info[] = '</div>';
					}
				}

				$cur_info[] = '</div>';

				$cur_info[] = '</div>';
			}
		}

		$cur_info[] = '<div class="mb-3">';
		$cur_info[] = '<label class="form-label">Comments</label>';

		$existing_comment = false;
		foreach($check_list_comments as $cur_comment)
		{
			if ($cur_comment['location_id'] == $location['id'])
			{
				$cur_info[] = '<textarea name="existing_comment['.$cur_comment['id'].']" class="form-control">'.$cur_comment['comment'].'</textarea>';
				$existing_comment = true;
			}
		}

		if (!$existing_comment)
			$cur_info[] = '<textarea name="new_comment['.$location['id'].']" class="form-control"></textarea>';

		$cur_info[] = '</div>';

		$cur_info[] = '</div>';

		echo implode("\n", $cur_info);
	}

?>
	</div>

	<div class="card">
		<div class="card-body">
			<p><strong>Job Complete</strong></p>
			<div class="row">
				<div class="col col-md-auto">
					<div class="form-check form-check-inline border">
						<input name="completed" class="form-check-input" type="radio" id="inputRadio0" value="0" <?php echo (isset($_POST['completed']) && $_POST['completed'] == 0 || ($form_info['completed'] == 0) ? 'checked' : '') ?>>
						<label class="form-check-label" for="inputRadio0">NO</label>
					</div>
					<div class="form-check form-check-inline border">	
						<input name="completed" class="form-check-input" type="radio" id="inputRadio1" value="1" <?php echo (isset($_POST['completed']) && $_POST['completed'] == 1 || ($form_info['completed'] == 1) ? 'checked' : '') ?>>
						<label class="form-check-label" for="inputRadio1">YES</label>
					</div>
				</div>
			</div>
			<div class="mb-3">
				<p><strong>If not, why?</strong></p>
				<textarea name="remarks" class="form-control" id="FormControlRemarks" rows="2"><?php echo (isset($_POST['remarks']) ? html_encode($_POST['remarks']) : html_encode($form_info['remarks'])) ?></textarea>
			</div>
		</div>
	</div>

	<div class="card">
		<div class="card-header bg-info">
			<h6 class="card-title mb-0">List of used materials</h6>
		</div>
		<div class="card-body">
			<div class="alert alert-warning fw-bold" role="alert">Use this section to add unlisted materials.</div>
			<div class="row">
				<div class="col">
					<div class="mb-3">
						<input type="text" name="part_description" class="form-control" list="datalistOptions" placeholder="Enter name of material">
						<datalist id="datalistOptions">
<?php
	$query = array(
		'SELECT'	=> 'p.*',
		'FROM'		=> 'punch_list_painter_parts AS p',
		'ORDER BY'	=> 'p.part_name',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$parts_info = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$parts_info[] = $row;
	}

	foreach($parts_info as $part_info)
	{	
		echo '<option value="'.html_encode($part_info['part_name']).'">';
	}
?>
						</datalist>
					</div>
				</div>
				<div class="col">
					<div class="mb-3">
						<input type="number" name="quantity" class="form-control" placeholder="Quantity">
					</div>
				</div>
				<div class="col">
					<div class="mb-3">
						<button type="submit" name="add_material" class="btn btn-secondary">Add material</button>
					</div>
				</div>
			</div>
			
		</div>
	</div>

	<table class="table table-striped">
		<thead>
			<tr>
				<th>#</th>
				<th>Material Name</th>
				<th>Quantity</th>
				<th>Cost per</th>
				<th>Total Cost</th>
				<th>Action</th>
			</tr>
		</thead>
		<tbody>
<?php

	$materials_info = $DBLayer->select_all('punch_list_painter_materials', 'form_id='.$id);
	if (!empty($materials_info))
	{
		$i = 1;
		foreach($materials_info as $cur_info)
		{
			$anchor = (isset($_GET['mid']) && $_GET['mid'] == $cur_info['id']) ? 'anchor' : '';

			echo '<tr class="'.$anchor.'">';
			echo '<td><input type="hidden" value="'.$cur_info['id'].'" name="field_ids['.$cur_info['id'].']">'.$i.'</td>';
			echo '<td>'.html_encode($cur_info['part_description']).'</td>';
			echo '<td><input value="'.html_encode($cur_info['part_quantity']).'" name="part_quantity['.$cur_info['id'].']" size="5"></td>';
			echo '<td><input value="'.html_encode($cur_info['cost_per']).'" name="cost_per['.$cur_info['id'].']" size="5"></td>';
			echo '<td>'.html_encode($cur_info['cost_total']).'</td>';
			echo '<td><button type="submit" name="delete_material['.$cur_info['id'].']" class="badge bg-danger" onclick="return confirm(\'Are you sure you want to delete this item?\')">Delete</button></td>';
			echo '</tr>';
			++$i;
		}

		$total_cost = ($form_info['total_cost'] != '') ? number_format($form_info['total_cost'], 2, '.', '') : '0.00';
		echo '<tr><td colspan="3"></td><td><strong>TOTAL:</strong></td><td colspan="2"><strong>'.$total_cost.'</strong></td></tr>';
	}
	else
		$Core->add_warning('List of Used Materials is not filled in.');
?>
		</tbody>
	</table>

	<div class="card">
		<div class="card-body">
			<div class="mb-3">
				<p><strong>Comments</strong></p>
				<textarea rows="2" name="materials_comment" class="form-control" placeholder="Leave your comment"><?php echo (isset($_POST['materials_comment']) ? html_encode($_POST['materials_comment']) : html_encode($form_info['materials_comment'])) ?></textarea>
			</div>
		</div>

		<div class="card-body bg-info" style="position:fixed;bottom:0;width:100%;z-index:300;">
			<button type="submit" name="update_form" class="btn btn-sm btn-primary">Update form</button>
<?php if (empty($Core->warnings)) : ?>
			<button type="submit" name="submit_form" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to comlete this form? After submitting you cannot edit this form.')">Submit form</button>
<?php else: ?>
			<a href="#" class="btn btn-sm btn-secondary text-white">Check warnings</a>
<?php endif; ?>
		</div>

	</div>

</form>

<?php
	$punch_path = $form_info['file_path'] . '/painter_form_'.$id.'.pdf';
	if (file_exists(SITE_ROOT . $punch_path))
	{
?>
	<style>#demo_iframe {width:100%; height:400px; zoom: 2;}</style>
	<iframe id="demo_iframe" src="<?php echo BASE_URL.'/'.$punch_path.'?'.time() ?>"></iframe>
<?php
	}
?>

<?php
}
else
{
	$Core->set_page_title('Painter Punch List');
	$Core->set_page_id('punch_list_management_painter_request', 'hca_fs');
	require SITE_ROOT.'header.php';

	$query = array(
		'SELECT'	=> 'id, pro_name, manager_email',
		'FROM'		=> 'sm_property_db',
		'ORDER BY'	=> 'display_position'
	);
	if ($User->get('g_sm_property_mngr') == 1)
		$query['WHERE']	= 'id='.$User->get('sm_pm_property_id');
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$property_info = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$property_info[$row['id']] = $row;
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
		'WHERE'		=> 'group_id=9',
		'ORDER BY'	=> 'g.g_id, u.realname',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$users_info = [];
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		$users_info[] = $fetch_assoc;
	}
?>
	<div class="container-fluid">
		<form method="post" accept-charset="utf-8" action="">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			<div class="card">
				<div class="card-header">
					<h6 class="card-title mb-0">Create Painter Punch List</h6>
				</div>
				<div class="card-body">
					<div class="alert alert-warning" role="alert">Fill out this form and it will be sent to selected employee by email.</div>

					<div class="row">
						<div class="col-md-3">
							<div class="mb-3">
								<label class="form-label" for="date_requested">Date Requested</label>
								<input type="date" name="date_requested" value="<?php echo (isset($_POST['date_requested']) ? html_encode($_POST['date_requested']) : '') ?>" class="form-control" id="date_requested">
							</div>
						</div>
						<div class="col-md-3">
							<div class="mb-3">
								<label for="input_technician_id" class="form-label">Technician</label>
								<select name="technician_id" class="form-select form-select-sm" id="input_technician_id">
<?php
$optgroup = 0;
echo "\t\t\t\t\t\t".'<option value="0" selected="selected" disabled>Select an Empoyee</option>'."\n";
foreach ($users_info as $cur_user)
{
	if ($cur_user['group_id'] != $optgroup) {
		if ($optgroup) {
			echo '</optgroup>';
		}
		echo '<optgroup label="'.html_encode($cur_user['g_title']).'">';
		$optgroup = $cur_user['group_id'];
	}
	
	echo "\t\t\t\t\t\t".'<option value="'.$cur_user['id'].'">'.html_encode($cur_user['realname']).'</option>'."\n";
}
?>
								</select>
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-md-3">
							<div class="mb-3">
								<label class="form-label" for="input_property_name">Property name</label>
								<select id="property_id" name="property_id" required class="form-select form-select-sm" onchange="getUnits()">
<?php
	echo '<option value="0" selected="selected" disabled>Select a property</option>'."\n";
	foreach ($property_info as $cur_info) {
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['pro_name']).'</option>'."\n";
	}
?>
								</select>
							</div>
						</div>
						<div class="col-md-3">
							<div class="mb-3">
								<label for="input_unit_number" class="form-label">Unit #</label>
								<div id="input_unit_number">
									<input type="text" name="unit_number" value="" class="form-control" required>
								</div>
							</div>
						</div>
					</div>

					<button type="submit" name="create" class="btn btn-primary">Create Punch List</button>
				</div>
			</div>
		</form>
	</div>

<script>
function getUnits(){
	var csrf_token = "<?php echo generate_form_token($URL->link('punch_list_management_ajax_get_units')) ?>";
	var id = $("#property_id").val();
	jQuery.ajax({
		url:	"<?php echo $URL->link('punch_list_management_ajax_get_units') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({id:id,csrf_token:csrf_token}),
		success: function(re){
			$("#input_unit_number").empty().html(re.unit_number);
		},
		error: function(re){
			document.getElementById("input_unit_number").innerHTML = re;
		}
	});
}
</script>

<?php
}

require SITE_ROOT.'footer.php';
