<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$property_access = ($User->get('sm_pm_property_id') > 0) ? true : false;
$technician_access = ($User->get('group_id') == $Config->get('o_hca_fs_maintenance')) ? true : false;

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$hash = isset($_GET['hash']) ? swift_trim($_GET['hash']) : '';

//if (!$access || $id < 1)
//	message('You do not have access to this page. Please, login and try again.');

$PunchList = new PunchList;

if (isset($_POST['create']))
{
	$hash_key = random_key(5, true, true);
	$form_data = [
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

		// FILL OUT CHECK LIST FORM
		$query = array(
			'SELECT'	=> 'm.*, l.location_name',
			'FROM'		=> 'punch_list_management_maint_moisture AS m',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'punch_list_management_maint_locations AS l',
					'ON'			=> 'l.id=m.location_id'
				),
			),
			'ORDER BY'	=> 'l.location_name, m.moisture_name',
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$moisture_info = [];
		while ($row = $DBLayer->fetch_assoc($result)) {
			$moisture_info[] = $row;
		}

		if (!empty($moisture_info) && $form_id > 0)
		{
			foreach($moisture_info as $cur_info)
			{
				$form_data = [
					'moisture_id'		=> $cur_info['id'],
					'check_status'		=> 0,
					'form_id'			=> $id,
				];
				$new_id = $DBLayer->insert_values('punch_list_management_maint_moisture_check_list', $form_data);
			}
		}

		// Add flash message
		$flash_message = 'Form has been created';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('punch_list_management_maintenance_request', [$form_id, $hash_key]), $flash_message);
	}
}

else if (isset($_POST['add_location_item']))
{
	$form_data = [
		'item_id'				=> isset($_POST['item_id']) ? intval($_POST['item_id']) : 0,
		'item_description'		=> isset($_POST['item_description']) ? swift_trim($_POST['item_description']) : '',
		'item_status'			=> isset($_POST['item_status']) ? intval($_POST['item_status']) : 0,
		'form_id'				=> $id,
	];
	
	if ($form_data['item_id'] < 1 && $form_data['item_description'] == '')
		$Core->add_error('Item not selected. If the item does not exist in the list, enter it manually.');
	if ($form_data['item_status'] < 1)
		$Core->add_error('Action not selected.');

	if (empty($Core->errors))
	{
		// Create a new
		$new_id = $DBLayer->insert_values('punch_list_management_maint_request_items', $form_data);
		
		// Add flash message
		$flash_message = 'Item has been added';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('punch_list_management_maintenance_request', [$id, $hash]).'#locations', $flash_message);
	}
}

else if (isset($_POST['delete_location_item']))
{
	$item_id = intval(key($_POST['delete_location_item']));
	$DBLayer->delete('punch_list_management_maint_request_items', $item_id);

	// Add flash message
	$flash_message = 'Item has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('punch_list_management_maintenance_request', [$id, $hash]), $flash_message);
}

