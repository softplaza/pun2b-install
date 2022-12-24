<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if ($User->get('g_read_board') == '0')
	message($lang_common['No view']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$hash = isset($_GET['hash']) ? swift_trim($_GET['hash']) : '';

if ($id < 1 || $hash == '')
	message('Sorry, you came to the wrong link.');

$HcaMi = new HcaMi;

//Get cur form info
$query = array(
	'SELECT'	=> 'f.*, pj.unit_number, pj.location, pj.leak_type, pj.symptom_type, pj.symptoms, pj.mois_source, pj.action, pj.remarks, p.pro_name, u1.realname AS project_manager1',
	'FROM'		=> 'hca_5840_forms AS f',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'hca_5840_projects AS pj',
			'ON'			=> 'pj.id=f.project_id'
		],
		[
			'INNER JOIN'	=> 'sm_property_db AS p',
			'ON'			=> 'p.id=pj.property_id'
		],
		[
			'LEFT JOIN'		=> 'users AS u1',
			'ON'			=> 'u1.id=pj.performed_uid'
		],
	],
	'WHERE'		=> 'f.id='.$id.' AND f.link_hash=\''.$DBLayer->escape($hash).'\'',
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
		$flash_message = 'Form #'.$id.' has been submited by manager of property';
		$HcaMi->addAction($project_info['id'], $flash_message);

		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
	else
		$Core->add_error('You must mark the checkbox before submitting this form.');
}

// Setup the form
$page_param['fld_count'] = $page_param['group_count'] = $page_param['item_count'] = 0;

$Core->set_page_id('hca_mi_form', 'hca_mi');
require SITE_ROOT.'header.php';
?>

<div class="card-header">
	<h6 class="card-title mb-0">Moisture Inspection Form</h6>
</div>
<div class="container">

<?php if ($form_info['submited_time'] == 0): ?>
	<div class="alert alert-warning my-3" role="alert">
		<p class="fw-bold">Information:</p>
		<p>Please submit this form as confirmation.</p>
	</div>
<?php else: ?>
	<div class="alert alert-success my-3" role="alert">
		<p class="fw-bold">Information:</p>
		<p>This form has already been submitted.</p>
	</div>
<?php endif; ?>

	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
		<div class="card-body border">
			<div class="row ">
				<div class="col-sm-6">
					<div class="mb-1">
						<span>Property:</span>
						<span class="fw-bold"><?php echo html_encode($form_info['pro_name']) ?></span>
					</div>
					<div class="mb-1">
						<span>Unit #</span>
						<span class="fw-bold"><?php echo html_encode($form_info['unit_number']) ?></span>
					</div>
					<div class="mb-1">
						<span>Location:</span>
						<span class="fw-bold"><?php echo html_encode($form_info['location']) ?></span>
					</div>
					<div class="mb-1">
						<span>Project Manager:</span>
						<span class="fw-bold"><?php echo html_encode($form_info['project_manager1']) ?></span>
					</div>

					<div class="alert alert-info my-3" role="alert">
						<p class="fw-bold">Notice for manager:</p>
<?php if ($form_info['submited_time'] == 0): ?>
						<p><?php echo format_time($form_info['mailed_time'],1).': '.$form_info['msg_for_manager'] ?></p>
<?php
else:
foreach($msg_info as $cur_info){
	echo '<p><span>'.format_time($cur_info['mailed_time']).': '.$cur_info['msg_for_manager'].'</span></p>';
}
endif;
?>
					</div>

				<?php if ($form_info['submited_time'] == 0): ?>

					<div class="mb-3 form-check">
						<input class="form-check-input" type="checkbox" name="manager_check" value="1" id="fld_manager_check">
						<label class="form-check-label" for="fld_manager_check">Mark this checkbox to confirm the action.</label>
					</div>
					<div class="mb-3">
						<label class="form-label" for="fld_msg_from_manager">Comments</label>
						<textarea id="fld_msg_from_manager" name="msg_from_manager" class="form-control" placeholder="Required field" required><?php echo html_encode($form_info['msg_from_manager']) ?></textarea>
					</div>
					<div class="mb-3">
						<button type="submit" name="update" class="btn btn-primary">Submit</button>
					</div>

				<?php else: ?>

					<div class="mb-3">
						<span>Manager approved: </span>
						<span class="fw-bold"><?php echo ($form_info['submited_time'] > 0) ? 'YES' : 'NO' ?></span>
					</div>
					<div class="alert alert-info my-3" role="alert">
						<p class="fw-bold">Manager comments</p>
						<p><?php echo html_encode($form_info['msg_from_manager']) ?></p>
					</div>

				<?php endif; ?>

				</div>
			</div>
		</div>
	</form>
</div>

<?php
require SITE_ROOT.'footer.php';
