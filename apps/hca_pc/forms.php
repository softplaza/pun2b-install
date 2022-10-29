<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_pc', 4))
	message($lang_common['No permission']);

$action = isset($_GET['action']) ? swift_trim($_GET['action']) : 'submitted';
$errors = array();

if (isset($_POST['approve']))
{
	$id = intval(key($_POST['approve']));

	if ($id < 1)
		message($lang_common['Bad request']);

	$query = array(
		'UPDATE'	=> 'sm_pest_control_forms',
		'SET'		=> 'submited_status=1, submited_time='.time().', submited_by=\''.$DBLayer->escape($User->get('realname')).'\'',
		'WHERE'		=> 'id='.$id
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	
	// Add flash message
	$flash_message = 'Project #'.$id.' has been submitted by '.$User->get('realname');
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

$query = array(
	'SELECT'	=> 'COUNT(id)',
	'FROM'		=> 'sm_pest_control_forms',
);
if ($action == 'mailed')
	$query['WHERE'] = 'manager_check=0 AND submited_status=0';
else if ($action == 'confirmed')
	$query['WHERE'] = 'manager_check=1 AND submited_status=1';
else
	$query['WHERE'] = 'manager_check=1 AND submited_status=0';
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$query = array(
	'SELECT'	=> 'f.*, r.unit, r.location, r.pest_problem, p.pro_name',
	'FROM'		=> 'sm_pest_control_forms AS f',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'sm_pest_control_records AS r',
			'ON'			=> 'r.id=f.project_id'
		),
		array(
			'LEFT JOIN'		=> 'sm_property_db AS p',
			'ON'			=> 'p.id=r.property_id'
		),
	),
	'ORDER BY'	=> 'f.manager_time DESC',
	'LIMIT'		=> $PagesNavigator->limit(),
);
if ($action == 'mailed')
	$query['WHERE'] = 'f.manager_check=0 AND f.submited_status=0';
else if ($action == 'confirmed')
	$query['WHERE'] = 'f.manager_check=1 AND f.submited_status=1';
else
	$query['WHERE'] = 'f.manager_check=1 AND f.submited_status=0';
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$main_info[] = $fetch_assoc;
}
$PagesNavigator->num_items($main_info);

if ($action == 'mailed')
	$Core->set_page_id('sm_pc_forms_mailed', 'hca_pc');
else if ($action == 'confirmed')
	$Core->set_page_id('sm_pc_forms_confirmed', 'hca_pc');
else
	$Core->set_page_id('sm_pc_forms_submitted', 'hca_pc');

require SITE_ROOT.'header.php';
?>
	
<?php
if (!empty($main_info))
{
?>
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<table class="table table-sm table-striped table-bordered">
			<thead class="new-msgs">
				<tr>
					<th>Property</th>
					<th>Unit #</th>
					<th>Location</th>
					<th>Problem</th>
					<th>Notice for Manager</th>
					<th>Remarks</th>
					<th>Manager Comment</th>
					<th>Confirm</th>
				</tr>
			</thead>
			<tbody>
<?php
	$odd_even_class = 'odd';
	foreach ($main_info as $cur_info) 
	{
		if ($action == 'submitted')
			$btn_action = '<button type="submit" name="approve['.$cur_info['id'].']" class="btn btn-sm btn-success" onclick="return confirm(\'Are you sure you want to mark this project as completed?\')">Ok</button>';
		else if ($action == 'mailed')
			$btn_action = '<span class="badge bg-primary">Mailed</span>';
		else if ($action == 'confirmed')
			$btn_action = '<span class="badge bg-success">Completed</span> <p>'.format_time($cur_info['submited_time']).'</p>';
		else
			$btn_action = '';
		
		$form_link = ($User->is_admmod() || $action == 'submitted' || $action == 'confirmed') ? '<p><a href="'.$URL->link('sm_pest_control_form', array($cur_info['id'], $cur_info['link_hash'])).'" class="float-end" target="_blank">View details</a></p>' : '';
		
		$manager_action = ($cur_info['manager_time'] > 0) ? ('<span class="time">'.format_time($cur_info['manager_time']).': </span>&nbsp;&nbsp;'.html_encode($cur_info['manager_message'])) : 'Waiting for an answer...';
		
?>
				<tr>
					<td><a href="<?php echo $URL->link('sm_pest_control_active', $cur_info['project_id'].'#rid'.$cur_info['project_id']) ?>" target="_blank"><?php echo $cur_info['pro_name']; ?></a></td>
					<td><?php echo html_encode($cur_info['unit']); ?></td>
					<td><?php echo $cur_info['location']; ?></td>
					<td><?php echo $cur_info['pest_problem']; ?></td>
					<td>
						<span class="time"><?php echo format_time($cur_info['mailed_time']) ?>:</span>&nbsp;&nbsp;<?php echo html_encode($cur_info['notice_for_manager']); ?>
					</td>
					<td><?php echo html_encode($cur_info['remarks']) ?></td>
					<td>
						<p><?php echo $manager_action ?></p>
						<?php echo $form_link ?>
					</td>
					<td class="button">
						<?php echo $btn_action ?>
					</td>
				</tr>
<?php
	}

?>
			</tbody>
		</table>
	</form>
<?php
}
else
{
?>
	<div class="alert alert-warning mt-3" role="alert">You have no items on this page.</div>
<?php
}
require SITE_ROOT.'footer.php';