else if (isset($_POST['update_form']))
{
	// Update form Info
	$form_data = [
		'date_submitted'	=> isset($_POST['date_submitted']) ? strtotime($_POST['date_submitted']) : time(),
		'completed'			=> (isset($_POST['completed']) && $_POST['completed'] == 1) ? 1 : 0,
		'remarks'			=> isset($_POST['remarks']) ? swift_trim($_POST['remarks']) : '',
		'time_spent'		=> isset($_POST['time_spent']) ? swift_trim($_POST['time_spent']) : '',
		'moisture_comment'	=> isset($_POST['moisture_comment']) ? swift_trim($_POST['moisture_comment']) : '',
		'materials_comment'	=> isset($_POST['materials_comment']) ? swift_trim($_POST['materials_comment']) : '',

		'current_water_pressure' => isset($_POST['current_water_pressure']) ? intval($_POST['current_water_pressure']) : 0,
		'adjusted_water_pressure' => isset($_POST['adjusted_water_pressure']) ? intval($_POST['adjusted_water_pressure']) : 0,
		'current_water_temp' => isset($_POST['current_water_temp']) ? intval($_POST['current_water_temp']) : 0,
		'adjusted_water_temp' => isset($_POST['adjusted_water_temp']) ? intval($_POST['adjusted_water_temp']) : 0,
	];
	$DBLayer->update('punch_list_management_maint_request_form', $form_data, $id);

	// Update Check List
	if (isset($_POST['moisture_id']) && !empty($_POST['moisture_id']))
	{
		foreach($_POST['moisture_id'] as $key => $val)
		{
			$query = [
				'SELECT'	=> 'ch.*',
				'FROM'		=> 'punch_list_management_maint_moisture_check_list AS ch',
				'WHERE'		=> 'ch.moisture_id='.$key.' AND ch.form_id='.$id
			];
			$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
			$check_list = $DBLayer->fetch_assoc($result);

			if (!empty($check_list) && $check_list['moisture_id'] == $key) // Update
			{
				$check_list_data = [
					'check_status'		=> isset($_POST['check_status'][$key]) ? intval($_POST['check_status'][$key]) : 0,
				];
				$DBLayer->update('punch_list_management_maint_moisture_check_list', $check_list_data, 'moisture_id='.$key.' AND form_id='.$id);
			}
			else
			{
				$check_list_data = [
					'moisture_id'		=> isset($_POST['moisture_id'][$key]) ? intval($_POST['moisture_id'][$key]) : 0,
					'check_status'		=> isset($_POST['check_status'][$key]) ? intval($_POST['check_status'][$key]) : 0,
					'form_id'			=> $id,
				];
				$new_id = $DBLayer->insert_values('punch_list_management_maint_moisture_check_list', $check_list_data);
			}


		}

		$moisture_comment = isset($_POST['moisture_comment']) ? swift_trim($_POST['moisture_comment']) : '';
		$DBLayer->update('punch_list_management_maint_request_form', ['moisture_comment' => $moisture_comment], $id);
	}

	// Update Material List
	if (isset($_POST['field_ids']) && !empty($_POST['field_ids']))
	{
		$total_cost = 0;
		foreach($_POST['field_ids'] as $key => $val)
		{
			$part_number = isset($_POST['part_number'][$key]) ? $_POST['part_number'][$key] : '';
			$part_description = isset($_POST['part_description'][$key]) ? $_POST['part_description'][$key] : '';
			$part_quantity = isset($_POST['part_quantity'][$key]) ? intval($_POST['part_quantity'][$key]) : 0;
			$cost_per = isset($_POST['cost_per'][$key]) ? $_POST['cost_per'][$key] : '0.00';
			$cost_per2 = is_numeric($cost_per) ? number_format($cost_per, 2, '.', '') : 0;

			$materials_data = [
				'part_number'		=> $part_number,
				'part_description'	=> $part_description,
				'part_quantity'		=> $part_quantity,
				'cost_per'			=> $cost_per,
				'cost_total'		=> $part_quantity * $cost_per2,
			];
			$new_id = $DBLayer->update('punch_list_management_maint_request_materials', $materials_data, $key);

			$total_cost = $total_cost + $materials_data['cost_total'];
		}
		$DBLayer->update('punch_list_management_maint_request_form', ['total_cost' => $total_cost], $id);
	}

	if ($form_data['completed'] < 1 && $form_data['remarks'] == '')
		$Core->add_error('Remarks cannot be empty if job is not completed. Please leave your comment.');

	if (empty($Core->errors))
	{
		// Add flash message
		$flash_message = 'Punch List has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['add_material']))
{
	$form_data = [
		'part_number'		=> isset($_POST['part_number']) ? swift_trim($_POST['part_number']) : '',
		'part_description'	=> isset($_POST['part_description']) ? swift_trim($_POST['part_description']) : '',
		'part_quantity'		=> isset($_POST['part_quantity']) ? intval($_POST['part_quantity']) : 0,
		'type_work'			=> isset($_POST['type_work']) ? intval($_POST['type_work']) : 0,
		'form_id'			=> $id,
	];
	
	// Checking Part if parts desc found - return part_number, if not create or update
	$part_number = $PunchList->checkPart($form_data['part_description'], $form_data['part_number']);

	if ($part_number != '')
		$form_data['part_number'] = $part_number;

	if ($form_data['part_description'] == '')
		$Core->add_error('Enter part description or select from dropdown list.');
	if ($form_data['type_work'] == 0)
		$Core->add_error('Select material\'s action: "Replaced" or "Repaired".');

	if (empty($Core->errors))
	{
		// Create a new
		$new_id = $DBLayer->insert_values('punch_list_management_maint_request_materials', $form_data);
		
		// Add flash message
		$flash_message = 'Material has been added';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('punch_list_management_maintenance_request', [$id, $hash]).'&mid='.$new_id, $flash_message);
	}
}

else if (isset($_POST['delete_material']))
{
	$item_id = intval(key($_POST['delete_material']));
	$DBLayer->delete('punch_list_management_maint_request_materials', $item_id);

	// Add flash message
	$flash_message = 'Item has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('punch_list_management_maintenance_request', [$id, $hash]).'#material_list', $flash_message);
}

else if (isset($_POST['submit_form']))
{
	// Update Basic form Info
	$form_data = [
		'date_submitted'			=> time(),
		'completed'					=> (isset($_POST['completed']) && $_POST['completed'] == 1) ? 1 : 0,
		'remarks'					=> isset($_POST['remarks']) ? swift_trim($_POST['remarks']) : '',
		'time_spent'				=> isset($_POST['time_spent']) ? swift_trim($_POST['time_spent']) : '',
		'moisture_comment'			=> isset($_POST['moisture_comment']) ? swift_trim($_POST['moisture_comment']) : '',
		'materials_comment'			=> isset($_POST['materials_comment']) ? swift_trim($_POST['materials_comment']) : '',
		'submitted_by_technician'	=> time(),
	];
	
	$check_list_info = $DBLayer->select_all('punch_list_management_maint_moisture_check_list', 'form_id='.$id);

	if (empty($check_list_info))
		$Core->add_error('Apartment Check List is not filled in. Please, fill in the Apartment Check List.');
	if ($form_data['completed'] == 0 && $form_data['remarks'] == '')
		$Core->add_error('Comment cannot be empty if job is not completed. Please, leave your comment.');
	if ($form_data['time_spent'] == '')
		$Core->add_error('Field "Time spent" cannot be empty.');

	if (empty($Core->errors))
	{
		$DBLayer->update('punch_list_management_maint_request_form', $form_data, $id);

		$form_info = $DBLayer->select('punch_list_management_maint_request_form', 'id='.$id);

		if ($form_info['property_id'] > 0)
		{
			$query = array(
				'SELECT'	=> 'id, pro_name, manager_email',
				'FROM'		=> 'sm_property_db',
				'WHERE'		=> 'id='.$form_info['property_id']
			);
			$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
			$property_info = $DBLayer->fetch_assoc($result);
	
			// Send link by email to maintenance for continue
			$mail_subject = 'Apartment Punch List';
			$mail_message = [];
			$mail_message[] = 'Apartment Punch List has been completed by Technician.';
			$mail_message[] = 'To view Apartment Punch List follow this link.';
			$mail_message[] = $URL->link('punch_list_management_maintenance_request', [$id, $form_info['hash_key']]);
			
			$SwiftMailer = new SwiftMailer;
			$SwiftMailer->send($property_info['manager_email'], $mail_subject, implode("\n\n", $mail_message));
			
			$PunchList->genPDF($id);

			// COMPLETE WORK ORDER
			$work_order_data = [
				'work_status' => 2
			];
			if ($form_info['work_order_id'] > 0)
				$DBLayer->update('hca_fs_requests', $work_order_data, $form_info['work_order_id']);

			// Add flash message
			$flash_message = 'Item has been added';
			$FlashMessenger->add_info($flash_message);
			redirect($URL->link('punch_list_management_maintenance_request', [$id, $form_info['hash_key']]), $flash_message);
		}
		else
			$Core->add_error('Wrong property. Notify property manager to complete this form.');
	}
}


if ($id > 0)
{
	$locations = [];
	$query = [
		'SELECT'	=> 'l.*',
		'FROM'		=> 'punch_list_management_maint_locations AS l',
		'ORDER BY'	=> 'l.loc_position',
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result)) {
		$locations[] = $row;
	}

	$equipments = [];
	$query = [
		'SELECT'	=> 'e.*',
		'FROM'		=> 'punch_list_management_maint_equipments AS e',
		'ORDER BY'	=> 'e.eq_position',
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result)) {
		$equipments[] = $row;
	}

	$location_items = $DBLayer->select_all('punch_list_management_maint_items');

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

	$access2 = ($User->checkAccess('hca_fs') || $User->get('id') == $form_info['technician_id'] || $User->get('sm_pm_property_id') == $form_info['property_id']) ? true : false;
	if (!$access2 && ($hash == '' || $form_info['hash_key'] != $hash))
		message($lang_common['No permission']);

	if ($form_info['completed'] == 0 && $form_info['remarks'] == '')
		$Core->add_warning('If the job isn\'t completed, don\'t forget to leave a comment below "If not, why?".');

	$Core->warnings_collapsed = false;

	$Core->set_page_id('punch_list_management_maintenance_request', 'hca_fs');
	require SITE_ROOT.'header.php';

