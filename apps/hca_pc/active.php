<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_pc', 1)) ? true : false;
if(!$access)
	message($lang_common['No permission']);

$access12 = ($User->checkAccess('hca_pc', 12)) ? true : false; // manage projects
$access14 = ($User->checkAccess('hca_pc', 14)) ? true : false; // send emails to managers

$cur_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = isset($_GET['action']) ? swift_trim($_GET['action']) : '';

$reported_by_arr = array('MANAGER', 'RESIDENT', 'MAINTENANCE TECH', 'VENDOR');
$pest_problem_arr = array('ROACHES','BED BUGS','RATS','MICE','FLEAS', 'TERMITES','GOPHERS','OTHER');
$vendors_arr = array('TERMINIX','MVP','THRASHER');
$apt_locations_arr = array('L/ROOM','D/ROOM','KITCHEN','HALLWAY','G/BATHROOM','M/BATHROOM','G/BEDROOM','M/BEDROOM','LAUNDRY','BALCONY','WHATER HEATER CLOSET','ATTICK','ENTIRE UNIT','LANDSCAPE');
$unit_clearance_arr = array(0 => 'NO', 1 => 'YES', 2 => 'IN PROGRESS', 3 => 'ON HOLD');

if (isset($_POST['update_event']))
{
	$project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
	$event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
	$date_time = isset($_POST['date_time']) ? swift_trim($_POST['date_time']) : '';
	$event_message = isset($_POST['message']) ? swift_trim($_POST['message']) : '';
	
	if ($event_message == '')
		$Core->add_error('Event message can not by empty. Write your message.');
	if ($date_time == '')
		$Core->add_error('Incorrect Date. Set the date for the event.');
	
	if (empty($Core->errors))
	{
		if ($event_id > 0)
		{
			$form_data = [
				'date_time'		=> $date_time,
				'message'		=> $event_message
			];
			$DBLayer->update('sm_calendar_events', $form_data, $event_id);
		}
		else
		{
			$query = array(
				'INSERT'	=> 'project_name, project_id, poster_id, date_time, message',
				'INTO'		=> 'sm_calendar_events',
				'VALUES'	=> '\'hca_pc\',
					\''.$DBLayer->escape($project_id).'\',
					\''.$DBLayer->escape($User->get('id')).'\',
					\''.$DBLayer->escape($date_time).'\',
					\''.$DBLayer->escape($event_message).'\''
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}

		$flash_message = 'Message has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('sm_pest_control_active', $project_id), $flash_message);
	}
}

else if (isset($_POST['delete_event']))
{
	$project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
	$event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;

	$DBLayer->delete('sm_calendar_events', $event_id);

	$flash_message = 'Message has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('sm_pest_control_active', $project_id), $flash_message);
}

else if (isset($_POST['send_email']))
{
	$id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
	$manager_action = swift_trim($_POST['manager_action']);
	
	if ($id > 0)
	{
		$query = array(
			'SELECT'	=> 'r.*, p.pro_name, p.manager_email',
			'FROM'		=> 'sm_pest_control_records AS r',
			'JOINS'		=> array(
				array(
					'LEFT JOIN'		=> 'sm_property_db AS p',
					'ON'			=> 'p.id=r.property_id'
				),
			),
			'WHERE'		=> 'r.id='.$id,
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$project_info = $DBLayer->fetch_assoc($result);
		
		if (!isset($project_info['manager_email']) || empty($project_info['manager_email']))
			$Core->add_error('Manager email is empty, please go to PROPERTY MANAGEMENT => LIST OF PROPERTIES and fill the Email of Property.');
		if (empty($manager_action))
			$Core->add_error('Notice for Manager is empty, please fill the field for Manager.');
		
		$time_now = time();
		if (empty($Core->errors))
		{
			//CREATE UNCOMLETED FORM FOR MANAGER
			$query = array(
				'INSERT'	=> 'project_id, notice_for_manager, mailed_time, link_hash',
				'INTO'		=> 'sm_pest_control_forms',
				'VALUES'	=> ''.$id.',
					\''.$DBLayer->escape($manager_action).'\',
					\''.$time_now.'\',
					\''.$DBLayer->escape($project_info['link_hash']).'\''
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			$new_id = $DBLayer->insert_id();
			
			if ($new_id)
			{
				$mail_message = ($Config->get('o_sm_pest_control_manager_email_msg') != '' ? $DBLayer->escape($Config->get('o_sm_pest_control_manager_email_msg')) : $DBLayer->escape('N/A'))."\n\n";
				$mail_message .= $DBLayer->escape($URL->link('sm_pest_control_form', array($new_id, $project_info['link_hash'])));
				
				//SEND EMAIL to Manager of Property
				$SwiftMailer = new SwiftMailer;
				$SwiftMailer->send($project_info['manager_email'], 'Pest Control Project', $mail_message);
			
				$query = array(
					'UPDATE'	=> 'sm_pest_control_records',
					'SET'		=> 'manager_check=0, email_status=1, manager_action=\''.$DBLayer->escape($manager_action).'\', mailed_last_time=\''.$DBLayer->escape($time_now).'\', last_form_id='.$new_id,
					'WHERE'		=> 'id='.$id
				);
				$DBLayer->query_build($query) or error(__FILE__, __LINE__);
				
				// Add flash message
				$flash_message = 'Email has been sent to '.$project_info['manager_email'];
				$FlashMessenger->add_info($flash_message);
				redirect($URL->link('sm_pest_control_active', $id.'#rid'.$id), $flash_message);
			}
			else 
				$Core->add_error('Cannot send form for the Manager.');
		}
	}
	else
		$Core->add_error('Wrong request. Update the page and try again.');
}

$search_by_property = isset($_GET['property']) ? swift_trim($_GET['property']) : '';
$search_by_unit = isset($_GET['unit']) ? swift_trim($_GET['unit']) : '';

$query = array(
	'SELECT'	=> 'COUNT(id)',
	'FROM'		=> 'sm_pest_control_records',
	'WHERE'		=> '(unit_clearance=0 OR unit_clearance=2 OR unit_clearance=3)',
);
// SEARCH BY SECTION //
if (!empty($search_by_property) && $search_by_property != 'ALL PROPERTIES') {
	$query['WHERE'] .= ' AND property=\''.$DBLayer->escape($search_by_property).'\'';
}
if (!empty($search_by_unit)) {
	$search_by_unit2 = '%'.$search_by_unit.'%';
	$query['WHERE'] .= ' AND unit LIKE \''.$DBLayer->escape($search_by_unit2).'\'';
}
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

// Setup the form
$page_param['fld_count'] = $page_param['group_count'] = $page_param['item_count'] = 0;
$cur_id = (isset($id) && $id > 0) ? $id : $cur_id;

$query = array(
	'SELECT'	=> 'pro_name',
	'FROM'		=> 'sm_property_db',
	'ORDER BY'	=> 'display_position'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $fetch_assoc;
}

$query = array(
	'SELECT'	=> 'r.*, p.pro_name',
	'FROM'		=> 'sm_pest_control_records AS r',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'sm_property_db AS p',
			'ON'			=> 'p.id=r.property_id'
		),
	),
	'WHERE'		=> '(r.unit_clearance=0 OR r.unit_clearance=2 OR r.unit_clearance=3)',
	'ORDER BY'	=> 'r.property, p.pro_name',
	'LIMIT'		=> $PagesNavigator->limit(),
);
// SEARCH BY SECTION //
if (!empty($search_by_property) && $search_by_property != 'ALL PROPERTIES') {
	$query['WHERE'] .= ' AND property=\''.$DBLayer->escape($search_by_property).'\'';
}
if (!empty($search_by_unit)) {
	$search_by_unit2 = '%'.$search_by_unit.'%';
	$query['WHERE'] .= ' AND unit LIKE \''.$DBLayer->escape($search_by_unit2).'\'';
}
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $projects_ids = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$main_info[] = $row;
	$projects_ids[] = $row['id'];
}
$PagesNavigator->num_items($main_info);

