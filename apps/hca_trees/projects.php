<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_trees', 3)) ? true : false;
if(!$access)
	message($lang_common['No permission']);

$section = isset($_GET['section']) ? $_GET['section'] : 'active';
$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$search_by_job_status = isset($_GET['job_status']) ? intval($_GET['job_status']) : -1;

$search_query = [];
if ($search_by_property_id > 0)
	$search_query[] = 'tp.property_id='.$search_by_property_id;

if ($search_by_job_status == 0)
	$search_query[] = 'tp.job_status=0';
else if ($search_by_job_status == 1)
	$search_query[] = 'tp.job_status=1';
else if ($search_by_job_status == 2) 
	$search_query[] = 'tp.job_status=2';
else if ($search_by_job_status == 3)
	$search_query[] = 'tp.job_status=3';
else
	$search_query[] = 'tp.job_status > 0';

$query = array(
	'SELECT'	=> 'COUNT(tp.id)',
	'FROM'		=> 'hca_trees_projects AS tp'
);
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$query = array(
	'SELECT'	=> 'tp.*, p.pro_name, v.vendor_name',
	'FROM'		=> 'hca_trees_projects AS tp',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'sm_property_db AS p',
			'ON'			=> 'p.id=tp.property_id'
		),
		array(
			'LEFT JOIN'		=> 'sm_vendors AS v',
			'ON'			=> 'v.id=tp.vendor_id'
		),
	),
	'ORDER BY'	=> 'tp.job_status, p.pro_name',
	'LIMIT'		=> $PagesNavigator->limit(),
);
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$records_info = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result))
{
	$records_info[] = $fetch_assoc;
}
$PagesNavigator->num_items($records_info);

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'sm_property_db',
	'ORDER BY'	=> 'pro_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $row;
}

$Core->set_page_title('Projects');
$Core->set_page_id('hca_trees_projects', 'hca_trees');

require SITE_ROOT.'header.php';
?>

<nav class="navbar container-fluid search-box">
	<form method="get" accept-charset="utf-8" action="">
		<input type="hidden" name="section" value="<?php echo $section ?>"/>
		<div class="row">
			<div class="col">
				<select name="property_id" class="form-select form-select-sm">
					<option value="">All Properties</option>
<?php foreach ($property_info as $val){
		if ($search_by_property_id == $val['id'])
			echo '<option value="'.$val['id'].'" selected="selected">'.$val['pro_name'].'</option>';
		else
			echo '<option value="'.$val['id'].'">'.$val['pro_name'].'</option>';
} ?>
				</select>
			</div>
			<div class="col">
				<select name="job_status" class="form-select form-select-sm">
					<option value="-1">All Job Statuses</option>
<?php
$job_statuses = array(1 => 'In-Progress', 2 => 'On Hold', 3 => 'Completed', 0 => 'Removed');
foreach ($job_statuses as $key => $value)
{
	if ($search_by_job_status == $key)
		echo '<option value="'.$key.'" selected="selected">'.$value.'</option>';
	else
		echo '<option value="'.$key.'">'.$value.'</option>';
}
?>
				</select>
			</div>
			<div class="col">
				<button type="submit" class="btn btn-sm btn-outline-success">Search</button>
				<a href="<?php echo $URL->link('hca_trees_projects') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
			</div>
		</div>
	</form>
</nav>

<?php
if (!empty($records_info)) 
{
?>
<form method="post" accept-charset="utf-8" action="">
	<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	<table class="table table-striped table-bordered table-responsive">
		<thead>
			<tr>
				<th>Property/Unit#</th>
				<th>Location</th>
				<th>Project Description</th>
				<th>Date of Notice Posting</th>
				<th>Vendor Name</th>
				<th>PO Number</th>
				<th>Total Cost</th>
				<th>Start Date</th>
				<th>End Date</th>
				<th>Job Completion Inspection Date</th>
				<th>Remarks</th>
			</tr>
		</thead>
		<tbody>
<?php

	foreach ($records_info as $cur_info)
	{
		$page_param['td'] = [];
		$total_cost = is_numeric($cur_info['total_cost']) ? number_format($cur_info['total_cost'], 2, '.', '') : 0;
		$total_cost_alert = ($total_cost >= 5000) ? ' class="bg-warning text-danger fw-bold"' : '';
		$start_date_alert = sm_is_today($cur_info['start_date']) || sm_is_today($cur_info['end_date']) ? ' class="bg-warning text-danger fw-bold"' : '';

		if ($cur_info['job_status'] == 1)
			$job_status = '<span class="badge badge-primary">In Progress</span>';
		else if ($cur_info['job_status'] == 2)
			$job_status = '<span class="badge badge-secondary">On Hold</span>';
		else if ($cur_info['job_status'] == 3)
			$job_status = '<span class="badge badge-success">Completed</span>';
		else if ($cur_info['job_status'] == 0)
			$job_status = '<span class="badge badge-danger">Removed</span>';

		if ($User->checkAccess('hca_trees', 4))
		{
			$Core->add_dropdown_item('<a href="'.$URL->link('hca_trees_manage_project', $cur_info['id']).'"><i class="fas fa-edit"></i> Edit project</a>');
			$dropdown_menu = '<span class="float-end">'.$Core->get_dropdown_menu($cur_info['id']).'</span>';
		}
		else
			$dropdown_menu = '';
?>
			<tr id="row<?php echo $cur_info['id'] ?>">
				<td>
					<p><?php echo html_encode($cur_info['pro_name']) ?></p>
					<p><?php echo $job_status ?></p>
					<?php echo $dropdown_menu ?>
				</td>
				<td>
					<?php echo html_encode($cur_info['location']) ?>
				</td>
				<td><?php echo html_encode($cur_info['project_desc']) ?></td>
				<td><?php echo format_time($cur_info['noticed_date'], 1) ?></td>
				<td><?php echo html_encode($cur_info['vendor_name']) ?></td>
				<td><?php echo html_encode($cur_info['po_number']) ?></td>
				<td <?php echo $total_cost_alert ?>><?php echo $total_cost ?></td>
				<td <?php echo $start_date_alert ?>><?php echo format_time($cur_info['start_date'], 1) ?></td>
				<td><?php echo format_time($cur_info['end_date'], 1) ?></td>
				<td><?php echo format_time($cur_info['completion_date'], 1) ?></td>
				<td><?php echo html_encode($cur_info['remarks']) ?></td>
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
<div class="alert alert-warning my-3" role="alert">
	You have no items on this page or not found within your search criteria.
</div>
<?php
}
require SITE_ROOT.'footer.php';