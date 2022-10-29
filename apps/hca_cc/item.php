<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_cc', 1)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$HcaCC = new HcaCC;

if (isset($_POST['add']))
{
	$form_data = [
		'item_name'		=> isset($_POST['item_name']) ? swift_trim($_POST['item_name']) : '',
		'item_desc'		=> isset($_POST['item_desc']) ? swift_trim($_POST['item_desc']) : '',
		'frequency'		=> isset($_POST['frequency']) ? intval($_POST['frequency']) : 0,
		'department'	=> isset($_POST['department']) ? intval($_POST['department']) : 0,
		'required_by'	=> isset($_POST['required_by']) ? intval($_POST['required_by']) : 0,
		'date_due'		=> isset($_POST['date_due']) ? swift_trim($_POST['date_due']) : '',

		//'action_owner'	=> isset($_POST['action_owner']) ? intval($_POST['action_owner']) : 0,
		//'property_id'	=> isset($_POST['property_id']) ? intval($_POST['property_id']) : 0,
	];

	if ($form_data['item_name'] == '')
		$Core->add_error('Item name can not be empty.');

	if ($form_data['frequency'] == 0)
		$Core->add_error('Set frequency.');

	if ($form_data['department'] == 0)
		$Core->add_error('Set department.');

	if ($form_data['required_by'] == 0)
		$Core->add_error('Set "Required by" from dropdown.');

	if ($form_data['date_due'] == '')
		$Core->add_error('Setup "Due Date".');

	if (empty($Core->errors))
	{
		// Create a new
		$new_id = $DBLayer->insert_values('hca_cc_items', $form_data);
		
		// Add flash message
		$flash_message = 'item has been added';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_cc_item', $new_id), $flash_message);
	}
}