?>
<style>
.punch-col2{columns: 2;}
.punch-col3{columns: 3;}
.punch-section{margin: 10px;}
.punch-select{margin: 3px;}
.punch-section textarea{width:100%}
.punch-box {padding: 5px;margin-right: -5px;border: solid 1px #000;}
</style>

<?php
/*
	if ($form_info['submitted_by_technician'] > 0 && $technician_access)
	{
?>
	<div class="card">
		<div class="card-body">
			<div class="alert alert-success fw-bold" role="alert">Punch List Form has been completed.</div>
		</div>
	</div>

<?php if (file_exists('files/maintenance_punch_list.pdf')) : ?>
	<style>#demo_iframe {width:100%; height:400px; zoom: 2;}</style>
	<iframe name="emergency_schedule" id="demo_iframe" src="files/maintenance_punch_list.pdf?<?php echo time() ?>"></iframe>
<?php endif; ?>
<?php
		require SITE_ROOT.'footer.php';
	}
*/
	$items_info = [];
	$query = [
		'SELECT'	=> 'i.*, e.equipment_name, l.location_name',
		'FROM'		=> 'punch_list_management_maint_items AS i',
		'JOINS'		=> [
			[
				'LEFT JOIN'		=> 'punch_list_management_maint_equipments AS e',
				'ON'			=> 'e.id=i.equipment_id'
			],
			[
				'INNER JOIN'	=> 'punch_list_management_maint_locations AS l',
				'ON'			=> 'l.id=i.location_id'
			],
		],
		'ORDER BY'	=> 'e.equipment_name, i.item_name',
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result)) {
		$items_info[] = $row;
	}

	//print_dump($form_info);
	$positions = [
		1 => 'Replaced', 
		2 => 'Repaired', 
		3 => 'Parts on Order',
		4 => 'Re-Keyed', 
	];
