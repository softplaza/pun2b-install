<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_mi'))
	message($lang_common['No permission']);

$HcaMi = new HcaMi;

if (isset($_POST['approve']))
{
	$id = intval(key($_POST['approve']));

	if ($id < 1)
		message($lang_common['Bad request']);

	$query = array(
		'UPDATE'	=> 'hca_5840_forms',
		'SET'		=> 'completed_time='.time(),
		'WHERE'		=> 'id='.$id
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	
	$flash_message = 'Form has been confirmed by manager of project: '.$User->get('realname');
	//$HcaMi->addAction($id, $flash_message); // required project id

	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

$query = array(
	'SELECT'	=> 'COUNT(id)',
	'FROM'		=> 'hca_5840_forms',
	'WHERE'		=> 'submited_time>0 AND completed_time=0',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

//GET NEW UNCOMPLETED FORM FROM MANAGER
$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'hca_5840_forms',
	'WHERE'		=> 'submited_time>0 AND completed_time=0',
	'ORDER BY'	=> 'submited_time DESC',
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

//$Core->set_page_title('Submitted forms');
$Core->set_page_id('hca_mi_forms_submitted', 'hca_mi');
require SITE_ROOT.'header.php';
?>

<div class="card-header">
	<h6 class="card-title mb-0">Completed forms of Property Managers</h6>
</div>

<?php
if (!empty($forms_info))
{
?>
	<form method="post" accept-charset="utf-8" action="">
		<div class="hidden">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
		</div>
		<table class="table table-striped table-bordered">
			<thead class="new-msgs">
				<tr>
					<th class="info">Property Information</th>
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
					<td class="info">
						<p><a href="<?php echo $URL->link('hca_5840_projects', '#pid'.$cur_info['project_id']) ?>"><?php echo $project_info['property_name'] ?></a></p>
						<p><?php echo html_encode($project_info['unit_number']) ?></p>
						<p><?php echo html_encode($project_info['location']) ?></p>
					</td>
					<td class="comment"><?php echo $project_info['mois_source'] ?></td>
					<td class="comment"><?php echo $project_info['symptoms'] ?></td>
					<td class="comment"><?php echo $project_info['action'] ?></td>
					<td class="comment"><?php echo $project_info['remarks'] ?></td>
					<td class="comment">
						<span class="time"><?php echo format_time($cur_info['mailed_time']) ?>:</span>
						<p><?php echo $cur_info['msg_for_manager'] ?></p>
					</td>
					<td class="comment">
						<span class="time"><?php echo format_time($cur_info['submited_time']) ?>:</span>
						<p><?php echo $cur_info['msg_from_manager'] ?></p>
					</td>
					<td class="button">
						<span class="submit primary"><input type="submit" name="approve[<?php echo $cur_info['id']; ?>]" value="Ok" onclick="return confirm('Are you sure you want to mark this message as confirmed?')"></span>
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