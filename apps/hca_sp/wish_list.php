<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$action = isset($_GET['action']) ? swift_trim($_GET['action']) : '';

$permission = ($User->is_admmod()) ? true : false;
if (!$permission)
	message($lang_common['No permission']);

$query = array(
	'SELECT'	=> 'pj.*, pt.pro_name, u1.realname AS first_manager, u2.realname AS second_manager',
	'FROM'		=> 'sm_special_projects_records AS pj',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=pj.property_id'
		),
		array(
			'LEFT JOIN'		=> 'users AS u1',
			'ON'			=> 'u1.id=pj.project_manager_id'
		),
		array(
			'LEFT JOIN'		=> 'users AS u2',
			'ON'			=> 'u2.id=pj.second_manager_id'
		),
	),
	'WHERE'		=> 'pj.work_status=3',
	'ORDER BY'	=> 'pt.pro_name',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$projects_info = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$projects_info[$fetch_assoc['id']] = $fetch_assoc;
}

if (isset($_POST['update']))
{
	$id = intval(key($_POST['update']));
	//Get POST because it may be changed when sent
	$remarks = isset($_POST['remarks'][$id]) ? swift_trim($_POST['remarks'][$id]) : '';
	
	$project_desc = isset($projects_info[$id]) ? $projects_info[$id]['project_desc'] : '';
	$property_name = isset($projects_info[$id]) ? $projects_info[$id]['pro_name'] : '';
	$start_date = isset($projects_info[$id]) ? $projects_info[$id]['start_date'] : 0;
	$end_date = isset($projects_info[$id]) ? $projects_info[$id]['end_date'] : 0;
	
	$project_manager_id = isset($projects_info[$id]) ? $projects_info[$id]['project_manager_id'] : 0;
	$second_manager_id = isset($projects_info[$id]) ? $projects_info[$id]['second_manager_id'] : 0;
	$time_now = time();
	$project_number = date('Y', $time_now).'-'.$id;
	
	if (empty($Core->errors))
	{
		$query = array(
			'SELECT'	=> 'id, realname, email',
			'FROM'		=> 'users',
			'WHERE'		=> 'id='.$project_manager_id.' OR id='.$second_manager_id,
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$emails_array = array();
		while ($row = $DBLayer->fetch_assoc($result)) {
			if ($row['id'] != $User->get('id'))
				$emails_array[] = $row['email'];
		}
		
		$mail_list = !empty($emails_array) ? implode(',', $emails_array) : '';
		$mail_subject = 'Special Project Approved';
		$mail_message = 'Hello.'."\n\n";
		$mail_message .= 'Please follow up on this project. Your recommendation has been approved.'."\n\n";
		$mail_message .= 'Property name: '.$property_name."\n";
		$mail_message .= 'Start date: '.(($start_date > 0) ? format_time($start_date, 1) : 'N/A')."\n";
		$mail_message .= 'End date: '.(($end_date > 0) ? format_time($end_date, 1) : 'N/A')."\n";
		$mail_message .= 'Project description: '.($project_desc != '' ? html_encode($project_desc) : 'N/A')."\n";
		$mail_message .= 'Remarks: '.($remarks != '' ? html_encode($remarks) : 'N/A')."\n";
		
		if ($mail_list != '')
		{
			$SwiftMailer = new SwiftMailer;
			$SwiftMailer->send($mail_list, $mail_subject, $mail_message);
		}
		
		$query = array(
			'UPDATE'	=> 'sm_special_projects_records',
			'SET'		=> 'project_number=\''.$DBLayer->escape($project_number).'\', work_status=2, remarks=\''.$DBLayer->escape($remarks).'\'',
			'WHERE'		=> 'id='.$id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
		// Add flash message
		$flash_message = 'Special Project #'.$id.' has been approved';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('sm_special_projects_active', $id.'#pid'.$id), $flash_message);
	}
}

$Core->set_page_id('sm_special_projects_wish_list', 'hca_sp');
require SITE_ROOT.'header.php';
?>

<style>

</style>

<?php	
if (!empty($projects_info)) 
{
?>			
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<table class="table table-sm table-striped table-bordered">
			<thead>
				<tr>
					<th class="th1">Property</th>
					<th>Description</th>
					<th>Project manager</th>
					<th>Action date</th>
					<th>Start Date</th>
					<th>End Date</th>
					<th>Budget</th>
					<th>Cost</th>
					<th>Remarks</th>
					<th>Action</th>
				</tr>
			</thead>
			<tbody>
<?php
	foreach ($projects_info as $cur_info)
	{
		$second_manager = isset($cur_info['second_manager']) ? '<p>'.html_encode($cur_info['second_manager']).'</p>' : '';
?>
				<tr>
					<td class="td1">
						<p><?php echo html_encode($cur_info['pro_name']) ?></p>
						<p>(<?php echo ($cur_info['project_scale'] == 0 ? 'MINOR' : 'MAJOR') ?>)</p>
					</td>
					<td class="desc"><?php echo $cur_info['project_desc'] ?></td>
					<td>
						<p><?php echo html_encode($cur_info['first_manager']) ?></p>
						<p><?php echo $second_manager ?></p>
					</td>
					<td><?php echo format_time($cur_info['action_date'], 1) ?></td>
					<td><?php echo format_time($cur_info['start_date'], 1) ?></td>
					<td><?php echo format_time($cur_info['end_date'], 1) ?></td>
					<td class="budget">$<?php echo gen_number_format($cur_info['budget'], 2) ?></td>
					<td class="cost">$<?php echo gen_number_format($cur_info['cost'], 2) ?></td>
					<td class="remarks"><textarea type="text" name="remarks[<?php echo $cur_info['id'] ?>]" class="form-control"><?php echo html_encode($cur_info['remarks']) ?></textarea ></td>
					<td><span class="submit primary"><input type="submit" name="update[<?php echo $cur_info['id'] ?>]" value="Approve" onclick="return confirm('Are you sure you want to approve this project?')"/></span></td>
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