?>
<!--PAGE 1 START-->
<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />

	<div class="card" id="locations">
		<div class="card-body">
			<div class="row">
				<div class="col border">
					<label class="form-label">Property:</label>
					<h6><?php echo html_encode($form_info['pro_name']) ?><h6>
				</div>
				<div class="col border">
					<label class="form-label">Unit #:</label>
					<h6><?php echo html_encode($form_info['unit_number']) ?><h6>
				</div>
				<div class="col border">
					<label class="form-label">Technician:</label>
					<h6><?php echo html_encode($form_info['realname']) ?><h6>
				</div>
				<div class="col border">
					<label class="form-label">The date will be set automatically after completing the form.</label>
				</div>
			</div>
		</div>
	</div>

	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Apartment Punch List</h6>
		</div>
		<div class="card-body">
			<div class="alert alert-warning fw-bold" role="alert">Use this section to create list.</div>
			<div class="row">
				<div class="col">
					<div class="mb-3">
						<select name="location_id" class="form-select form-select-sm" id="location_id" onchange="getLocationItems()">
<?php
	echo "\t\t\t\t\t\t".'<option value="0" selected disabled>Select Location</option>'."\n";
	foreach($locations as $location)
	{
		echo "\t\t\t\t\t\t".'<option value="'.$location['id'].'">'.html_encode($location['location_name']).'</option>'."\n";
	}
?>
						</select>
					</div>
				</div>

				<div class="col">
					<div class="mb-3" id="form_item_id">
						<input type="text" name="item_description" class="form-control">
					</div>
				</div>

				<div class="col">
					<div class="mb-3">
						<select name="item_status" class="form-select form-select-sm">
						<option value="0" selected disabled>Select action</option>
<?php
	foreach($positions as $key => $val)
	{
		echo '<option value="'.$key.'">'.$val.'</option>';
	}
?>
						</select>
					</div>
				</div>

				<div class="col">
					<button type="submit" name="add_location_item" class="btn btn-secondary btn-sm">Add item</button>
				</div>
			</div>
		</div>
	</div>

	<div class="card">
		<div class="card-body">
<?php
	$request_info = [];
	$query = [
		'SELECT'	=> 'r.*, i.item_name, i.location_id, i.equipment_id, e.equipment_name',
		'FROM'		=> 'punch_list_management_maint_request_items AS r',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'punch_list_management_maint_request_form AS f',
				'ON'			=> 'f.id=r.form_id'
			],
			[
				'LEFT JOIN'		=> 'punch_list_management_maint_items AS i',
				'ON'			=> 'i.id=r.item_id'
			],
			[
				'LEFT JOIN'		=> 'punch_list_management_maint_equipments AS e',
				'ON'			=> 'e.id=i.equipment_id'
			],
		],
		'ORDER BY'	=> 'e.eq_position, i.item_name',
		'WHERE'		=> 'f.id='.$id
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result)) {
		$request_info[] = $row;
	}

	if (empty($request_info))
		$Core->add_warning('The Apartment Punch List is not created. To fill in, use the drop-down list to add each item.');

