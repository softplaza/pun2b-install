<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_sp', 12)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1) 
	message($lang_common['Bad request']);

//Get cur project info
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
	'WHERE'		=> 'pj.id='.$id,
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$project_info = $DBLayer->fetch_assoc($result);

if (empty($project_info))
	message('Sorry, this Special Project does not exist or has been removed.');

$query = array(
	'SELECT'	=> 'u.id, u.email, u.realname',
	'FROM'		=> 'users AS u',
	'WHERE'		=> 'u.sm_special_projects_access > 0',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users_info = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result))
	$users_info[$fetch_assoc['id']] = $fetch_assoc;

$first_manager = sm_get_data_by_id($users_info, $project_info['project_manager_id']);
$second_manager = sm_get_data_by_id($users_info, $project_info['second_manager_id']);

if (isset($_POST['send']))
{
	$form_first_manager = isset($_POST['first_manager']) ? intval($_POST['first_manager']) : 0;
	$form_second_manager = isset($_POST['second_manager']) ? intval($_POST['second_manager']) : 0;
	$form_message = isset($_POST['message']) ? swift_trim($_POST['message']) : '';
	
	if ($form_message == '')
		$Core->add_error('Message cannot be empty.');
	
	if ($form_first_manager == 0 && $form_second_manager == 0)
		$Core->add_error('You must select at least one Manager to send a recommendation.');
	
	$subject = 'HCA: Special Project recommendation';
	$mail_message = 'You have a new recommendation. See details bellow.'."\n\n";
	if (!empty($form_message))
		$mail_message .= 'Recommendation: '.$DBLayer->escape($form_message)."\n\n";
	$mail_message .= 'Project ID number: '.$project_info['project_number']."\n\n";
	$mail_message .= 'Property name: '.$project_info['pro_name']."\n\n";
	$mail_message .= 'Project Managers: '.(($second_manager != '') ? $first_manager['realname'].', '.$second_manager['realname'] : $first_manager['realname'])."\n\n";
	$mail_message .= 'Description: '.(($project_info['project_desc'] != '') ? $DBLayer->escape($project_info['project_desc']) : 'N/A')."\n\n";
	$mail_message .= 'Remarks: '.(($project_info['remarks'] != '') ? $DBLayer->escape($project_info['remarks']) : 'N/A')."\n\n";
	$mail_message .= 'Start Date: '.(($project_info['start_date'] > 0) ? format_time($project_info['start_date'],1,'m/d/Y') : 'N/A')."\n\n";
	$mail_message .= 'End Date: '.(($project_info['end_date'] > 0) ? format_time($project_info['end_date'],1,'m/d/Y') : 'N/A')."\n\n";
	$mail_message .= 'Budget: $.'.$project_info['budget']."\n\n";
	$mail_message .= 'Cost: $.'.$project_info['cost']."\n\n";
	$mail_message .= 'To view the project recommendation follow this link: '.$URL->link('sm_special_projects_manage_recommendations', $id)."\n\n";
	
	$time_now = time();
	if (empty($Core->errors))
	{
		if ($form_first_manager == 1)
		{
			$query = array(
				'INSERT'	=> 'project_id, for_user_id, from_user_id, sent_time, message',
				'INTO'		=> 'sm_special_projects_recommendations',
				'VALUES'	=> 
					''.$project_info['id'].',
					'.$first_manager['id'].',
					'.$User->get('id').',
					'.$time_now.',
					\''.$DBLayer->escape($form_message).'\''
			);
			// add hook here too
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			
			//SEND EMAIL
			$SwiftMailer = new SwiftMailer;
			$SwiftMailer->send($first_manager['email'], $subject, $mail_message);
		}
		
		if ($form_second_manager == 1)
		{
			$query = array(
				'INSERT'	=> 'project_id, for_user_id, from_user_id, sent_time, message',
				'INTO'		=> 'sm_special_projects_recommendations',
				'VALUES'	=> 
					''.$project_info['id'].',
					'.$second_manager['id'].',
					'.$User->get('id').',
					'.$time_now.',
					\''.$DBLayer->escape($form_message).'\''
			);
			// add hook here too
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			
			//SEND EMAIL
			$SwiftMailer = new SwiftMailer;
			$SwiftMailer->send($second_manager['email'], $subject, $mail_message);
		}
		
		$query = array(
			'UPDATE'	=> 'sm_special_projects_records',
			'SET'		=> 'mngr_email_status='.$time_now,
			'WHERE'		=> 'id='.$id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
		// Add flash message
		$flash_message = 'A recommendations was sent for Project #'.$id;
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}
else if (isset($_POST['agree']))
{
	$rid = intval(key($_POST['agree']));

	if ($rid > 0)
	{
		$query = array(
			'UPDATE'	=> 'sm_special_projects_recommendations',
			'SET'		=> 'read_status=1, read_time='.time(),
			'WHERE'		=> 'id='.$rid
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	}
	
	// Add flash message
	$flash_message = 'Recommendation #'.$rid.' has been submitted in Project #'.$id;
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

$query = array(
	'SELECT'	=> 'id, project_id, for_user_id, from_user_id, sent_time, message, read_status, read_time',
	'FROM'		=> 'sm_special_projects_recommendations',
	'WHERE'		=> 'project_id='.$id,
	'ORDER BY'	=> 'sent_time DESC'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$rec_info = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$rec_info[] = $fetch_assoc;
}

$next_event = sm_special_projects_check_next_event($id, $event_alert);

if ($event_alert)
	$Core->add_warning('Upcoming Work! '.$next_event);

if ($project_info['cost'] > $project_info['budget'])
	$Core->add_warning('Total price $'.gen_number_format($project_info['cost'], 2).' is more than an Budget $'.gen_number_format($project_info['budget'], 2));

// Setup the form
$page_param['fld_count'] = $page_param['group_count'] = $page_param['item_count'] = 0;

$Core->set_page_title('Recommendations');
$Core->set_page_id('sm_special_projects_manage_recommendations', 'hca_sp');
require SITE_ROOT.'header.php';
?>

<style>
.main-subhead .hn{color: rebeccapurple;font-weight: bold;}
thead th {padding: 5px !important;border: 1px solid #adafc7;background: #c0e6fc;text-align: center;font-weight: bold !important;}
tbody td {padding: 5px 10px !important;border: 1px solid #adafc7;}
table {table-layout: initial;}
textarea:focus {height: 100px;}
.datetime{width: 10%;}
.button{text-align: center;width: 10%;}
.ct-group textarea{width:97%}
.datetime{width: 10%;}
.textarea{min-width:155px;}
.odd{background: #fafaeb;}
.even{background: #ebfaef;}
.empty-row{background-color: #eeebf1;padding: 10px 1px !important;}
thead .tc2{width: 200px;}
</style>

<div class="main-content main-frm">
	<div class="ct-group">
		<div class="ct-set warn-set">
			<div class="ct-box warn-box">
				<h6 class="ct-legend hn warn"><span>Project Information:</span></h6>
				<p>Created: <strong><?php echo format_time($project_info['created_date']) ?></strong></p>
				<p>Project ID: <strong><?php echo html_encode($project_info['project_number']) ?></strong></p>
				<p>Property: <strong><?php echo html_encode($project_info['pro_name']) ?></strong></p>
	<?php if ($project_info['unit_number'] != ''): ?>
				<p>Unit number: <strong><?php echo html_encode($project_info['unit_number']) ?></strong></p>
	<?php endif; ?>
				<p>Description: <strong><?php echo html_encode($project_info['project_desc']) ?></strong></p>
				<p>Budget: <strong>$<?php echo gen_number_format($project_info['budget'], 2) ?></strong></p>
			</div>
		</div>

		<form method="post" accept-charset="utf-8" action="">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			<fieldset class="frm-group group<?php echo ++$page_param['group_count'] ?>">
				<fieldset class="mf-set set1">
					<legend><span>Managers: </span></legend>
					<div class="mf-box mf-yesno">
						
<?php if (!empty($first_manager)) : ?>
						<div class="mf-item">
							<input type="hidden" name="first_manager" value="0">
							<span class="fld-input"><input type="checkbox" id="fld6" name="first_manager" value="1" checked="checked"></span>
							<label for="fld6" class="warn"><?php echo $first_manager['realname'] ?> </label>
						</div>
<?php endif; ?>
						
<?php if (!empty($second_manager)) : ?>
						<div class="mf-item">
							<input type="hidden" name="second_manager" value="0">
							<span class="fld-input"><input type="checkbox" id="fld7" name="second_manager" value="1" checked="checked"></span>
							<label for="fld7"><?php echo $second_manager['realname'] ?> </label>
						</div>
<?php endif; ?>
					</div>
				</fieldset>
				
				<div class="txt-set set<?php echo ++$page_param['item_count'] ?>">
					<div class="txt-box textarea">
						<label for="fld<?php echo ++$page_param['fld_count'] ?>"><span>Recommendation text</span><small>Marked Managers receive a recommendation.</small></label>
						<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $page_param['fld_count'] ?>" name="message" rows="3" cols="55"></textarea></span></div>
					</div>
				</div>
			</fieldset>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="send" value="Send recommendation"></span>
			</div>
		</form>

	</div>

	<div class="content-head">
		<h6 class="hn"><strong>List of recommendations</strong></h6>
	</div>
	<div class="ct-group">
<?php
if (!empty($rec_info))
{
?>
		<form method="post" accept-charset="utf-8" action="">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			</div>
			<table>
				<thead>
					<tr>
						<th class="tc1">From</th>
						<th class="tc2">To</th>
						<th class="tc3">Recommendation</th>
						<th class="tc4">Confirmed</th>
					</tr>
				</thead>
				<tbody>
<?php
	$odd_even_class = 'odd';
	foreach ($rec_info as $event) 
	{
		$from_user_info = sm_get_data_by_id($users_info, $event['from_user_id']);
		
		if ($event['for_user_id'] == $User->get('id'))
			$submitted = ($event['read_status'] == 0) ? '<span class="submit primary"><input type="submit" name="agree['.$event['id'].']" value="ok" ></span>' : 'Confirmed: '.format_time($event['read_time']);
		else
			$submitted = ($event['read_status'] == 0) ? 'Not confirmed' : 'Confirmed: '.format_time($event['read_time']);
		
		if (!empty($first_manager) && $first_manager['id'] == $event['for_user_id'])
			$project_manager = $first_manager['realname'];
		else if (!empty($second_manager) && $second_manager['id'] == $event['for_user_id'])
			$project_manager = $second_manager['realname'];
		
		if ($event['for_user_id'] == $User->get('id') || $access)
		{
?>
						<tr class="<?php echo $odd_even_class; ?>">
							<td class="datetime">
								<?php echo format_time($event['sent_time']); ?>
								<p><?php echo !empty($from_user_info) ? html_encode($from_user_info['realname']) : '' ?></p>
							</td>
							<td><?php echo $project_manager; ?></td>
							<td><?php echo html_encode($event['message']); ?></td>
							<td class="button"><?php echo $submitted ?></td>
						</tr>
<?php
			$odd_even_class = ($odd_even_class == 'odd') ? 'even' : 'odd';
		}
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
		<div class="ct-box warn-box">
			<p>No recommendations found for this project.</p>
		</div>
<?php
}
?>
	</div>
</div>

<?php
require SITE_ROOT.'footer.php';