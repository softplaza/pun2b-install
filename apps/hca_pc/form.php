<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if ($User->get('g_read_board') == '0')
	message($lang_common['No view']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$hash = isset($_GET['hash']) ? swift_trim($_GET['hash']) : '';

if ($id < 1 || $hash == '')
	message('Sorry, you came to the wrong link.');

//Get cur form info
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
	'WHERE'		=> 'f.id='.$id.' AND f.link_hash=\''.$DBLayer->escape($hash).'\'',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$form_info = $DBLayer->fetch_assoc($result);

if (empty($form_info))
	message('Sorry, this form does not exist or has been removed.');

//Get all messages
$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'sm_pest_control_forms',
	'WHERE'		=> 'project_id='.$form_info['project_id'],
	'ORDER BY'	=> 'mailed_time, manager_time'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$msg_info = array();
while($fetch_assoc = $DBLayer->fetch_assoc($result)){
	$msg_info[] = $fetch_assoc;
}

if (isset($_POST['update']))
{
	$manager_check = isset($_POST['manager_check']) ? intval($_POST['manager_check']) : 0;
	$manager_message = swift_trim($_POST['manager_message']);
	$time_now = time();
	
	if ($manager_check == 0)
		$Core->add_error('You must mark the checkbox before submitting this form.');
	if ($manager_message == '')
		$Core->add_error('You cannot submit an empty form. If you have no comment, write N/A.');
	
	if (empty($Core->errors))
	{
		$query = array(
			'SELECT'	=> 'id, realname, email, sm_pc_notify_by_email',
			'FROM'		=> 'users',
			'WHERE'		=> 'sm_pc_access > 0'
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$fetch_users = $DBLayer->fetch_assoc($result);
		
		$query = array(
			'UPDATE'	=> 'sm_pest_control_records',
			'SET'		=> 'manager_check=1, manager_check_time='.$time_now,
			'WHERE'		=> 'last_form_id='.$id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
		//UPDATE STATUS, MESSAGE AND TIME
		$query = array(
			'UPDATE'	=> 'sm_pest_control_forms',
			'SET'		=> 'manager_check=1, manager_message=\''.$DBLayer->escape($manager_message).'\', manager_time='.$time_now,
			'WHERE'		=> 'id='.$id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
		//UPDATE FORM FOR INSPECTOR
		$query = array(
			'UPDATE'	=> 'sm_pest_control_forms',
			'SET'		=> 'manager_check=1, submited_status=1, submited_time='.$time_now,
			'WHERE'		=> 'id!='.$id.' AND project_id='.$form_info['project_id']
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
		$mail_message = $DBLayer->escape('Your project has been approved from manager of property. Please follow this link bellow and confirm the project as comleted.')."\n\n";
		$mail_message .= 'Follow this link '.$DBLayer->escape($URL->link('sm_pc_forms', 'submitted'))."\n\n";
		$mail_message .= 'Property name: '.$DBLayer->escape($form_info['pro_name'])."\n\n";
		$mail_message .= 'Unit number: '.($form_info['unit'] != '' ? $DBLayer->escape($form_info['unit']) : $DBLayer->escape('N/A'))."\n\n";
		$mail_message .= 'Location: '.$DBLayer->escape($form_info['location'])."\n\n";
		$mail_message .= 'Pest problem: '.$DBLayer->escape($form_info['pest_problem'])."\n\n";
		$mail_message .= 'Remarks: '.$DBLayer->escape($form_info['remarks'])."\n\n";
		$mail_message .= 'Message from Manager: '.($manager_message != '' ? $DBLayer->escape($manager_message) : $DBLayer->escape('N/A'))."\n\n";
		
		//SEND EMAIL
		if ($fetch_users['sm_pc_notify_by_email'] == 1)
		{
			$SwiftMailer = new SwiftMailer;
			$SwiftMailer->send($fetch_users['email'], 'HCA: Pest Control Project', $mail_message);
		}
		
		// Add flash message
		$flash_message = 'The form has been submitted successfully.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

// Setup the form
$page_param['fld_count'] = $page_param['group_count'] = $page_param['item_count'] = 0;

if ($form_info['manager_check'] == 0)
{
	$Core->set_page_title('Pest Control Form');
	$Core->set_page_id('sm_pest_control_manager', 'hca_pc');
	require SITE_ROOT.'header.php';
?>

<style>
.main-subhead .hn span{color: red;font-weight: bold;}
</style>

<div class="main-content main-frm">
	<div id="admin-alerts" class="ct-set warn-set">
		<div class="ct-box warn-box">
			<h6 class="ct-legend hn warn"><span>Information:</span></h6>
			<p>Please double check information bellow, change status if job completed and leave your comment.</p>
		</div>
	</div>
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<fieldset class="frm-group group1">
			<div class="ct-set group-item3">
				<div class="ct-box">
					<h6 class="ct-legend hn"><span>Property</span></h6>
					<p><span><strong><?php echo html_encode($form_info['pro_name']) ?></strong></span></p>
				</div>
			</div>
			<div class="ct-set group-item3">
				<div class="ct-box">
					<h6 class="ct-legend hn"><span>Unit #</span></h6>
					<p><span><strong><?php echo html_encode($form_info['unit']) ?></strong></span></p>
				</div>
			</div>
			<div class="ct-set group-item3">
				<div class="ct-box">
					<h6 class="ct-legend hn"><span>Location</span></h6>
					<p><span><strong><?php echo html_encode($form_info['location']) ?></strong></span></p>
				</div>
			</div>
			<div class="ct-set group-item3">
				<div class="ct-box">
					<h6 class="ct-legend hn"><span>Pest Control problem</span></h6>
					<p><span><strong><?php echo html_encode($form_info['pest_problem']) ?></strong></span></p>
				</div>
			</div>
			<div class="ct-set group-item3">
				<div class="ct-box warn-box">
					<h6 class="ct-legend hn"><span>Notice for manager</span></h6>
					<p><span><?php echo format_time($form_info['mailed_time']).': '.$form_info['notice_for_manager'] ?></span></p>
				</div>
			</div>
			<div class="sf-set set4">
				<div class="sf-box checkbox">
					<span class="fld-input"><input type="checkbox" name="manager_check" value="1" required></span>
					<label for="fld7"><span style="color:red;">Manager action required</span>You must check this checkbox to confirm the action.</label>
				</div>
			</div>
			<div class="txt-set set<?php echo ++$page_param['item_count'] ?>">
				<div class="txt-box textarea">
					<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span style="color:red;"><strong>Manager comment required</strong></span><small>Please leave your message.</small></label>
					<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $page_param['fld_count'] ?>" name="manager_message" rows="3" cols="55" required placeholder="Enter text message here and submit this form"></textarea></span></div>
				</div>
			</div>
		</fieldset>
		<div class="frm-buttons">
			<span class="submit primary"><input type="submit" name="update" value="Submit" /></span>
		</div>
	</form>
</div>
	
<?php
	require SITE_ROOT.'footer.php';
}
else
{
	$Core->set_page_title('The form was confirmed by the manager on '.format_time($form_info['manager_time']));
	$Core->set_page_id('sm_pest_control_manager', 'hca_pc');
	require SITE_ROOT.'header.php';
?>

<style>
.main-subhead .hn span{color: green;font-weight: bold;}
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
				<p><span><strong><?php echo html_encode($form_info['pro_name']) ?></strong></span></p>
			</div>
		</div>
		<div class="ct-set group-item3">
			<div class="ct-box">
				<h6 class="ct-legend hn"><span>Unit #</span></h6>
				<p><span><strong><?php echo html_encode($form_info['unit']) ?></strong></span></p>
			</div>
		</div>
		<div class="ct-set group-item3">
			<div class="ct-box">
				<h6 class="ct-legend hn"><span>Location</span></h6>
				<p><span><strong><?php echo html_encode($form_info['location']) ?></strong></span></p>
			</div>
		</div>
		<div class="ct-set group-item3">
			<div class="ct-box">
				<h6 class="ct-legend hn"><span>Pest Control problem</span></h6>
				<p><span><strong><?php echo html_encode($form_info['pest_problem']) ?></strong></span></p>
			</div>
		</div>
		<div class="ct-set group-item3">
			<div class="ct-box warn-box">
				<h6 class="ct-legend hn"><span>Notices for manager</span></h6>
<?php
	foreach($msg_info as $cur_info){
		echo '<p><span>'.format_time($cur_info['mailed_time']).': '.$cur_info['notice_for_manager'].'</span></p>';
	}
?>
			</div>
		</div>
		<div class="ct-set group-item3">
			<div class="ct-box">
				<h6 class="ct-legend hn"><span>Manager approved</span></h6>
				<p><span><strong><?php echo ($form_info['manager_check'] == 1) ? 'YES' : 'NO' ?></strong></span></p>
			</div>
		</div>
		<div class="ct-set group-item3">
			<div class="ct-box warn-box">
				<h6 class="ct-legend hn"><span>Manager comment</span></h6>
<?php
	foreach($msg_info as $cur_info) {
		if ($cur_info['manager_check'] == 1 && !empty($cur_info['manager_message']))
			echo '<p><span>'.format_time($cur_info['manager_time']).': '.$cur_info['manager_message'].'</span></p>';
	}
?>
			</div>
		</div>
	</fieldset>
</div>
<?php
	require SITE_ROOT.'footer.php';
}