foreach($locations as $location)
{
	$location_output = [];
	$location_items_output = [];

	$location_output[] = '<div class="row">';
	$location_output[] = '<h6><strong style="text-decoration:underline;color: brown;">'.html_encode($location['location_name']).':</strong></h6>';
	$location_output[] = '</div>';

	foreach($request_info as $request_item) 
	{
		// if MAIN CATEGORY
		if ($request_item['equipment_id'] == 0 && $location['id'] == $request_item['location_id'])
		{
			$item_status = isset($positions[$request_item['item_status']]) ? $positions[$request_item['item_status']] : 'n/a';
			$location_items_output[] = '<div class="row border">';
			$location_items_output[] = '<div class="col"><span style="font-weight:bold">'.$request_item['item_name'].'</span></div>';
			$location_items_output[] = '<div class="col"><span style="text-decoration:underline">'.$item_status.'</span></div>';
			$location_items_output[] = '<div class="col"><button type="submit" name="delete_location_item['.$request_item['id'].']" class="badge bg-danger text-white" onclick="return confirm(\'Are you sure you want to delete this item?\')">Delete</button></div>';
			$location_items_output[] = '</div>';
		}
	}

	if (!empty($location_items_output))
	{
		echo implode("\n", $location_output);
		echo implode("\n", $location_items_output);
	}

	foreach($equipments as $equipment)
	{
		$equipment_output = [];
		$equipment_items_output = [];
		if ($equipment['location_id'] == $location['id'])
		{
			$equipment_name = ($equipment['equipment_name'] != '') ? $equipment['equipment_name'] : '';
			$equipment_output[] = '<div class="row">';
			$equipment_output[] = '<h6><strong style="text-decoration:underline;color: brown;">'.html_encode($location['location_name']).': '.$equipment_name.'</strong></h6>';
			$equipment_output[] = '</div>';
		}

		
		foreach($request_info as $request_item) 
		{
			if ($request_item['equipment_id'] == $equipment['id'] && $location['id'] == $request_item['location_id'])
			{
				$item_status = isset($positions[$request_item['item_status']]) ? $positions[$request_item['item_status']] : 'n/a';
				$equipment_items_output[] = '<div class="row border">';
				$equipment_items_output[] = '<div class="col"><span style="font-weight:bold">'.$request_item['item_name'].'</span></div>';
				$equipment_items_output[] = '<div class="col"><span style="text-decoration:underline">'.$item_status.'</span></div>';
				$equipment_items_output[] = '<div class="col"><button type="submit" name="delete_location_item['.$request_item['id'].']" class="badge bg-danger text-white" onclick="return confirm(\'Are you sure you want to delete this item?\')">Delete</button></div>';
				$equipment_items_output[] = '</div>';
			}
		}

		if (!empty($equipment_items_output))
		{
			echo implode("\n", $equipment_output);
			echo implode("\n", $equipment_items_output);
		}
	}
}

/*
	$other_items = [];
	$other_items[] = '<div class="row">';
	$other_items[] = '<h6><strong style="text-decoration:underline;color: brown;">OTHER</strong></h6>';
	$other_items[] = '</div>';

	foreach($request_info as $request_item) 
	{
		if ($request_item['item_description'] != '')
		{
			$item_status = isset($positions[$request_item['item_status']]) ? $positions[$request_item['item_status']] : 'n/a';

			$other_items[] = '<div class="row border">';
			$other_items[] = '<div class="col"><span style="font-weight:bold">'.$request_item['item_description'].'</span></div>';
			$other_items[] = '<div class="col"><span style="text-decoration:underline">'.$item_status.'</span></div>';
			$other_items[] = '<div class="col"><button type="submit" name="delete_location_item['.$request_item['id'].']" class="badge bg-danger text-white" onclick="return confirm(\'Are you sure you want to delete this item?\')">Delete</button></div>';
			$other_items[] = '</div>';
		}
	}
	echo implode("\n", $other_items);
*/

?>
		</div>
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
			<div class="mb-3">
				<p><strong>Time spent (hours)</strong></p>
				<input type="text" name="time_spent" value="<?php echo (isset($_POST['time_spent']) ? html_encode($_POST['time_spent']) : html_encode($form_info['time_spent'])) ?>" id="FormControl_time_spent">
			</div>
<!--
			<div class="mb-3">
				<button type="submit" name="form_sent" class="btn btn-primary">Submit Punch List</button>
			</div>
-->
		</div>
	</div>


	<!--PAGE 2-->
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Apartment Check List</h6>
		</div>
		<div class="card-body punch-col2">
