<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'active';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$access = ($User->checkAccess('hca_pc', 2)) ? true : false;
if(!$access)
	message($lang_common['No permission']);

$unit_clearance = array(0 => 'NO', 1 => 'YES', 2 => 'ON HOLD', 3 => 'IN PROGRESS');

$search_by_property = isset($_GET['property']) ? swift_trim($_GET['property']) : '';
$search_by_unit = isset($_GET['unit']) ? swift_trim($_GET['unit']) : '';
$search_by_date_from = (isset($_GET['date_from']) && ($_GET['date_from'] != $_GET['date_to'])) ? strtotime($_GET['date_from']) : '';
$search_by_date_to = (isset($_GET['date_to']) && ($_GET['date_from'] != $_GET['date_to'])) ? strtotime($_GET['date_to']) : '';

if (isset($_POST['delete']))
{
	$project_id = intval(key($_POST['delete']));

	$query = array(
		'UPDATE'	=> 'sm_pest_control_records',
		'SET'		=> 'unit_clearance=5',
		'WHERE'		=> 'id='.$project_id
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);

	$mail_message = 'Warning!'."\n";
	$mail_message .= 'User '.$User->get('realname').' has deleted a project from active projects.'."\n";
	$mail_message .= 'Project ID: '. $project_id."\n\n";
	$mail_message .= 'You can restore this Project if necessary. To restore a project, go to Pest Control Projects, then the RESTORE tab, then select the project to restore.';

	$SwiftMailer = new SwiftMailer;
	$SwiftMailer->send($Config->get('o_mailing_list'), 'HCA: Pest Control Project Removing', $mail_message);

	// Add flash message
	$flash_message = 'Pest Control project #'.$project_id.' has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}
else if (isset($_POST['return']))
{
	$project_id = intval(key($_POST['return']));

	$query = array(
		'UPDATE'	=> 'sm_pest_control_records',
		'SET'		=> 'unit_clearance=2',
		'WHERE'		=> 'id='.$project_id
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	
	// Add flash message
	$flash_message = 'Pest Control project #'.$project_id.' has been recovered';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('sm_pest_control_active', $project_id.'#rid'.$project_id), $flash_message);
}

$query = array(
	'SELECT'	=> 'COUNT(id)',
	'FROM'		=> 'sm_pest_control_records',
	'WHERE'		=> 'unit_clearance=1'
);
// SEARCH BY SECTION //
if (!empty($search_by_property) && $search_by_property != 'ALL PROPERTIES') {
	$query['WHERE'] .= ' AND property=\''.$DBLayer->escape($search_by_property).'\'';
}
if (!empty($search_by_date_from) && $search_by_date_from > 0) {
	$query['WHERE'] .= ' AND start_date>\''.$DBLayer->escape($search_by_date_from).'\'';
}
if (!empty($search_by_date_to) && $search_by_date_to > 0) {
	$query['WHERE'] .= ' AND completion_date<\''.$DBLayer->escape($search_by_date_to).'\'';
}
if (!empty($search_by_unit)) {
	$search_by_unit2 = '%'.$search_by_unit.'%';
	$query['WHERE'] .= ' AND unit LIKE \''.$DBLayer->escape($search_by_unit2).'\'';
}
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
	'WHERE'		=> 'r.unit_clearance=1',
	'ORDER BY'	=> 'r.property, p.pro_name',
	'LIMIT'		=> $PagesNavigator->limit(),
);
// SEARCH BY SECTION //
if (!empty($search_by_property) && $search_by_property != 'ALL PROPERTIES') {
	$query['WHERE'] .= ' AND r.property=\''.$DBLayer->escape($search_by_property).'\'';
}
if (!empty($search_by_date_from) && $search_by_date_from > 0) {
	$query['WHERE'] .= ' AND r.start_date>\''.$DBLayer->escape($search_by_date_from).'\'';
}
if (!empty($search_by_date_to) && $search_by_date_to > 0) {
	$query['WHERE'] .= ' AND r.completion_date<\''.$DBLayer->escape($search_by_date_to).'\'';
}
if (!empty($search_by_unit)) {
	$search_by_unit2 = '%'.$search_by_unit.'%';
	$query['WHERE'] .= ' AND r.unit LIKE \''.$DBLayer->escape($search_by_unit2).'\'';
}
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

$Core->set_page_id('sm_pest_control_records', 'hca_pc');
require SITE_ROOT.'header.php';
?>
	
	<nav class="navbar container-fluid search-box">
		<form method="get" accept-charset="utf-8" action="">
			<div class="row">
				<div class="col pe-0">
					<select name="property" class="form-select-sm">
						<option value="">Select property</option>
<?php foreach ($property_info as $val){
			if($search_by_property == $val['pro_name'])
				echo '<option value="'.$val['pro_name'].'" selected="selected">'.$val['pro_name'].'</option>';
			else
				echo '<option value="'.$val['pro_name'].'">'.$val['pro_name'].'</option>';
} ?>
					</select>
				</div>
				<div class="col pe-0">
					<input name="unit" type="text" value="<?php echo $search_by_unit ?>" placeholder="Enter Unit #" class="form-control-sm"/>
				</div>
				<div class="col pe-0">
					<input type="month" name="date_from" value="<?php echo isset($_GET['date_from']) ? $_GET['date_from'] : '' ?>" class="form-control-sm"/>
				</div>
				<div class="col pe-0">
					<input type="month" name="date_to" value="<?php echo (isset($_GET['date_to'])) ? $_GET['date_to'] : format_time(time(), 1, 'Y-m', null, true) ?>" class="form-control-sm"/>

				</div>
				<div class="col pe-0">
					<button type="submit" class="btn btn-sm btn-outline-success">Search</button>
				</div>
			</div>
		</form>
	</nav>
<?php
	if (!empty($main_info)) 
	{
?>
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token($URL->link('sm_pest_control_records')) ?>" />
		<table class="table table-sm table-striped table-bordered">
			<thead class="thead-list">
				<tr>
					<th>Property Unit#</th>
					<th>Location</th>
					<th>Reported Date/By</th>
					<th>Pest Control Problem</th>
					<th>Pest Control Action</th>
					<th>Surrounding Units Inspection Date</th>
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
					<td><?php echo ($info['manager_check'] == 1) ? 'YES' : 'NO' ?></td>
					<td><?php echo !empty($info['completion_date']) ? date('m/d/Y', $info['completion_date']) : '' ?></td>
					<td><?php echo ($info['unit_clearance'] == 1) ? 'YES' : 'NO' ?></td>
					<td><?php echo !empty($info['remarks']) ? html_encode($info['remarks']) : '' ?></td>
					<td class="button">

						<span class="submit primary"><input type="submit" name="return[<?php echo $info['id'] ?>]" value="Return" onclick="return confirm('Are you sure you want to return this project to Active Projects?')" /></span>
						<span class="submit primary caution"><input type="submit" name="delete[<?php echo $info['id'] ?>]" value="Remove" onclick="return confirm('Are you sure you want to delete this project?')" /></span>

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
	<div class="alert alert-warning mt-3" role="alert">You have no items on this page or not found within your search criteria.</div>
<?php
	}
require SITE_ROOT.'footer.php';