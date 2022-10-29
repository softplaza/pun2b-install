<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$User->checkAccess('hca_pc', 3))
	message($lang_common['No permission']);

$unit_clearance_arr = array(0 => 'NO', 1 => 'YES', 2 => 'ON HOLD', 3 => 'IN PROGRESS');

if (isset($_POST['delete']))
{
	$id = intval(key($_POST['delete']));
	
	$query = array(
		'DELETE'	=> 'sm_pest_control_records',
		'WHERE'		=> 'id='.$id
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	
	// Add flash message
	$flash_message = 'The project #'.$id.' has been permanently deleted.';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}
else if (isset($_POST['update']))
{
	$id = intval(key($_POST['update']));
	$manager_check = isset($_POST['manager_check'][$id]) ? intval($_POST['manager_check'][$id]) : 0;
	$unit_clearance = isset($_POST['unit_clearance'][$id]) ? intval($_POST['unit_clearance'][$id]) : 5;
	
	if ($unit_clearance == 5)
		$Core->add_error('You must assign the status of the project to which it will return.');
	
	if ($manager_check == 0 && $unit_clearance == 1)
		$Core->add_error('You cannot archive this project because this project has not yet been confirmed by the property manager. Choose a different status.');
	
	if (empty($Core->errors))
	{
		//REPLACE - MOVE TO RECICLE
		$query = array(
			'UPDATE'	=> 'sm_pest_control_records',
			'SET'		=> 'unit_clearance='.$unit_clearance,
			'WHERE'		=> 'id='.$id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
		// Add flash message
		$flash_message = 'The project #'.$id.' has been recovered.';
		$FlashMessenger->add_info($flash_message);
		
		if ($unit_clearance == 1)
			redirect($URL->link('sm_pest_control_records', $id.'#rid'.$id), $flash_message);
		else
			redirect($URL->link('sm_pest_control_active', $id.'#rid'.$id), $flash_message);
	}

}

$query = array(
	'SELECT'	=> 'COUNT(id)',
	'FROM'		=> 'sm_pest_control_records',
	'WHERE'		=> 'unit_clearance=5'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$query = array(
	'SELECT'	=> 'r.*, p.pro_name',
	'FROM'		=> 'sm_pest_control_records AS r',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'sm_property_db AS p',
			'ON'			=> 'p.id=r.property_id'
		),
	),
	'WHERE'		=> 'r.unit_clearance=5',
	'ORDER BY'	=> 'r.property, p.pro_name',
	'LIMIT'		=> $PagesNavigator->limit(),
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$main_info[] = $fetch_assoc;
}
$PagesNavigator->num_items($main_info);

$query = array(
	'SELECT'	=> 'pro_name',
	'FROM'		=> 'sm_property_db',
	'ORDER BY'	=> 'pro_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $fetch_assoc;
}

$Core->set_page_id('sm_pest_control_recycle', 'hca_pc');
require SITE_ROOT.'header.php';
?>

<?php
if (!empty($main_info)) 
{
?>
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<table class="table table-sm table-striped table-bordered">
			<thead>
				<tr>
					<th>Property Unit#</th>
					<th>Location</th>
					<th>Reported Date/By</th>
					<th>Pest Control Problem</th>
					<th>Pest Control Action</th>
					<th>Inspection Date</th>
					<th>Vendor</th>
					<th>Start Date</th>
					<th>Follow-Up Date</th>
					<th>Notice for Manager</th>
					<th>Manager approved</th>
					<th>Projected Completion Date</th>
					<th>Unit Clearance</th>
					<th>Remarks</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
<?php
	foreach ($main_info as $info)
	{
			$page_param['td'] = array();
			$page_param['td']['property_name'] = ($info['property_id'] > 0) ? html_encode($info['pro_name']) : html_encode($info['property']);
			$page_param['td']['unit_number'] = html_encode($info['unit']);
?>
				<tr>
					<td class="property">
						<p><?php echo $page_param['td']['property_name'] ?></p>
						<p><?php echo $page_param['td']['unit_number'] ?></p>
					</td>
					<td><?php echo html_encode($info['location']) ?></td>
					<td><?php echo !empty($info['reported']) ? date('m/d/Y', $info['reported']) : '' ?>
					<?php echo $info['reported_by'] ?></td>
					<td><?php echo $info['pest_problem'] ?></td>
					<td><?php echo !empty($info['pest_action']) ? html_encode($info['pest_action']) : '' ?></td>
					<td><?php echo !empty($info['inspection_date']) ? date('m/d/Y', $info['inspection_date']) : '' ?></td>
					<td><?php echo $info['vendor'] ?></td>
					<td><?php echo !empty($info['start_date']) ? date('m/d/Y', $info['start_date']) : '' ?></td>
					<td><?php echo !empty($info['follow_up']) ? html_encode($info['follow_up']) : '' ?></td>
					<td><?php echo !empty($info['manager_action']) ? html_encode($info['manager_action']) : '' ?></td>
					<input type="hidden" name="manager_check[<?php echo $info['id'] ?>]" value="<?php echo $info['manager_check'] ?>">
					<td><?php echo ($info['manager_check'] == 1) ? 'YES' : 'NO' ?></td>
					<td><?php echo !empty($info['completion_date']) ? date('m/d/Y', $info['completion_date']) : '' ?></td>
					<td><select name="unit_clearance[<?php echo $info['id'] ?>]">
<?php
		echo '<option value="5" selected="selected">(Removed)</option>';
		foreach ($unit_clearance_arr as $key => $val) {
			if ($key == $info['unit_clearance']) {
				echo '<option value="'.$key.'" selected="selected">'.$val.'</option>';
			} else {
				echo '<option value="'.$key.'">'.$val.'</option>';
			}
		}
?>
					</select></td>
					<td><?php echo !empty($info['remarks']) ? html_encode($info['remarks']) : '' ?></td>
					<td class="btn-action">
						<button type="submit" name="update[<?php echo $info['id'] ?>]" class="btn btn-sm btn-primary">Restore</button>
					</td>
				</tr>
<?php
	}
?>
			</tbody>
		</table>
	</form>
<?php
} else {
?>
	<div class="alert alert-warning mt-3" role="alert">You have no items on this page.</div>
<?php
}
require SITE_ROOT.'footer.php';