<?php 
	$check_statuses = [
		0 => 'Select one',
		1 => 'OK',
		2 => 'YES',
		3 => 'NO',
		4 => 'Repaired',
		5 => 'Replaced'
	];

	$query = array(
		'SELECT'	=> 'm.*, l.location_name',
		'FROM'		=> 'punch_list_management_maint_moisture AS m',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'punch_list_management_maint_locations AS l',
				'ON'			=> 'l.id=m.location_id'
			),
		),
		'ORDER BY'	=> 'l.location_name, m.moisture_name',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$moisture_info = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$moisture_info[] = $row;
	}

	$moisture_items = [];
	$query = [
		'SELECT'	=> 'ch.*, m.moisture_name, m.location_id, m.status_exceptions, l.location_name',
		'FROM'		=> 'punch_list_management_maint_moisture_check_list AS ch',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'punch_list_management_maint_request_form AS f',
				'ON'			=> 'f.id=ch.form_id'
			],
			[
				'INNER JOIN'	=> 'punch_list_management_maint_moisture AS m',
				'ON'			=> 'm.id=ch.moisture_id'
			],
			[
				'INNER JOIN'	=> 'punch_list_management_maint_locations AS l',
				'ON'			=> 'l.id=m.location_id'
			],
		],
	//	'ORDER BY'	=> 'l.location_name, i.item_name',
		'WHERE'		=> 'f.id='.$id
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result)) {
		$moisture_items[] = $row;
	}

	if (!empty($moisture_items))
	{
		$location_id = $check_status_counter = 0;
		foreach($moisture_items as $cur_info)
		{
			if ($location_id != $cur_info['location_id'])
			{
				if ($location_id) {
					echo '</div>';
				}
				echo '<div class="punch-section">';
				echo '<strong>'.html_encode($cur_info['location_name']).'</strong>';
				$location_id = $cur_info['location_id'];
			}

			$check_status_output = [];
			$checked_css = ($cur_info['check_status'] == 0 ? 'text-danger' : 'text-success');
			$check_status_output[] = '<input type="hidden" name="moisture_id['.$cur_info['moisture_id'].']" value="'.$cur_info['moisture_id'].'">';
			$check_status_output[] = '<select name="check_status['.$cur_info['moisture_id'].']">';
			//$check_status_output[] = '<option value="0" selected>- - -</option>';

			$status_exceptions = explode(',', $cur_info['status_exceptions']);
			foreach($check_statuses as $key => $val)
			{
				if ($key == $cur_info['check_status'])
					$check_status_output[] = '<option value="'.$key.'" selected>'.$val.'</option>';
				else if (in_array($key, $status_exceptions))
					$check_status_output[] = '<option value="'.$key.'">'.$val.'</option>';
			}
			$check_status_output[] = '</select>';

			echo '<p>'.implode('', $check_status_output).'<span style="margin-left:5px" class="'.$checked_css.'">'.$cur_info['moisture_name'].'<span></p>';

			if ($cur_info['check_status'] == 0)
				++$check_status_counter;
		}

		echo '</div>';

		if ($check_status_counter > 0)
		$Core->add_warning($check_status_counter.' item(s) are not checked on the checklist.');
			//echo '<div class="alert alert-alert fw-bold" role="alert">'.$check_status_counter.' item(s) are not checked on the checklist.</div>';
	}
	else
	{
		$Core->add_warning('The Apartment Checklist is not filled in.');

		$location_id = 0;
		foreach($moisture_info as $cur_info)
		{
			if ($location_id != $cur_info['location_id'])
			{
				if ($location_id) {
					echo '</div>';
				}
				echo '<div class="punch-section">';
				echo '<strong>'.html_encode($cur_info['location_name']).'</strong>';
				$location_id = $cur_info['location_id'];
			}

			$check_status_output = [];
			$check_status_output[] = '<input type="hidden" name="moisture_id['.$cur_info['id'].']" value="'.$cur_info['id'].'">';
			$check_status_output[] = '<select name="check_status['.$cur_info['id'].']">';
			//$check_status_output[] = '<option value="0" selected>- - -</option>';
			foreach($check_statuses as $key => $val)
			{
				$check_status_output[] = '<option value="'.$key.'">'.$val.'</option>';
			}
			$check_status_output[] = '</select>';
			echo '<p>'.implode('', $check_status_output).'<span style="margin-left:5px">'.$cur_info['moisture_name'].'<span></p>';
		}
		echo '</div>';
	}
?>
		</div>

		<div class="card-body">

			<div class="row mb-3">
				
				<div class="alert alert-danger my-3 hidden" role="alert" id="alert_adjusted_water_pressure">The entered value exceeds the water pressure limit 58 psi. Fill the field "Adjuster water pressure".</div>

				<div class="col-md-auto mb-1">
					<label class="form-label" for="fld_current_water_pressure">Current water pressure (psi)</label>
					<input type="number" name="current_water_pressure" value="<?php echo (isset($_POST['current_water_pressure']) ? html_encode($_POST['current_water_pressure']) : html_encode($form_info['current_water_pressure'])) ?>" id="fld_current_water_pressure" class="form-control" min="0" max="999" onchange="checkMaxPressure()" oninput="checkMaxPressure()">
					<label class="text-muted">Enter an integer without letters</label>
				</div>
				<div class="col-md-auto mb-1 <?php echo ($form_info['current_water_pressure'] < 59 ? 'hidden' : '') ?>" id="col_adjusted_water_pressure">
					<label class="form-label text-danger" for="fld_adjusted_water_pressure">Adjuster water pressure (psi)</label>
					<input type="number" name="adjusted_water_pressure" value="<?php echo (isset($_POST['adjusted_water_pressure']) ? html_encode($_POST['adjusted_water_pressure']) : html_encode($form_info['adjusted_water_pressure'])) ?>" id="fld_adjusted_water_pressure" class="form-control">
					<label class="text-muted">Enter an integer without letters</label>
				</div>
			</div>

			<div class="row mb-3">

				<div class="alert alert-danger my-3 hidden" role="alert" id="alert_adjusted_water_temp">The entered value exceeds the water temperature limit 120 °F. Fill the field "Adjuster water temperature".</div>

				<div class="col-md-auto mb-1">
					<label class="form-label" for="fld_current_water_temp">Current water temperature (°F)</label>
					<input type="number" name="current_water_temp" value="<?php echo (isset($_POST['current_water_temp']) ? html_encode($_POST['current_water_temp']) : html_encode($form_info['current_water_temp'])) ?>" id="fld_current_water_temp" class="form-control" min="0" max="999" onchange="checkMaxTemp()" oninput="checkMaxTemp()">
					<label class="text-muted">Enter an integer without letters</label>
				</div>
				<div class="col-md-auto mb-1 <?php echo ($form_info['current_water_temp'] < 121 ? 'hidden' : '') ?>" id="col_adjusted_water_temp">
					<label class="form-label text-danger" for="fld_adjusted_water_temp">Adjuster water temperature (°F)</label>
					<input type="number" name="adjusted_water_temp" value="<?php echo (isset($_POST['adjusted_water_temp']) ? html_encode($_POST['adjusted_water_temp']) : html_encode($form_info['adjusted_water_temp'])) ?>" id="fld_adjusted_water_temp" class="form-control">
					<label class="text-muted">Enter an integer without letters</label>
				</div>
			</div>

			<div class="mb-3">
				<p><strong>Comment</strong></p>
				<textarea rows="2" name="moisture_comment" class="form-control"><?php echo (isset($_POST['moisture_comment']) ? html_encode($_POST['moisture_comment']) : html_encode($form_info['moisture_comment'])) ?></textarea>
			</div>
		</div>
	</div>


	<!-- PAGE 3 MATERIALS USED-->
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">List of used materials</h6>
		</div>
		<div class="card-body">
			<div class="alert alert-warning fw-bold" role="alert">Use this section to add unlisted materials.</div>
			<div class="row">
				<div class="col">
					<div class="mb-3">
						<input type="text" name="part_number" class="form-control" placeholder="Enter part #">
					</div>
				</div>
				<div class="col">
					<div class="mb-3">
						<input type="text" name="part_description" class="form-control" list="datalistOptions" placeholder="Select part or enter part description">
						<datalist id="datalistOptions">