$follow_up_info = [];
if (!empty($projects_ids))
{
	$query = array(
		'SELECT'	=> 'e.id, e.project_id, e.date_time, e.message',
		'FROM'		=> 'sm_calendar_events AS e',
		'WHERE'		=> 'e.project_id IN('.implode(',', $projects_ids).') AND project_name=\'hca_pc\'',
		'ORDER BY'	=> 'e.time'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result)) {
		$follow_up_info[] = $row;
	}
}

$Core->set_page_id('sm_pest_control_active', 'hca_pc');
require SITE_ROOT.'header.php';
?>

<nav class="navbar container-fluid search-box">
	<form method="get" accept-charset="utf-8" action="">
		<div class="row">
			<div class="col pe-0">
				<select name="property" class="form-select-sm">
					<option value="">Select property</option>
<?php 
foreach ($property_info as $val)
{
	if($search_by_property == $val['pro_name'])
		echo '<option value="'.$val['pro_name'].'" selected="selected">'.$val['pro_name'].'</option>';
	else
		echo '<option value="'.$val['pro_name'].'">'.$val['pro_name'].'</option>';
}
?>
				</select>
			</div>
			<div class="col pe-0">
				<input name="unit" type="text" value="<?php echo $search_by_unit ?>" placeholder="Enter Unit #" class="form-select-sm"/>
			</div>
			<div class="col pe-0">
				<button type="submit" class="btn btn-sm btn-outline-success">Search</button>
			</div>
		</div>
	</form>
