<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_mi', 3)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$query = array(
	'SELECT'	=> 'COUNT(id)',
	'FROM'		=> 'hca_5840_forms',
	'WHERE'		=> 'completed_time>0',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

//GET NEW UNCOMPLETED FORM FROM MANAGER
$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'hca_5840_forms',
	'WHERE'		=> 'completed_time>0',
	'ORDER BY'	=> 'completed_time DESC',
	'LIMIT'		=> $PagesNavigator->limit(),
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$forms_info = $projects_ids = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$forms_info[$row['id']] = $row;
	$projects_ids[] = $row['project_id'];
}
$PagesNavigator->num_items($forms_info);

$projects_info = array();
if (!empty($projects_ids))
{
	$query = array(
		'SELECT'	=> '*',
		'FROM'		=> 'hca_5840_projects',
		'WHERE'		=> 'id IN ('.implode(',', $projects_ids).')'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$projects_info = array();
	while ($row = $DBLayer->fetch_assoc($result)) {
		$projects_info[$row['id']] = $row;
	}
}

$Core->set_page_id('hca_5840_forms_confirmed', 'hca_5840');
require SITE_ROOT.'header.php';

if (!empty($forms_info))
{
?>
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<table class="table table-striped table-bordered">
			<thead>
				<tr>
					<th>Property Information</th>
					<th>Source of moisture</th>
					<th>Symptoms</th>
					<th>Action</th>
					<th>Remarks</th>
					<th>Notice for Manager</th>
					<th>Manager Comment</th>
					<th>Confirm</th>
				</tr>
			</thead>
			<tbody>
<?php
	foreach ($forms_info as $cur_info) 
	{
		$project_info = sm_get_data_by_id($projects_info, $cur_info['project_id']);
		if (!empty($project_info))
		{
?>
				<tr>
					<td>
						<p><a href="<?php echo $URL->link('hca_5840_projects', '#pid'.$cur_info['project_id']) ?>"><?php echo $project_info['property_name'] ?></a></p>
						<p><?php echo html_encode($project_info['unit_number']) ?></p>
						<p><?php echo html_encode($project_info['location']) ?></p>
					</td>
					
					<td><?php echo $project_info['mois_source'] ?></td>
					<td><?php echo $project_info['symptoms'] ?></td>
					<td><?php echo $project_info['action'] ?></td>
					<td><?php echo $project_info['remarks'] ?></td>
					<td>
						<span class="time"><?php echo format_time($cur_info['mailed_time']) ?>:</span>
						<p><?php echo $cur_info['msg_for_manager'] ?></p>
					</td>
					<td>
						<span class="time"><?php echo format_time($cur_info['submited_time']) ?>:</span>
						<p><?php echo $cur_info['msg_from_manager'] ?></p>
					</td>
					
					<td>
						<p style="color:green">Completed: <?php echo format_time($cur_info['completed_time']) ?></p>
					</td>
				</tr>
<?php
		}
	}
?>
			</tbody>
		</table>
	</form>
<?php
} else {
?>
	<div class="alert alert-warning my-3" role="alert">You have no items on this page or not found within your search criteria.</div>
<?php
}
require SITE_ROOT.'footer.php';