<?php
	$query = array(
		'SELECT'	=> 'p.*, g.group_name',
		'FROM'		=> 'punch_list_management_maint_parts AS p',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'	=> 'punch_list_management_maint_parts_group AS g',
				'ON'			=> 'g.id=p.group_id'
			),
		),
		'ORDER BY'	=> 'g.group_name, p.part_name',
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$parts_info = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$parts_info[] = $row;
	}

	$optgroup = 0;
	foreach($parts_info as $part_info)
	{
		if ($part_info['group_id'] != $optgroup)
		{
			if ($optgroup) {
			//	echo '</optgroup>';
			}
			//echo '<optgroup label="'.html_encode($part_info['group_name']).'">';
			$optgroup = $part_info['group_id'];
			echo '<option value="- - '.html_encode($part_info['group_name']).'">';
		}
		echo '<option value="'.html_encode($part_info['part_name']).'">';
	}
?>
						</datalist>
					</div>
				</div>
				<div class="col">
					<div class="mb-3">
						<select name="type_work" class="form-select form-select-sm">
							<option value="0" disabled selected>Work performed</option>
							<option value="1">Replaced</option>
							<option value="2">Repaired</option>
						</select>
					</div>
				</div>
				<div class="col">
					<div class="mb-3">
						<input type="number" name="part_quantity" class="form-control" placeholder="Quantity" min="0">
					</div>
				</div>
			</div>
			<button type="submit" name="add_material" class="btn btn-secondary float-end">Add material</button>
		</div>
	</div>

	<table class="table">
		<thead>
			<tr>
				<th></th>
				<th>Part#</th>
				<th>Description</th>
				<th>Quantity</th>
				<th>Work Performed</th>
				<th>Cost per Part</th>
				<th>Total Cost</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
<?php

	$materials_info = $DBLayer->select_all('punch_list_management_maint_request_materials', 'form_id='.$id);
	if (!empty($materials_info))
	{
		$i = 1;
		foreach($materials_info as $cur_info)
		{
			$type_work = ($cur_info['type_work'] == 1) ? 'Replaced' : 'Repaired';
			$anchor = (isset($_GET['mid']) && $_GET['mid'] == $cur_info['id']) ? 'anchor' : '';

			echo '<tr class="'.$anchor.'">';
			echo '<td><input type="hidden" value="'.$cur_info['id'].'" name="field_ids['.$cur_info['id'].']">'.$i.'</td>';
			//echo '<td>'.html_encode($cur_info['part_number']).'</td>';
			echo '<td><input value="'.html_encode($cur_info['part_number']).'" name="part_number['.$cur_info['id'].']" size="7"></td>';

			//echo '<td>'.html_encode($cur_info['part_description']).'</td>';
			echo '<td><textarea name="part_description['.$cur_info['id'].']">'.html_encode($cur_info['part_description']).'</textarea></td>';

			echo '<td><input value="'.html_encode($cur_info['part_quantity']).'" name="part_quantity['.$cur_info['id'].']" size="5"></td>';
			echo '<td>'.$type_work.'</td>';
			echo '<td><input value="'.html_encode($cur_info['cost_per']).'" name="cost_per['.$cur_info['id'].']" size="5"></td>';
			echo '<td>'.html_encode($cur_info['cost_total']).'</td>';
			echo '<td><button type="submit" name="delete_material['.$cur_info['id'].']" class="badge bg-danger" onclick="return confirm(\'Are you sure you want to delete this item?\')">Delete</button></td>';
			echo '</tr>';
			++$i;
		}

		$total_cost = ($form_info['total_cost'] != '') ? html_encode($form_info['total_cost']) : '0.00';
		echo '<tr><td colspan="5"></td><td><strong>TOTAL:</strong></td><td colspan="2"><strong>'.$total_cost.'</strong></td></tr>';
	}
	else
		$Core->add_warning('List of Used Materials is not filled in.');
