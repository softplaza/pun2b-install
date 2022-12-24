<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_wom', 55))
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$HcaWOM = new HcaWOM;

if (isset($_POST['add']))
{
	$form_data = [
		'item_type' => isset($_POST['item_type']) ? intval($_POST['item_type']) : 0,
		'item_name'		=> isset($_POST['item_name']) ? swift_trim($_POST['item_name']) : '',
	];

	$item_actions = [];
	if (isset($_POST['item_actions']) && !empty($_POST['item_actions']))
	{
		foreach($_POST['item_actions'] as $key => $val)
		{
			if ($val == 1)
				$item_actions[] = $key;
		}
	}
	$form_data['item_actions'] = !empty($item_actions) ? implode(',', $item_actions) : '';

	if ($form_data['item_name'] == '')
		$Core->add_error('Item name cannot be empty.');

	if ($form_data['item_type'] == 0)
		$Core->add_error('Select type of item.');

	if (empty($Core->errors))
	{
		// Create a new item
		$new_id = $DBLayer->insert_values('hca_wom_items', $form_data);
		
		// Add flash message
		$flash_message = 'Item has been added.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}
else if (isset($_POST['add_type']))
{
	$form_data = [
		'type_name'		=> isset($_POST['type_name']) ? swift_trim($_POST['type_name']) : '',
	];

	if ($form_data['type_name'] == '')
		$Core->add_error('Type name cannot be empty.');

	if (empty($Core->errors))
	{
		// Create a new type
		$new_id = $DBLayer->insert_values('hca_wom_types', $form_data);
		
		// Add flash message
		$flash_message = 'Type of item has been added.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}
else if (isset($_POST['delete_type']))
{
	$DBLayer->delete('hca_wom_items', $id);
	
	// Add flash message
	$flash_message = 'Item has been deleted.';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('hca_wom_admin_items', 0), $flash_message);
}
else if (isset($_POST['add_problem']))
{
	$form_data = [
		'problem_name'		=> isset($_POST['problem_name']) ? swift_trim($_POST['problem_name']) : '',
	];

	if ($form_data['problem_name'] == '')
		$Core->add_error('Problem/action name cannot be empty.');

	if (empty($Core->errors))
	{
		// Create a new type
		$new_id = $DBLayer->insert_values('hca_wom_problems', $form_data);
		
		// Add flash message
		$flash_message = 'Problem/action of item has been added.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}
else if (isset($_POST['delete_problem']))
{
	$DBLayer->delete('hca_wom_items', $id);
	
	// Add flash message
	$flash_message = 'Item has been deleted.';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('hca_wom_admin_items', 0), $flash_message);
}
else if (isset($_POST['update']))
{
	$form_data = [
		'item_type' => isset($_POST['item_type']) ? intval($_POST['item_type']) : '',
		'item_name'		=> isset($_POST['item_name']) ? swift_trim($_POST['item_name']) : '',
	];

	$item_actions = [];
	if (isset($_POST['item_actions']) && !empty($_POST['item_actions']))
	{
		foreach($_POST['item_actions'] as $key => $val)
		{
			if ($val == 1)
				$item_actions[] = $key;
		}
	}
	$form_data['item_actions'] = !empty($item_actions) ? implode(',', $item_actions) : '';

	if ($form_data['item_name'] == '')
		$Core->add_error('Iitem name cannot be empty.');

	if ($form_data['item_type'] == 0)
		$Core->add_error('Select type of item.');

	if (empty($Core->errors))
	{
		$DBLayer->update('hca_wom_items', $form_data, $id);

		// Add flash message
		$flash_message = 'Item has been updated.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['delete']))
{
	$DBLayer->delete('hca_wom_items', $id);
	
	// Add flash message
	$flash_message = 'Item has been deleted.';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('hca_wom_admin_items', 0), $flash_message);
}

if ($id > 0)
{
	$Core->set_page_id('hca_wom_admin_items', 'hca_fs');
	require SITE_ROOT.'header.php';

	$query = array(
		'SELECT'	=> 'i.*',
		'FROM'		=> 'hca_wom_items AS i',
		'WHERE'		=> 'i.id='.$id,
		'ORDER BY'	=> 'i.item_name'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$item_info = $DBLayer->fetch_assoc($result);

?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Edit item</h6>
		</div>
		<div class="card-body">

			<div class="row">
				<div class="col-md-4">
					<div class="mb-3">
						<label class="form-label" for="fld_item_type">Type of item</label>
						<select name="item_type" class="form-select form-select-sm" required>

<?php
	$query = array(
		'SELECT'	=> 'tp.*',
		'FROM'		=> 'hca_wom_types AS tp',
		'ORDER BY'	=> 'tp.type_name'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$hca_wom_types = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$hca_wom_types[$row['id']] = $row['type_name'];
	}

	foreach($hca_wom_types as $key => $value)
	{
		if ($key == $item_info['item_type'])
			echo '<option value="'.$key.'" selected>'.html_encode($value).'</option>';
		else
			echo '<option value="'.$key.'">'.html_encode($value).'</option>';
	}
?>
						</select>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-4">
					<div class="mb-3">
						<label class="form-label" for="fld_item_name">Item name</label>
						<input type="text" name="item_name" value="<?php echo html_encode($item_info['item_name']) ?>" class="form-control" id="fld_item_name" required>
					</div>
				</div>
			</div>

			<h6 class="card-title mb-0">Item actions</h6>
			<hr class="my-2">
			<div class="mb-3">
<?php

$query = array(
	'SELECT'	=> 'pr.*',
	'FROM'		=> 'hca_wom_problems AS pr',
	'ORDER BY'	=> 'pr.problem_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$hca_wom_problems = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$hca_wom_problems[$row['id']] = $row['problem_name'];
}

$item_actions = explode(',', $item_info['item_actions']);
foreach($hca_wom_problems as $key => $value)
{
	$checked = in_array($key, $item_actions) ? 'checked' : '';
?>
				<div class="form-check form-check-inline">
					<input type="hidden" name="item_actions[<?=$key?>]" value="0">
					<input class="form-check-input" id="fld_item_actions<?=$key?>" type="checkbox" name="item_actions[<?=$key?>]" value="1" <?php echo $checked ?>>
					<label class="form-check-label" for="fld_item_actions<?=$key?>"><?php echo $value ?></label>
				</div>
<?php
}
?>
			</div>

			<button type="submit" name="update" class="btn btn-primary">Update</button>
			<a href="<?php echo $URL->link('hca_wom_admin_items', 0) ?>" class="btn btn-secondary text-white">Back</a>
<?php if ($User->is_admin()): ?>
			<button type="submit" name="delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this item?')">Delete</button>
<?php endif; ?>
		</div>
	</div>
</form>

<?php
	require SITE_ROOT.'footer.php';
}

$Core->set_page_id('hca_wom_admin_items', 'hca_fs');
require SITE_ROOT.'header.php';
?>

<div class="accordion accordion-flush" id="accordionFlushExample">
  <div class="accordion-item">
    <h2 class="accordion-header" id="flush-headingOne">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseOne" aria-expanded="false" aria-controls="flush-collapseOne">Add an item</button>
    </h2>
    <div id="flush-collapseOne" class="accordion-collapse collapse" aria-labelledby="flush-headingOne" data-bs-parent="#accordionFlushExample">
		<div class="accordion-body card-body">
			<form method="post" accept-charset="utf-8" action="">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
				<div class="row">
					<div class="col-md-4">
						<div class="mb-3">
							<label class="form-label" for="fld_item_type">Type of item</label>
							<select name="item_type" class="form-select form-select-sm" required>
<?php
$query = array(
	'SELECT'	=> 'tp.*',
	'FROM'		=> 'hca_wom_types AS tp',
	'ORDER BY'	=> 'tp.type_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$hca_wom_types = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$hca_wom_types[] = $row;
}

foreach($hca_wom_types as $cur_info)
{
	echo '<option value="'.$cur_info['id'].'">'.html_encode($cur_info['type_name']).'</option>';
}
?>
							</select>
						</div>
						<div class="mb-3">
							<label class="form-label" for="fld_item_name">Item name</label>
							<input type="text" name="item_name" value="<?php echo isset($_POST['item_name']) ? html_encode($_POST['item_name']) : '' ?>" class="form-control" id="fld_item_name" required>
						</div>
					</div>
					<h6 class="card-title mb-0">Availabe Actions/Problems</h6>
					<hr class="my-1">
					<label class="text-muted">Mark the necessary actions or problems that will be displayed in the drop-down list for the created item.</label>
					
					<div class="mb-3">
<?php

$query = array(
	'SELECT'	=> 'pr.*',
	'FROM'		=> 'hca_wom_problems AS pr',
	'ORDER BY'	=> 'pr.problem_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$hca_wom_problems = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$hca_wom_problems[$row['id']] = $row['problem_name'];
}

foreach($hca_wom_problems as $key => $value)
{
?>
						<div class="form-check form-check-inline">
							<input type="hidden" name="item_actions[<?=$key?>]" value="0">
							<input class="form-check-input" id="fld_item_actions<?=$key?>" type="checkbox" name="item_actions[<?=$key?>]" value="1">
							<label class="form-check-label" for="fld_item_actions<?=$key?>"><?php echo $value ?></label>
						</div>
<?php
}
?>
					</div>
					<div class="mb-3">
						<button type="submit" name="add" class="btn btn-primary">Submit</button>
					</div>
				</div>
			</form>
		</div>
    </div>
  </div>

  <div class="accordion-item">
    <h2 class="accordion-header" id="flush-headingTwo">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseTwo" aria-expanded="false" aria-controls="flush-collapseTwo">Add type of item</button>
    </h2>
    <div id="flush-collapseTwo" class="accordion-collapse collapse" aria-labelledby="flush-headingTwo" data-bs-parent="#accordionFlushExample">
		<div class="accordion-body card-body">
			<form method="post" accept-charset="utf-8" action="">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
				<div class="row">
					<div class="col-md-4">
						<div class="mb-3">
							<label class="form-label" for="fld_type_name">Type name</label>
							<input type="text" name="type_name" value="<?php echo isset($_POST['type_name']) ? html_encode($_POST['type_name']) : '' ?>" class="form-control" id="fld_type_name" required>
						</div>
						<div class="mb-3">
							<button type="submit" name="add_type" class="btn btn-sm btn-primary">Submit</button>
						</div>
					</div>
					<div class="col-md-4">
						<table class="table table-striped table-bordered table-sm">
							<tbody>
								<tr>
									<th>List of available types</th>
								</tr>
							</tbody>
							<tbody>
<?php
$query = array(
	'SELECT'	=> 'tp.*',
	'FROM'		=> 'hca_wom_types AS tp',
	'ORDER BY'	=> 'tp.type_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$hca_wom_types = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$hca_wom_types[] = $row;
}

if (!empty($hca_wom_types))
{
	foreach($hca_wom_types as $cur_info)
	{

		echo '<tr><td>'.$cur_info['type_name'].'</td></tr>';

	}
}
?>
							</tbody>
						</table>
					</div>
				</div>
			</form>
		</div>
    </div>
  </div>

  <div class="accordion-item">
    <h2 class="accordion-header" id="flush-headingThree">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseThree" aria-expanded="false" aria-controls="flush-collapseThree">Add actions/problem of item</button>
    </h2>
    <div id="flush-collapseThree" class="accordion-collapse collapse" aria-labelledby="flush-headingThree" data-bs-parent="#accordionFlushExample">
		<div class="accordion-body card-body">
			<form method="post" accept-charset="utf-8" action="">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
				<div class="row">
					<div class="col-md-4">
						<div class="mb-3">
							<label class="form-label" for="fld_problem_name">Action/Problem name</label>
							<input type="text" name="problem_name" value="<?php echo isset($_POST['problem_name']) ? html_encode($_POST['problem_name']) : '' ?>" class="form-control" id="fld_problem_name" required>
						</div>
						<div class="mb-3">
							<button type="submit" name="add_problem" class="btn btn-sm btn-primary">Submit</button>
						</div>
					</div>

					<div class="col-md-4">
						<table class="table table-striped table-bordered table-sm">
							<tbody>
								<tr>
									<th>List of available types</th>
								</tr>
							</tbody>
							<tbody>
<?php
$query = array(
	'SELECT'	=> 'pr.*',
	'FROM'		=> 'hca_wom_problems AS pr',
	'ORDER BY'	=> 'pr.problem_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$hca_wom_problems = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$hca_wom_problems[] = $row;
}

if (!empty($hca_wom_problems))
{
	foreach($hca_wom_problems as $cur_info)
	{

		echo '<tr><td>'.$cur_info['problem_name'].'</td></tr>';

	}
}
?>
							</tbody>
						</table>
					</div>
				</div>
			</form>
		</div>
    </div>
  </div>
</div>

<div class="card-header">
	<h6 class="card-title mb-0">List of items</h6>
</div>
<?php
$query = array(
	'SELECT'	=> 'i.*, tp.type_name',
	'FROM'		=> 'hca_wom_items AS i',
	'JOINS'		=> [
		[
			'LEFT JOIN'		=> 'hca_wom_types AS tp',
			'ON'			=> 'tp.id=i.item_type'
		],
	],
	'ORDER BY'	=> 'tp.type_name, i.item_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$hca_wom_items = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$hca_wom_items[] = $row;
}

$query = array(
	'SELECT'	=> 'pr.*',
	'FROM'		=> 'hca_wom_problems AS pr',
	'ORDER BY'	=> 'pr.problem_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$hca_wom_problems = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$hca_wom_problems[$row['id']] = $row['problem_name'];
}

if (!empty($hca_wom_items))
{
?>
<table class="table table-striped table-bordered table-hover">
	<thead>
		<tr>
			<th>Type of item</th>
			<th>Item name</th>
			<th>Actions/Problems</th>
			<th></th>
		</tr>
	</thead>
	<tbody>
<?php
	$i = 1;
	foreach($hca_wom_items as $cur_info)
	{
?>
		<tr>
			<td class=""><?php echo html_encode($cur_info['type_name']) ?></td>
			<td class="fw-bold"><?php echo html_encode($cur_info['item_name']) ?></td>
			<td class="text-danger"><?php echo $HcaWOM->getActions($hca_wom_problems, $cur_info['item_actions']) ?></td>
			<td class="text-danger"><a href="<?php echo $URL->link('hca_wom_admin_items', $cur_info['id']) ?>" class="badge bg-primary text-white">Edit</a></td>
		</tr>
<?php
		++$i;
	}
?>
	</tbody>
</table>
<?php
}
else
	echo '<div class="alert alert-warning" role="alert">You have no items on this page.</div>';


require SITE_ROOT.'footer.php';
