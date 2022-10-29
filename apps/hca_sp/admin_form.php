<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$permission = ($User->is_admmod()) ? true : false;
$permission1 = ($User->is_admmod()) ? true : false;
if (!$permission)
	message($lang_common['No view']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

//Get cur form info
$query = array(
	'SELECT'	=> 'id, project_id, project_number, property_name, first_manager_id, first_manager, second_manager_id, second_manager, notice_for_admin, project_desc, remarks, mailed_time, admin_approved, admin_message, submited_time, submited_by',
	'FROM'		=> 'sm_special_projects_forms',
	'WHERE'		=> 'id='.$id,
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$form_info = $DBLayer->fetch_assoc($result);

if (empty($form_info))
	message('Sorry, this form does not exist or has been removed.');

if (isset($_POST['update']))
{
	$admin_approved = isset($_POST['admin_approved']) ? intval($_POST['admin_approved']) : 0;
	$admin_message = isset($_POST['admin_message']) ? swift_trim($_POST['admin_message']) : '';
	$time_now = time();
	
	if ($admin_message == '')
		$Core->add_error('You cannot submit an empty form. If you have no comment, write N/A.');
	
	if ($admin_approved == 1)
	{
		$query = array(
			'SELECT'	=> 'id, realname, email',
			'FROM'		=> 'users',
			'WHERE'		=> 'id='.$form_info['first_manager_id'].' OR id='.$form_info['second_manager_id'].' OR sm_special_projects_access=3'
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$project_managers = array();
		$managers_emails = '';
		while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
			$project_managers[$fetch_assoc['id']] = $fetch_assoc;
			if($managers_emails == '')
				$managers_emails = $fetch_assoc['email'];
			else
				$managers_emails .= $fetch_assoc['email'];
		}
		
		$query = array(
			'UPDATE'	=> 'sm_special_projects_records',
			'SET'		=> 'admin_approved=1',
			'WHERE'		=> 'id='.$form_info['project_id']
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
		//UPDATE STATUS, MESSAGE AND TIME
		$query = array(
			'UPDATE'	=> 'sm_special_projects_forms',
			'SET'		=> 'admin_approved=1, admin_message=\''.$DBLayer->escape($admin_message).'\', submited_time='.$time_now,
			'WHERE'		=> 'id='.$id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
		//CLEAR TEXT
		$new_project_intro = $DBLayer->escape('Hello. Cpecial project has been approved..');
		$property_name = $DBLayer->escape($form_info['property_name']);
		$project_managers = ($form_info['second_manager'] != '') ? $DBLayer->escape($form_info['first_manager'].' and '.$form_info['second_manager']) : $DBLayer->escape($form_info['first_manager']);
		$project_desc = ($form_info['project_desc'] != '') ? $DBLayer->escape($form_info['project_desc']) : $DBLayer->escape('N/A');
		$remarks = ($form_info['remarks'] != '') ? $DBLayer->escape($form_info['remarks']) : $DBLayer->escape('N/A');
		
		//FORMAT MESSAGE
		$mail_message = ''.$new_project_intro.' '."\n\n".
			'Property name: '.$property_name.' '."\n\n".
			'Project Managers: '.$project_managers.' '."\n\n".
			'Project Description: '.$project_desc.' '."\n\n".
			'Remarks: '.$remarks;
		
		foreach ($project_managers as $manager_info)
		{
			//Create msg for each managers
			$query = array(
				'INSERT'	=> 'user_name, user_id, sender_name, sender_id, project_id, action_time, message',
				'INTO'		=> 'sm_special_projects_actions',
				'VALUES'	=> 
						'\''.$DBLayer->escape($manager_info['realname']).'\',
						\''.$DBLayer->escape($manager_info['id']).'\',
						\''.$DBLayer->escape($User->get('realname')).'\',
						\''.$DBLayer->escape($User->get('id')).'\',
						\''.$DBLayer->escape($form_info['project_id']).'\',
						\''.$DBLayer->escape($time_now).'\',
						\''.$DBLayer->escape($mail_message).'\''
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}
		
		//SEND EMAIL
		$SwiftMailer = new SwiftMailer;
		$SwiftMailer->send($managers_emails, 'Special Project Approved', $mail_message);
		
		// Add flash message
		$flash_message = 'The form #'.$id.' has been submitted successfully.';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('sm_special_projects_admin_form', $id), $flash_message);
	}
	else
		$Core->add_error('You must mark the checkbox before submitting this form.');
}

// Setup the form
$page_param['fld_count'] = $page_param['group_count'] = $page_param['item_count'] = 0;

$Core->set_page_title('Admin form');
$Core->set_page_id('sm_special_projects_admin_form', 'hca_sp');
require SITE_ROOT.'header.php';

if ($permission1 && $form_info['admin_approved'] == 0)
{
?>

<style>
.main-subhead .hn span{color: red;font-weight: bold;}
</style>

<div class="main-content main-frm">
	<div id="admin-alerts" class="ct-set warn-set">
		<div class="ct-box warn-box">
			<h6 class="ct-legend hn warn"><span>Information:</span></h6>
			<p>Please double check information bellow, approve this form and leave your comment.</p>
		</div>
	</div>
	<form method="post" accept-charset="utf-8" action="">
		<div class="hidden">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		</div>
		<fieldset class="frm-group group1">
			<div class="ct-set group-item<?php echo ++$page_param['item_count'] ?>">
				<div class="ct-box">
					<h6 class="ct-legend hn"><span>Property</span></h6>
					<p><span><strong><?php echo html_encode($form_info['property_name']) ?></strong></span></p>
				</div>
			</div>
			<div class="ct-set group-item<?php echo ++$page_param['item_count'] ?>">
				<div class="ct-box">
					<h6 class="ct-legend hn"><span>Project Managers</span></h6>
					<p><span><strong><?php echo html_encode($form_info['first_manager']) ?></strong></span></p>
<?php if ($form_info['second_manager_id'] > 0) { ?>
					<p><span><strong><?php echo html_encode($form_info['second_manager']) ?></strong></span></p>
<?php } ?>
				</div>
			</div>
			<div class="ct-set group-item<?php echo ++$page_param['item_count'] ?>">
				<div class="ct-box warn-box">
					<h6 class="ct-legend hn"><span>Project Description</span></h6>
					<p><span><?php echo ($form_info['project_desc'] != '') ? html_encode($form_info['project_desc']) : '' ?></span></p>
				</div>
			</div>
			<div class="ct-set group-item<?php echo ++$page_param['item_count'] ?>">
				<div class="ct-box warn-box">
					<h6 class="ct-legend hn"><span>Remarks</span></h6>
					<p><span><?php echo ($form_info['remarks'] != '') ? html_encode($form_info['remarks']) : '' ?></span></p>
				</div>
			</div>
			<div class="ct-set group-item<?php echo ++$page_param['item_count'] ?>">
				<div class="ct-box warn-box">
					<h6 class="ct-legend hn"><span>Notice for Admin</span></h6>
					<p><span><?php echo format_time($form_info['mailed_time']).': '.$form_info['notice_for_admin'] ?></span></p>
				</div>
			</div>
			<div class="sf-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="sf-box checkbox">
					<span class="fld-input"><input type="checkbox" name="admin_approved" value="1" required></span>
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span style="color:red;">Admin action required</span>You must check this checkbox to confirm the action.</label>
				</div>
			</div>
			<div class="txt-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="txt-box textarea">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span style="color:red;"><strong>Admin comment required</strong></span><small>Please leave your message.</small></label>
					<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $page_param['fld_count'] ?>" name="admin_message" rows="3" cols="55" required placeholder="Enter text message here and submit this form"></textarea></span></div>
				</div>
			</div>
		</fieldset>
		<div class="frm-buttons">
			<span class="submit primary"><input type="submit" name="update" value="Submit" /></span>
		</div>
	</form>
</div>
<?php
}
else if ($form_info['admin_approved'] == 1)
{
?>

<style>
.main-subhead .hn span{
    color: green;
    font-weight: bold;
}
</style>

<div class="main-content main-frm">
	<div id="admin-alerts" class="ct-set warn-set">
		<div class="ct-box warn-box">
			<h6 class="ct-legend hn warn"><span>Information:</span></h6>
			<p>This form has already been completed and submitted. See details below.</p>
		</div>
	</div>
	<fieldset class="frm-group group1">
		<div class="ct-set group-item3">
			<div class="ct-box">
				<h6 class="ct-legend hn"><span>Property</span></h6>
				<p><span><strong><?php echo html_encode($form_info['property_name']) ?></strong></span></p>
			</div>
		</div>
		<div class="ct-set group-item<?php echo ++$page_param['item_count'] ?>">
			<div class="ct-box">
				<h6 class="ct-legend hn"><span>Project Managers</span></h6>
				<p><span><strong><?php echo html_encode($form_info['first_manager']) ?></strong></span></p>
<?php if ($form_info['second_manager_id'] > 0) { ?>
				<p><span><strong><?php echo html_encode($form_info['second_manager']) ?></strong></span></p>
<?php } ?>
			</div>
		</div>
		<div class="ct-set group-item<?php echo ++$page_param['item_count'] ?>">
			<div class="ct-box warn-box">
				<h6 class="ct-legend hn"><span>Project Description</span></h6>
				<p><span><?php echo ($form_info['project_desc'] != '') ? html_encode($form_info['project_desc']) : 'N/A' ?></span></p>
			</div>
		</div>
		<div class="ct-set group-item<?php echo ++$page_param['item_count'] ?>">
			<div class="ct-box warn-box">
				<h6 class="ct-legend hn"><span>Remarks</span></h6>
				<p><span><?php echo ($form_info['remarks'] != '') ? html_encode($form_info['remarks']) : 'N/A' ?></span></p>
			</div>
		</div>
		<div class="ct-set group-item<?php echo ++$page_param['item_count'] ?>">
			<div class="ct-box warn-box">
				<h6 class="ct-legend hn"><span>Notice for Admin</span></h6>
				<p><span><?php echo format_time($form_info['mailed_time']).': '.$form_info['notice_for_admin'] ?></span></p>
			</div>
		</div>
		<div class="ct-set group-item3">
			<div class="ct-box">
				<h6 class="ct-legend hn"><span>Admin approved</span></h6>
				<p><span><strong><?php echo ($form_info['admin_approved'] == 1) ? 'YES' : 'NO' ?></strong></span></p>
			</div>
		</div>
		<div class="ct-set group-item3">
			<div class="ct-box warn-box">
				<h6 class="ct-legend hn"><span>Admin comment</span></h6>
				<p><span><?php echo format_time($form_info['submited_time']).': '.$form_info['admin_message'] ?></span></p>
			</div>
		</div>
	</fieldset>
</div>
<?php
}
require SITE_ROOT.'footer.php';