?>
		</tbody>
	</table>

	<div class="card">
		<div class="card-body">
			<div class="mb-3">
				<p><strong>Comment</strong></p>
				<textarea rows="2" name="materials_comment" class="form-control"><?php echo (isset($_POST['materials_comment']) ? html_encode($_POST['materials_comment']) : html_encode($form_info['materials_comment'])) ?></textarea>
			</div>
		
			<button type="submit" name="update_form" class="btn btn-sm btn-primary">Save changes</button>
<?php if (empty($Core->warnings)) : ?>
			<button type="submit" name="submit_form" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to comlete this form? After submitting you cannot edit this form.')">Submit form</button>
<?php else: ?>
			<a href="#" class="btn btn-sm btn-secondary text-white">Check warnings</a>
<?php endif; ?>
			
		</div>
	</div>
</form>

<?php
	$punch_path = $form_info['file_path'] . '/maintenance_form_'.$id.'.pdf';
	if (file_exists(SITE_ROOT . $punch_path))
	{
?>
<style>#demo_iframe {width:100%; height:400px; zoom: 2;}</style>
<iframe id="demo_iframe" src="<?php echo BASE_URL.'/'.$punch_path.'?'.time() ?>"></iframe>
<?php
	}
?>

<script>
function getLocationItems(){
	var csrf_token = "<?php echo generate_form_token($URL->link('punch_list_management_ajax_get_location_items')) ?>";
	var id = $("#location_id").val();
	jQuery.ajax({
		url:	"<?php echo $URL->link('punch_list_management_ajax_get_location_items') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({id:id,csrf_token:csrf_token}),
		success: function(re){
			$("#form_item_id").empty().html(re.form_item_id);
		},
		error: function(re){
			$("#form_item_id").empty().html('Error. Update the page and try again.');
		}
	});
}

function checkMaxPressure(){
	var v = $('#fld_current_water_pressure').val();
	if (v > 58)
		$('#col_adjusted_water_pressure, #alert_adjusted_water_pressure').css('display', 'block');
	else
		$('#col_adjusted_water_pressure, #alert_adjusted_water_pressure').css('display', 'none');
}
function checkMaxTemp(){
	var v = $('#fld_current_water_temp').val();
	if (v > 120)
		$('#col_adjusted_water_temp, #alert_adjusted_water_temp').css('display', 'block');
	else
		$('#col_adjusted_water_temp, #alert_adjusted_water_temp').css('display', 'none');
}
</script>
<?php
}
else
{
	$Core->set_page_id('punch_list_management_maintenance_request', 'hca_fs');
	require SITE_ROOT.'header.php';

	$query = array(
		'SELECT'	=> 'id, pro_name, manager_email',
		'FROM'		=> 'sm_property_db',
		'ORDER BY'	=> 'display_position'
	);
	if ($User->get('g_sm_property_mngr') == 1)
		$query['WHERE']	= 'id='.$User->get('sm_pm_property_id');
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$property_info = array();
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
		'WHERE'		=> 'group_id = 3',
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
			<div class="alert alert-warning fw-bold" role="alert">Enter basic information and press "Start filling"</div>
			<div class="card">
				<div class="card-body">
					<div class="row">
						<div class="col-md-8">
							<div class="mb-3">
								<label class="form-label" for="input_property_name">Property name</label>
								<select id="property_id" name="property_id" required class="form-control" onchange="getUnits()">
<?php
	echo '<option value="0" selected="selected" disabled>Select a property</option>'."\n";
	foreach ($property_info as $cur_info) {
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['pro_name']).'</option>'."\n";
	}
?>
								</select>
							</div>
							<div class="mb-3">
								<label for="input_unit_number" class="form-label">Unit #</label>
								<div id="input_unit_number">
									<input type="text" name="unit_number" value="" class="form-control" required>
								</div>
							</div>
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
					<button type="submit" name="create" class="btn btn-primary">Start filling</button>
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
?>
</div>
<?php
require SITE_ROOT.'footer.php';
