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
	'SELECT'	=> '*',
	'FROM'		=> 'hca_5840_forms',
	'WHERE'		=> 'id='.$id.' AND link_hash=\''.$DBLayer->escape($hash).'\'',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$form_info = $DBLayer->fetch_assoc($result);

if (empty($form_info))
	message('Sorry, this form does not exist or has been removed.');

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'hca_5840_projects',
	'WHERE'		=> 'id='.$form_info['project_id'],
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$project_info = $DBLayer->fetch_assoc($result);

//Get all messages
$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'hca_5840_forms',
	'WHERE'		=> 'project_id='.$form_info['project_id'],
//	'ORDER BY'	=> 'mailed_time'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$msg_info = array();
while($fetch_assoc = $DBLayer->fetch_assoc($result)){
	$msg_info[] = $fetch_assoc;
}

if (isset($_POST['update']))
{
	$manager_check = isset($_POST['manager_check']) ? intval($_POST['manager_check']) : 0;
	$msg_from_manager = isset($_POST['msg_from_manager']) ? swift_trim($_POST['msg_from_manager']) : '';
	$time_now = time();
	
	if ($msg_from_manager == '')
		$Core->add_error('You cannot submit an empty form. If you have no comment, write N/A.');
	
	if ($manager_check == 1)
	{
		$query = array(
			'UPDATE'	=> 'hca_5840_forms',
			'SET'		=> 'msg_from_manager=\''.$DBLayer->escape($msg_from_manager).'\', submited_time='.$time_now,
			'WHERE'		=> 'id='.$id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
		//UPDATE FORM FOR INSPECTOR
		$query = array(
			'UPDATE'	=> 'hca_5840_forms',
			'SET'		=> 'submited_time='.$time_now.', completed_time='.$time_now,
			'WHERE'		=> 'id!='.$id.' AND project_id='.$form_info['project_id']
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
/*
		//CLEAR TEXT
		$new_project_intro = $DBLayer->escape('Your project has been approved from manager of property. Please follow this link bellow and confirm the project as comleted.');
		$link_mail = $DBLayer->escape($URL->link('sm_pest_control_actions'));
		$property_name_mail = $DBLayer->escape($form_info['property']);
		$unit_mail = ($form_info['unit'] != '') ? $DBLayer->escape($form_info['unit']) : $DBLayer->escape('N/A');
		$location_mail = $DBLayer->escape($form_info['location']);
		$pest_problem_mail = $DBLayer->escape($form_info['pest_problem']);
		$remarks_mail = $DBLayer->escape($form_info['remarks']);
		$manager_message_mail = ($manager_message != '') ? $DBLayer->escape($manager_message) : $DBLayer->escape('N/A');
		
		//FORMAT MESSAGE
		$flash_message = ''.$new_project_intro.' '."\n\n".
			'Follow this link '.$link_mail.' '."\n\n".
			'Property name: '.$property_name_mail.' '."\n\n".
			'Unit number: '.$unit_mail.' '."\n\n".
			'Location: '.$location_mail.' '."\n\n".
			'Pest problem: '.$pest_problem_mail.' '."\n\n".
			'Remarks: '.$remarks_mail.' '."\n\n".
			'Message from Manager: '.$manager_message_mail;
*/
		$flash_message = 'Form has been submited by manager of property';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
	else
		$Core->add_error('You must mark the checkbox before submitting this form.');
}

// Setup the form
$page_param['fld_count'] = $page_param['group_count'] = $page_param['item_count'] = 0;

$Core->set_page_id('hca_5840_form', 'hca_5840');
require SITE_ROOT.'header.php';

if ($form_info['submited_time'] == 0)
{
?>
<style>
.main-subhead .hn span{color: red;font-weight: bold;}
</style>

	<div class="main-subhead">
		<h2 class="hn"><span class="a-project">From: Moisture Inspection</span></h2>
	</div>

	<div class="main-content main-frm">
		
		<?php $Core->get_messages() ?>
		
		<div id="admin-alerts" class="ct-set warn-set">
			<div class="ct-box warn-box">
				<h6 class="ct-legend hn warn"><span>Information:</span></h6>
				<p>Please double check information bellow and submit this form.</p>
			</div>
		</div>
		
		<form method="post" accept-charset="utf-8" action="">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			</div>
		
			<fieldset class="frm-group group1">
				
				<div class="ct-set group-item3">
					<div class="ct-box">
						<h6 class="ct-legend hn"><span>Property</span></h6>
						<p><span><strong><?php echo html_encode($project_info['property_name']) ?></strong></span></p>
					</div>
				</div>
				
				<div class="ct-set group-item3">
					<div class="ct-box">
						<h6 class="ct-legend hn"><span>Unit #</span></h6>
						<p><span><strong><?php echo html_encode($project_info['unit_number']) ?></strong></span></p>
					</div>
				</div>
				
				<div class="ct-set group-item3">
					<div class="ct-box">
						<h6 class="ct-legend hn"><span>Location</span></h6>
						<p><span><strong><?php echo html_encode($project_info['location']) ?></strong></span></p>
					</div>
				</div>
				
				<div class="ct-set group-item3">
					<div class="ct-box warn-box">
						<h6 class="ct-legend hn"><span>Notice for manager</span></h6>
						<p><span><?php echo format_time($form_info['mailed_time'],1).': '.$form_info['msg_for_manager'] ?></span></p>
<?php
/*
//SHOWS HISTORY FOR MANAGER
foreach($msg_info as $cur_info){
	echo '<p><span>'.format_time($cur_info['mailed_time'],0,null,null,true).': '.$cur_info['notice_for_manager'].'</span></p>';
	if($cur_info['manager_check'] == 1)
		echo '<p><span>'.format_time($cur_info['manager_time'],0,null,null,true).': '.$cur_info['manager_message'].'</span></p>';
*/
//	if($cur_info['manager_check'] == 1 && !empty($cur_info['manager_message']))
//		echo '<p><span>'.format_time($cur_info['manager_time'],0,null,null,true).': '.$cur_info['manager_message'].'</span></p>';
		
//}

?>
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
						<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $page_param['fld_count'] ?>" name="msg_from_manager" rows="3" cols="55" required placeholder="Enter text message here and submit this form"></textarea></span></div>
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
?>
<style>
.main-subhead .hn span{
    color: green;
    font-weight: bold;
}
</style>

	<div class="main-subhead">
		<h2 class="hn"><span class="a-project">The form was confirmed by the manager on <?php echo format_time($form_info['submited_time'], 1) ?></span></h2>
	</div>
	
	<div class="main-content main-frm">
		
		<?php $Core->get_messages() ?>
		
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
					<p><span><strong><?php echo html_encode($project_info['property_name']) ?></strong></span></p>
				</div>
			</div>
			
			<div class="ct-set group-item3">
				<div class="ct-box">
					<h6 class="ct-legend hn"><span>Unit #</span></h6>
					<p><span><strong><?php echo html_encode($project_info['unit_number']) ?></strong></span></p>
				</div>
			</div>
			
			<div class="ct-set group-item3">
				<div class="ct-box">
					<h6 class="ct-legend hn"><span>Location</span></h6>
					<p><span><strong><?php echo html_encode($project_info['location']) ?></strong></span></p>
				</div>
			</div>
			
			<div class="ct-set group-item3">
				<div class="ct-box warn-box">
					<h6 class="ct-legend hn"><span>Notices for manager</span></h6>
<?php
foreach($msg_info as $cur_info){
	echo '<p><span>'.format_time($cur_info['mailed_time']).': '.$cur_info['msg_for_manager'].'</span></p>';
}
?>
				</div>
			</div>
			
			<div class="ct-set group-item3">
				<div class="ct-box">
					<h6 class="ct-legend hn"><span>Manager approved</span></h6>
					<p><span><strong><?php echo ($form_info['submited_time'] > 0) ? 'YES' : 'NO' ?></strong></span></p>
				</div>
			</div>
			
			<div class="ct-set group-item3">
				<div class="ct-box warn-box">
					<h6 class="ct-legend hn"><span>Manager comments</span></h6>
<?php
	foreach($msg_info as $cur_info) {
		if ($cur_info['submited_time'] > 0 && !empty($cur_info['msg_from_manager']))
			echo '<p><span>'.format_time($cur_info['submited_time']).': '.$cur_info['msg_from_manager'].'</span></p>';
	}
?>
				</div>
			</div>
			
		</fieldset>
		
	</div>
<?php
	require SITE_ROOT.'footer.php';
}