</nav>

<?php
if (!empty($main_info))
{
?>

<form method="post" accept-charset="utf-8" action="">
	<div class="hidden">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
	</div>
	<table class="table table-sm table-striped table-bordered">
		<thead>
			<tr>
				<th class="th1">Property/Unit</th>
				<th>Location</th>
				<th>Reported by/Date</th>
				<th>Pest Control Problem</th>
				<th>Pest Control Action</th>
				<th>Surrounging Unit Inspection Date</th>
				<th>Vendor</th>
				<th>Start Date</th>
				<th>Follow-Up Date</th>
				<th>Notice for Manager<p></p></th>
				<th>Projected Completion Date</th>
				<th>Unit Clearance</th>
				<th>Remarks<p><span class="th-desc">(if "NO" or "ON HOLD" Why? Leave a comment below)<span></p></th>
			</tr>
		</thead>
		<tbody>
<?php

	foreach ($main_info as $cur_info)
	{
		if ($access12)
		{
			$Core->add_dropdown_item('<a href="'.$URL->link('sm_pest_control_manage_project', $cur_info['id']).'" class="btn btn-sm btn-primary text-white"><i class="fas fa-edit"></i> Edit project</a>');
			if ($access14)
				$Core->add_dropdown_item('<button type="button"  class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#emailModal" data-bs-whatever="" onclick="emailWindow('.$cur_info['id'].')"><i class="fas fa-at"></i> Notice for Manager</button>');

			$dropdown_menu = '<span class="float-end">'.$Core->get_dropdown_menu($cur_info['id']).'</span>';
		}
		else
			$dropdown_menu = '';

		$form_link = ($access14 && $cur_info['last_form_id'] > 0) ? $URL->link('sm_pest_control_form', array($cur_info['last_form_id'], $cur_info['link_hash'])) : '#';
		if ($cur_info['manager_check'] == 1)
			$email_status = '<a href="'.$form_link.'" class="badge bg-success text-white">Submited</a>';
		else if ($cur_info['email_status'] == 1)
			$email_status = '<a href="'.$form_link.'" class="badge bg-primary text-white">Noticed</a>';
		else
			$email_status = '<a href="'.$form_link.'" class="badge bg-secondary text-white">Not sent</a>';
		
		$status = isset($unit_clearance_arr[$cur_info['unit_clearance']]) ? $unit_clearance_arr[$cur_info['unit_clearance']] : 'n/a';
		$anchor = ($cur_id == $cur_info['id']) ? 'anchor' : '';

		$follow_up_dates = [];
		foreach ($follow_up_info as $cur_follow_up)
		{
			if ($cur_info['id'] == $cur_follow_up['project_id'])
			{
				$follow_up_dates[] = '<div class="alert-info mb-1 p-1">';
				if ($access12)
					$follow_up_dates[] = '<span class="float-end" onclick="getEvent('.$cur_info['id'].', '.$cur_follow_up['id'].')"><i class="fas fa-edit fa-lg" data-bs-toggle="modal" data-bs-target="#followUpModal"></i></span>';
				$follow_up_dates[] = '<ins>'.format_date($cur_follow_up['date_time'], 'n/j/y h:i a').'</ins>';
				$follow_up_dates[] = '<p>'.html_encode($cur_follow_up['message']).'</p>';
				$follow_up_dates[] = '</div>';
			}
		}
?>
			<tr id="rid<?php echo $cur_info['id'] ?>" class="<?php echo $anchor ?>">
				<td class="td1 property">
					<p><?php echo html_encode($cur_info['pro_name']) ?></p>
					<p>Unit: <?php echo html_encode($cur_info['unit']) ?></p>
					<?php echo $dropdown_menu ?>
				</td>
				<td><?php echo html_encode($cur_info['location']) ?></td>
				<td>
					<?php echo html_encode($cur_info['reported_by']) ?>
					<?php echo format_time($cur_info['reported'], 1, 'n/j/Y') ?>
				</td>
				<td><?php echo html_encode($cur_info['pest_problem']) ?></td>
				<td><?php echo html_encode($cur_info['pest_action']) ?></td>
				<td><?php echo format_time($cur_info['inspection_date'], 1, 'n/j/Y') ?></td>
				<td><?php echo html_encode($cur_info['vendor']) ?></td>
				<td><?php echo format_time($cur_info['start_date'], 1, 'n/j/Y') ?></td>
				<td class="min-200">
					<?php echo html_encode($cur_info['follow_up']) ?>
					<?php echo implode("\n", $follow_up_dates) ?>
<?php if ($access12) : ?>
					<p class="float-end"><i class="fas fa-plus-circle fa-lg text-primary" data-bs-toggle="modal" data-bs-target="#followUpModal" data-bs-whatever="" onclick="followUpWindow(<?php echo $cur_info['id'] ?>)"></i></p>
<?php endif; ?>
				</td>
				<td>
					<?php echo html_encode($cur_info['manager_action']) ?>
					<p><?php echo $email_status ?></p>
				</td>
				<td><?php echo format_time($cur_info['completion_date'], 1, 'n/j/Y') ?></td>
				<td><?php echo $status ?></td>
				<td><?php echo html_encode($cur_info['remarks']) ?></td>
			</tr>
<?php
	}
?>
		</tbody>
	</table>
</form>

<div class="modal fade" id="emailModal" tabindex="-1" aria-labelledby="emailModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method="post" accept-charset="utf-8" action="">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
				<input type="hidden" name="project_id" value="0" />
				<div class="modal-header">
					<h5 class="modal-title" id="emailModalLabel">Notice for Manager</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="mb-3">
						<label for="fld_manager_action" class="col-form-label">Message</label>
						<textarea name="manager_action" class="form-control" id="fld_manager_action"></textarea>
					</div>
				</div>
				<div class="modal-footer">
					<button type="submit" name="send_email" class="btn btn-primary">Send email</button>
				</div>
			</form>
		</div>
	</div>
</div>

<div class="modal fade" id="followUpModal" tabindex="-1" aria-labelledby="followUpModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method="post" accept-charset="utf-8" action="">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			<input type="hidden" name="project_id" value="0" />
			<input type="hidden" name="event_id" value="0" />
				<div class="modal-header">
					<h5 class="modal-title" id="followUpModalLabel">Follow Up Date</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="alert alert-warning" role="alert">
						Error: No internet connection or action has been deleted.
					</div>
					<div class="mb-3">
						<label for="fld_datetime" class="col-form-label">Date and time</label>
						<input type="datetime-local" name="date_time" class="form-control" id="fld_datetime">
					</div>
					<div class="mb-3">
						<label for="fld_message" class="col-form-label">Message</label>
						<textarea name="message" class="form-control" id="fld_message" rows="5"></textarea>
					</div>
				</div>
				<div class="modal-footer">
					<button type="submit" name="update_event" class="btn btn-primary">Update action</button>
					<button type="submit" name="delete_event" class="btn btn-danger" id="btn_delete" onclick="return confirm('Are you sure you want to delete this action?')">Delete</button>
				</div>
			</form>
		</div>
	</div>
</div>

<script>
function emailWindow(pid){
	$('.modal input[name="project_id"]').val(pid);
	$('.modal input[name="event_id"]').val('0');
	$('.modal input[name="datetime"]').val('');
	$('.modal textarea').val('');
}
function followUpWindow(pid){
	$('.modal #btn_delete').css('display', 'none');
	$(".modal .alert").css('display', 'none');
	$('.modal input[name="project_id"]').val(pid);
	$('.modal input[name="event_id"]').val('0');
	$('.modal input[name="datetime"]').val('');
	$('.modal textarea').val('');
}
function getEvent(pid,id)
{
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_pc_ajax_get_events')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_pc_ajax_get_events') ?>",
		type:	"POST",
		dataType: "json",
		data: ({event_id:id,csrf_token:csrf_token}),
		success: function(re){
			$(".modal .alert").css('display', 'none');
			$('.modal input[name="date_time"]').val(re.event_datetime);
			$('.modal textarea').val(re.event_message);
			$('.modal #btn_delete').css('display', 'block');
		},
		error: function(re){
			$(".modal .alert").css('display', 'block');
		}
	});
	
	$('.modal input[name="project_id"]').val(pid);
	$('.modal input[name="event_id"]').val(id);
}
</script>

<?php
} else {
?>
	<div class="alert alert-warning mt-3" role="alert">You have no items on this page or not found within your search criteria. <a href="<?php echo $URL->link('sm_pest_control_new') ?>">Create a new project.</a></div>
<?php
}
require SITE_ROOT.'footer.php';