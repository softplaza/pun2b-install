<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->is_admmod())
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
	message($lang_common['Bad request']);

if (isset($_POST['add_new']))
{
	$form_info = [
		'property_id' => $id,
		'bldg_number' => isset($_POST['bldg_number']) ? swift_trim($_POST['bldg_number']) : '',
	];

	if ($form_info['bldg_number'] == '')
		$Core->add_error('Building number can not be empty.');

	if ($form_info['property_id'] == 0)
		$Core->add_error('Building number can not be empty.');

	if (empty($Core->errors))
	{
		$DBLayer->insert('sm_property_buildings', $form_info);

		$query = array(
			'UPDATE'	=> 'sm_property_db',
			'SET'		=> 'total_bldgs=total_bldgs+1',
			'WHERE'		=> 'id='.$id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);

		// Add flash message
		$flash_message = 'Building added.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['update']))
{
	$bldg_id = isset($_POST['bldg_id']) ? $_POST['bldg_id'] : [];
	
	if (!empty($bldg_id))
	{
		foreach ($bldg_id as $key => $val) 
		{
			$DBLayer->update('sm_property_buildings', ['bldg_number' => $val], $key);
		}
		
		// Add flash message
		$flash_message = 'Building list updated.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

// Delete All units
else if (isset($_POST['delete']))
{
	$bldg_id = intval(key($_POST['delete']));

	$DBLayer->delete('sm_property_buildings', $bldg_id);

	$query = array(
		'UPDATE'	=> 'sm_property_db',
		'SET'		=> 'total_bldgs=total_bldgs-1',
		'WHERE'		=> 'id='.$id
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	// Add flash message
	$flash_message = 'Building deleted.';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

$query = [
	'SELECT'	=> 'b.id, b.bldg_number, p.pro_name',
	'FROM'		=> 'sm_property_buildings AS b',
	'JOINS'		=> [
		[
			'INNER JOIN'		=> 'sm_property_db as p',
			'ON'				=> 'p.id=b.property_id'
		],
	],
	'WHERE'		=> 'b.property_id='.$id,
	'ORDER BY'	=> 'LENGTH(b.bldg_number), b.bldg_number',
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = [];
while ($row = $DBLayer->fetch_assoc($result))
{
	$main_info[] = $row;
}

$Core->set_page_id('sm_property_management_buildings', 'sm_property_management');
require SITE_ROOT.'header.php';
?>

<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />

	<div class="card">
		<div class="card-header">
			<h6 class="card-title mb-0">Add a new building number</h6>
		</div>
		<div class="card-body">

			<div class="row mb-3">
				<div class="col-md-4">
					<label class="form-label" for="fld_bldg_number">Building number</label>
					<input id="fld_bldg_number" class="form-control" type="text" name="bldg_number" value="<?php echo isset($_POST['bldg_number']) ? html_encode($_POST['bldg_number']) : '' ?>">
				</div>
			</div>

			<button type="submit" name="add_new" class="btn btn-primary">Add building</button>

		</div>
	</div>
</form>


<?php
if (!empty($main_info))
{
?>
<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<table class="table table-sm table-striped table-bordered">
		<thead>
			<tr>
				<th>Property name</th>
				<th>Building number</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
<?php
	foreach ($main_info as $cur_info)
	{
?>
			<tr>
				<td><?php echo html_encode($cur_info['pro_name']) ?></td>
				<td class="ta-center"><input type="text" name="bldg_id[<?php echo $cur_info['id'] ?>]" size="10" value="<?php echo html_encode($cur_info['bldg_number']) ?>"></td>
				<td class="ta-center"><button type="submit" name="delete[<?php echo $cur_info['id'] ?>]" class="badge bg-danger">Delete</button></td>
			</tr>
<?php
	}
?>
		</tbody>
	</table>
	<button type="submit" name="update" class="btn btn-primary">Update info</button>
</form>
<?php
}
else
{
?>
	<div class="alert alert-warning" role="alert">You have no items on this page.</div>
<?php
}
require SITE_ROOT.'footer.php';