else if (isset($_POST['update']))
{
	$form_data = [
		'item_name'		=> isset($_POST['item_name']) ? swift_trim($_POST['item_name']) : '',
		'item_desc'		=> isset($_POST['item_desc']) ? swift_trim($_POST['item_desc']) : '',
		'frequency'		=> isset($_POST['frequency']) ? intval($_POST['frequency']) : 0,
		'department'	=> isset($_POST['department']) ? intval($_POST['department']) : 0,
		'action_owner'	=> isset($_POST['action_owner']) ? intval($_POST['action_owner']) : 0,
		'property_id'	=> isset($_POST['property_id']) ? intval($_POST['property_id']) : 0,
		'required_by'	=> isset($_POST['required_by']) ? intval($_POST['required_by']) : 0,
		'date_due'		=> isset($_POST['date_due']) ? swift_trim($_POST['date_due']) : '',
	];

	if ($form_data['item_name'] == '')
		$Core->add_error('Item name can not be empty.');

	if ($form_data['frequency'] == 0)
		$Core->add_error('Select frequency.');

	if ($form_data['department'] == 0)
		$Core->add_error('Select department.');

	if ($form_data['required_by'] == 0)
		$Core->add_error('Select "Required By".');

	if ($form_data['date_due'] == '')
		$Core->add_error('Setup "Due Date".');

	if (empty($Core->errors))
	{
		$DBLayer->update('hca_cc_items', $form_data, $id);

		// Add flash message
		$flash_message = 'item has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['delete']))
{
	$DBLayer->delete('hca_cc_items', $id);

	// Add flash message
	$flash_message = 'item has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('hca_cc_items', 0), $flash_message);
}

else if (isset($_POST['add_owner']))
{
	$form_data = [
		'user_id'	=> isset($_POST['user_id']) ? intval($_POST['user_id']) : 0,
		'item_id'	=> $id,
	];

	if ($form_data['user_id'] == 0)
		$Core->add_error('Select owner from dropdown list.');

	if (empty($Core->errors))
	{
		// Create a new
		$new_id = $DBLayer->insert_values('hca_cc_owners', $form_data);
		
		// Add flash message
		$flash_message = 'Owner has been added';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}
else if (isset($_POST['delete_owner']))
{
	$owner_id = intval(key($_POST['delete_owner']));
	$DBLayer->delete('hca_cc_owners', $owner_id);

	// Add flash message
	$flash_message = 'Owner has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

else if (isset($_POST['add_property']))
{
	$form_data = [
		'property_id'	=> isset($_POST['property_id']) ? intval($_POST['property_id']) : 0,
		'item_id'		=> $id,
	];

	if ($form_data['property_id'] == 0)
		$Core->add_error('Select property from dropdown list.');

	if (empty($Core->errors))
	{
		// Create a new
		$new_id = $DBLayer->insert_values('hca_cc_properties', $form_data);
		
		// Add flash message
		$flash_message = 'Property has been added';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}
else if (isset($_POST['delete_property']))
{
	$property_id = intval(key($_POST['delete_property']));
	$DBLayer->delete('hca_cc_properties', $property_id);

	// Add flash message
	$flash_message = 'Property has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

else if (isset($_POST['add_month']))
{
	$query = array(
		'SELECT'	=> 'i.*',
		'FROM'		=> 'hca_cc_items AS i',
		'WHERE'		=> 'i.id='.$id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$item_info = $DBLayer->fetch_assoc($result);

	$due_month = isset($_POST['due_month']) ? intval($_POST['due_month']) : 0;
	$months_due = explode(',', $item_info['months_due']);

	if ($due_month == 0)
		$Core->add_error('Select month from dropdown list.');

	if (empty($Core->errors))
	{
		if (empty($months_due))
			$months_due = [$due_month];
		else if (!in_array($due_month, $months_due))
			array_push($months_due, $due_month);

		asort($months_due);
		$form_data = [
			'months_due' => implode(',', $months_due),
		];

		// Create a new
		$DBLayer->update('hca_cc_items', $form_data, $id);
		
		// Add flash message
		$flash_message = 'Months have been updated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}
else if (isset($_POST['delete_month']))
{
	$query = array(
		'SELECT'	=> 'i.*',
		'FROM'		=> 'hca_cc_items AS i',
		'WHERE'		=> 'i.id='.$id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$item_info = $DBLayer->fetch_assoc($result);

	$delete_month = intval(key($_POST['delete_month']));
	$months_due = explode(',', $item_info['months_due']);

	if (empty($Core->errors))
	{
		$new_months = [];
		foreach($months_due as $month)
		{
			if ($month != $delete_month)
				$new_months[] = $month;
		}

		asort($new_months);
		$form_data = [
			'months_due' => implode(',', $new_months),
		];

		// Create a new
		$DBLayer->update('hca_cc_items', $form_data, $id);
		
		// Add flash message
		$flash_message = 'Month has been deleted';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
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

if ($id > 0)
{
	$Core->set_page_title('item management');
	$Core->set_page_id('hca_cc_items', 'hca_cc');
	require SITE_ROOT.'header.php';

	$query = array(
		'SELECT'	=> 'i.*',
		'FROM'		=> 'hca_cc_items AS i',
		'WHERE'		=> 'i.id='.$id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$item_info = $DBLayer->fetch_assoc($result);
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Item management</h6>
		</div>
		<div class="card-body">

			<div class="row">
				<div class="col-md-6">
					<div class="mb-3">
						<label class="form-label" for="fld_item_name">Item name</label>
						<input type="text" name="item_name" value="<?php echo isset($_POST['item_name']) ? html_encode($_POST['item_name']) : html_encode($item_info['item_name']) ?>" class="form-control" id="fld_item_name" required>
					</div>
				</div>
			</div>

			<div class="mb-3">
				<label class="form-label" for="fld_item_desc">Description</label>
				<textarea class="form-control" id="fld_item_desc" name="item_desc"><?php echo isset($_POST['item_desc']) ? html_encode($_POST['item_desc']) : html_encode($item_info['item_desc']) ?></textarea>
			</div>

			<div class="row">
				<div class="col-md-6 mb-3">
					<label class="form-label" for="fld_department">Department</label>
					<select name="department" class="form-select form-select-sm" required>
<?php
foreach($HcaCC->departments as $key => $value)
{
	if ($item_info['department'] == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.html_encode($value).'</option>'."\n";
	else
		echo '<option value="'.$key.'">'.html_encode($value).'</option>';
}
?>
					</select>
				</div>
				<div class="col-md-6 mb-3">
					<label class="form-label" for="fld_required_by">Required by</label>
					<select name="required_by" class="form-select form-select-sm" required>
						<option value="0">Select one</option>
<?php
foreach($HcaCC->required_by as $key => $value)
{
	if ($item_info['required_by'] == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.html_encode($value).'</option>'."\n";
	else
		echo '<option value="'.$key.'">'.html_encode($value).'</option>';
}
?>
					</select>
				</div>
			</div>

			<div class="row">
				<div class="col-md-6 mb-3">
					<label class="form-label" for="fld_frequency">Frequency</label>
					<select name="frequency" class="form-select form-select-sm" required>
						<option value="0">Select one</option>
<?php
foreach($HcaCC->frequency as $key => $value)
{
	if ($item_info['frequency'] == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.html_encode($value).'</option>'."\n";
	else
		echo '<option value="'.$key.'">'.html_encode($value).'</option>';
}
?>
					</select>
				</div>
				<div class="col-md-6 mb-3">
					<label class="form-label" for="fld_date_due">Due Date</label>
					<input type="date" name="date_due" value="<?php echo isset($_POST['date_due']) ? html_encode($_POST['date_due']) : (strtotime($item_info['date_due']) > 0 ? format_date($item_info['date_due'], 'Y-m-d') : '') ?>" class="form-control form-select-sm" id="fld_date_due" required>
				</div>
			</div>

			<div class="mb-3">
				<button type="submit" name="update" class="btn btn-primary">Update</button>
				<a href="<?php echo $URL->link('hca_cc_projects', $id) ?>" class="btn btn-secondary text-white">Back</a>
				<button type="submit" name="delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this item?')">Delete</button>
			</div>

		</div>
	</div>
</form>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
	<div class="card">
		<div class="card-body">
			<div class="row">
<?php
$query = array(
	'SELECT'	=> 'u.id, u.group_id, u.username, u.realname, u.email, g.g_id, g.g_title',
	'FROM'		=> 'groups AS g',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'g.g_id=u.group_id'
		)
	),
	'WHERE'		=> 'group_id=4 OR group_id=11',
	'ORDER BY'	=> 'g.g_id, u.realname',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users_info = [];
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$users_info[] = $fetch_assoc;
}

$query = [
	'SELECT'	=> 'o.*, u.realname',
	'FROM'		=> 'hca_cc_owners AS o',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'u.id=o.user_id'
		]
],
	'WHERE'		=> 'o.item_id='.$id,
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$owners_info = [];
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$owners_info[] = $fetch_assoc;
}

?>
				<div class="col-md-4">
					<div class="card-header">
						
					<div class="d-flex mb-2">
						<select name="user_id" class="form-select form-select-sm">
<?php
	$optgroup = 0;
	echo "\t\t\t\t\t\t".'<option value="0" selected="selected" disabled>Select one</option>'."\n";
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
						<button type="submit" name="add_owner" class="btn btn-sm btn-success ms-3">+</button>
					</div>

					</div>
					<table class="table table-striped table-bordered">
						<thead>
							<tr>
								<th>Action Owners</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
<?php
if (!empty($owners_info))
{
	foreach($owners_info as $cur_info)
	{
?>
		<tr>
			<td class="fw-bold"><?php echo html_encode($cur_info['realname']) ?></td>
			<td class="ta-center"><button type="submit" name="delete_owner[<?=$cur_info['id']?>]" class="badge bg-danger">Delete</button></td>
		</tr>
<?php
	}
}
else
{
	echo '<tr><td colspan="2">No action owners</td></tr>';
	$Core->add_warning('No action owners.');
}
	
?>
						</tbody>
					</table>
				</div>

<?php
$query = [
	'SELECT'	=> 'p.*, p2.pro_name',
	'FROM'		=> 'hca_cc_properties AS p',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'sm_property_db AS p2',
			'ON'			=> 'p2.id=p.property_id'
		]
],
	'WHERE'		=> 'p.item_id='.$id,
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$hca_cc_properties = [];
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$hca_cc_properties[] = $fetch_assoc;
}
?>
				<div class="col-md-4">
					<div class="card-header">
						
					<div class="d-flex mb-2">
						<select name="property_id" class="form-select form-select-sm">
<?php
$query = array(
	'SELECT'	=> 'p.*',
	'FROM'		=> 'sm_property_db AS p',
	//'WHERE'		=> 'p.id!=105 AND p.id!=113 AND p.id!=115 AND p.id!=116',
	'ORDER BY'	=> 'p.pro_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $row;
}

	echo "\t\t\t\t\t\t".'<option value="0" selected="selected" disabled>Select one</option>'."\n";
	foreach ($property_info as $cur_info)
	{
		echo "\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'">'.html_encode($cur_info['pro_name']).'</option>'."\n";
	}
?>
						</select>
						<button type="submit" name="add_property" class="btn btn-sm btn-success ms-3">+</button>
					</div>

					</div>
					<table class="table table-striped table-bordered">
						<thead>
							<tr>
								<th>Properties</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
<?php
if (!empty($hca_cc_properties))
{
	foreach($hca_cc_properties as $cur_info)
	{
?>
		<tr>
			<td class="fw-bold"><?php echo html_encode($cur_info['pro_name']) ?></td>
			<td class="ta-center"><button type="submit" name="delete_property[<?=$cur_info['id']?>]" class="badge bg-danger">Delete</button></td>
		</tr>
<?php
	}
}
else
{
	echo '<tr><td colspan="2">No properties assigned</td></tr>';
	$Core->add_warning('No properties assigned.');
}
?>
						</tbody>
					</table>
				</div>

				<div class="col-md-4">
					<div class="card-header">
						
					<div class="d-flex mb-2">
						<select name="due_month" class="form-select form-select-sm">
<?php
	echo "\t\t\t\t\t\t".'<option value="0" selected="selected" disabled>Select one</option>'."\n";
	foreach ($HcaCC->months as $key => $value)
	{
		echo "\t\t\t\t\t\t".'<option value="'.$key.'">'.$value.'</option>'."\n";
	}
?>
						</select>
						<button type="submit" name="add_month" class="btn btn-sm btn-success ms-3">+</button>
					</div>

					</div>
					<table class="table table-striped table-bordered">
						<thead>
							<tr>
								<th>Months (Quarterly and Bi-Annual only)</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
<?php
if ($item_info['months_due'] != '')
{
	$hca_cc_due_months = explode(',', $item_info['months_due']);
	foreach($hca_cc_due_months as $cur_month)
	{
		if ($cur_month != '')
		{
			$month = isset($HcaCC->months[$cur_month]) ? $HcaCC->months[$cur_month] : '';
?>
			<tr>
				<td class="fw-bold"><?php echo $month ?></td>
				<td class="ta-center"><button type="submit" name="delete_month[<?=$cur_month?>]" class="badge bg-danger">Delete</button></td>
			</tr>
	<?php
		}
	}
}
else
{
	echo '<tr><td colspan="2">No months scheduled</td></tr>';
}
?>
						</tbody>
					</table>
				</div>


			</div>

		</div>
	</div>
</form>

<?php

	$query = [
		'SELECT'	=> 'a.*, u.realname',
		'FROM'		=> 'hca_cc_actions AS a',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'hca_cc_tracks AS tr',
				'ON'			=> 'tr.id=a.track_id'
			],
			[
				'LEFT JOIN'		=> 'users AS u',
				'ON'			=> 'u.id=a.updated_by'
			],
		],
		'WHERE'		=> 'a.item_id='.$id
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$actions_info = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$actions_info[] = $row;
	}

	if (!empty($actions_info))
	{
?>
<table class="table table-striped table-bordered">
<thead>
	<tr>
		<th>Date & time</th>
		<th>Updated by</th>
		<th>Action</th>
	</tr>
</thead>
<tbody>
<?php
		foreach($actions_info as $cur_info)
		{
?>
	<tr>
		<td class="fw-bold"><?php echo format_time($cur_info['time_updated']) ?></td>
		<td class=""><?php echo html_encode($cur_info['realname']) ?></td>
		<td class=""><?php echo html_encode($cur_info['notes']) ?></td>
	</tr>
<?php
		}
?>
	</tbody>
</table>
<?php
	}

	require SITE_ROOT.'footer.php';
}

$query = [
	'SELECT'	=> 'i.*, pj.date_completed, pj.notes, pt.pro_name, u.realname',
	'FROM'		=> 'hca_cc_items AS i',
	'JOINS'		=> [
		[
			'LEFT JOIN'		=> 'hca_cc_items_tracking AS pj',
			'ON'			=> 'pj.id=i.last_tracking_id'
		],	
		[
			'LEFT JOIN'		=> 'users AS u',
			'ON'			=> 'u.id=i.action_owner'
		],	
		[
			'LEFT JOIN'		=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=i.property_id'
		],
	],
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$main_info[] = $row;
}

$Core->set_page_title('List of items');
$Core->set_page_id('hca_cc_items', 'hca_cc');
require SITE_ROOT.'header.php';
?>

<div class="card-header">
	<h6 class="card-title mb-0">Add an item</h6>
</div>
<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<div class="card">
		<div class="card-body">

			<div class="col-md-4 mb-3">
				<label class="form-label" for="fld_item_name">Item name</label>
				<input type="text" name="item_name" value="<?php echo isset($_POST['item_name']) ? html_encode($_POST['item_name']) : '' ?>" class="form-control" id="fld_item_name" required>
			</div>

			<div class="mb-3">
				<label class="form-label" for="fld_item_desc">Description</label>
				<textarea class="form-control" id="fld_item_desc" name="item_desc"><?php echo isset($_POST['item_desc']) ? html_encode($_POST['item_desc']) : '' ?></textarea>
			</div>

			<div class="col-md-4 mb-3">
				<label class="form-label" for="fld_frequency">Frequency</label>
				<select name="frequency" class="form-select form-select-sm" required>
					<option value="">Select one</option>
<?php
foreach($HcaCC->frequency as $key => $value)
{
	if (isset($_POST['frequency']) && $_POST['frequency'] == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.html_encode($value).'</option>'."\n";
	else
		echo '<option value="'.$key.'">'.html_encode($value).'</option>';
}
?>
				</select>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label" for="fld_department">Department</label>
				<select name="department" class="form-select form-select-sm" required>
					<option value="">Select one</option>
<?php
foreach($HcaCC->departments as $key => $value)
{
	if (isset($_POST['department']) && $_POST['department'] == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.html_encode($value).'</option>'."\n";
	else
		echo '<option value="'.$key.'">'.html_encode($value).'</option>';
}
?>
				</select>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label" for="fld_required_by">Required by</label>
				<select name="required_by" class="form-select form-select-sm" required>
					<option value="">Select one</option>
<?php
foreach($HcaCC->required_by as $key => $value)
{
	if (isset($_POST['required_by']) && $_POST['required_by'] == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.html_encode($value).'</option>'."\n";
	else
		echo '<option value="'.$key.'">'.html_encode($value).'</option>';
}
?>
				</select>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label" for="fld_date_due">Due Date</label>
				<input type="date" name="date_due" value="<?php echo isset($_POST['date_due']) ? html_encode($_POST['date_due']) : '' ?>" class="form-control form-select-sm" id="fld_date_due" required>
			</div>


			<button type="submit" name="add" class="btn btn-primary">Submit</button>
		</div>
	</div>
</form>

<?php
require SITE_ROOT.'footer.php';
