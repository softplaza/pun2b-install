<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$permission_level1 = ($User->checkAccess('hca_sp', 1)) ? true : false;
$permission_level2 = ($User->checkAccess('hca_sp', 12)) ? true : false; // manage
if (!$permission_level1)
	message($lang_common['No permission']);

$pid = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$search_by_project_manager_id = isset($_GET['project_manager_id']) ? intval($_GET['project_manager_id']) : 0;
$search_by_work_status = isset($_GET['work_status']) ? intval($_GET['work_status']) : 0;
$search_by_project_number = isset($_GET['project_number']) ? swift_trim($_GET['project_number']) : '';

$search_query = [];
$search_query[] = 'pj.work_status!=0';
$search_query[] = 'pj.work_status!=5';
if ($search_by_property_id > 0)
	$search_query[] = 'pj.property_id='.$search_by_property_id;
if ($search_by_project_manager_id > 0)
	$search_query[] = '(pj.project_manager_id='.$search_by_project_manager_id.' OR pj.second_manager_id='.$search_by_project_manager_id.')';
if ($search_by_work_status > 0)
	$search_query[] = 'pj.work_status='.$search_by_work_status;
if ($search_by_project_number != '')
	$search_query[] = 'pj.project_number=\''.$DBLayer->escape($search_by_project_number).'\'';
// Limit projects for property managers
if ($User->get('sm_pm_property_id') > 0)
	$search_query[] = 'pj.second_manager_id='.$User->get('id');

$work_statuses = array(
	//0 => 'Removed',
	1 => 'Active',
	2 => 'Bid Phase',
	6 => 'Contract Phase',
	7 => 'Job Phase',
	3 => 'Pending',
	4 => 'On Hold',
//	5 => 'Completed',
);

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
				'VALUES'	=> '\'hca_sp\',
					\''.$DBLayer->escape($project_id).'\',
					\''.$DBLayer->escape($User->get('id')).'\',
					\''.$DBLayer->escape($date_time).'\',
					\''.$DBLayer->escape($event_message).'\''
			);
			$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		}

		$flash_message = 'Action has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('sm_special_projects_active', $project_id), $flash_message);
	}
}

else if (isset($_POST['delete_event']))
{
	$project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
	$event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;

	$DBLayer->delete('sm_calendar_events', $event_id);

	$flash_message = 'Action has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect($URL->link('sm_special_projects_active', $project_id), $flash_message);
}

$query = array(
	'SELECT'	=> 'COUNT(pj.id)',
	'FROM'		=> 'sm_special_projects_records AS pj',
);
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

// Show only WORK STARTED - BID - ON HOLD
$query = array(
	'SELECT'	=> 'pj.*, pt.pro_name, u1.realname AS first_manager, u2.realname AS second_manager',
	'FROM'		=> 'sm_special_projects_records AS pj',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'sm_property_db AS pt',
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
	'ORDER BY'	=> 'pt.pro_name',
	'LIMIT'		=> $PagesNavigator->limit()
);
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $projects_ids = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$main_info[$fetch_assoc['id']] = $fetch_assoc;
	$projects_ids[] = $fetch_assoc['id'];
}
$PagesNavigator->num_items($main_info);

$follow_up_info = array();
if (!empty($projects_ids))
{
	$query = array(
		'SELECT'	=> 'id, table_id',
		'FROM'		=> 'sm_uploader',
		'WHERE'		=> 'table_id IN ('.implode(',', $projects_ids).') AND table_name=\'sm_special_projects_records\''
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$uploader_info = array();
	while ($row = $DBLayer->fetch_assoc($result)) {
		$uploader_info[] = $row['table_id'];
	}

	$query = array(
		'SELECT'	=> 'e.id, e.project_id, e.date_time, e.message',
		'FROM'		=> 'sm_calendar_events AS e',
		'WHERE'		=> 'e.project_id IN('.implode(',', $projects_ids).') AND project_name=\'hca_sp\'',
		'ORDER BY'	=> 'e.time'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result)) {
		$follow_up_info[] = $row;
	}
}

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'sm_property_db',
	'ORDER BY'	=> 'pro_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $fetch_assoc;
}

$project_managers = $User->getUserAccess('hca_sp', 14, 1);

$Core->set_page_id('sm_special_projects_active', 'hca_sp');
require SITE_ROOT.'header.php';
?>

<style>
.table .ta-center {text-align:center}
.min-200 {min-width:200px}
</style>

	<nav class="navbar container-fluid search-box">
		<form method="get" accept-charset="utf-8" action="">
			<div class="row">
				<div class="col pe-0">
					<select name="property_id" class="form-select-sm">
						<option value="">Display All Properties</option>
<?php
foreach ($property_info as $val) {
	if ($search_by_property_id == $val['id'])
		echo '<option value="'.$val['id'].'" selected>'.$val['pro_name'].'</option>';
	else
		echo '<option value="'.$val['id'].'">'.$val['pro_name'].'</option>';
}
?>
					</select>
				</div>
				<div class="col pe-0">
					<select name="project_manager_id" class="form-select-sm">
						<option value="">All Managers</option>
<?php 
foreach ($project_managers as $user_info)
{
	if ($search_by_project_manager_id == $user_info['id'])
		echo '<option value="'.$user_info['id'].'" selected>'.$user_info['realname'].'</option>';
	else
		echo '<option value="'.$user_info['id'].'">'.$user_info['realname'].'</option>';
}
?>
					</select>
				</div>
				<div class="col pe-0">
					<select name="work_status" class="form-select-sm">
						<option value="0">All Statuses</option>
<?php
foreach ($work_statuses as $key => $val)
{
	if ($search_by_work_status == $key) {
		echo '<option value="'.$key.'" selected>'.$val.'</option>';
	} else {
		echo '<option value="'.$key.'">'.$val.'</option>';
	}
}
?>
					</select>
				</div>
				<div class="col pe-0">
					<input name="project_number" type="text" value="<?php echo isset($_GET['project_number']) ? $_GET['project_number'] : '' ?>" placeholder="Project number" class="form-control-sm"/>
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
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<table class="table table-sm table-striped table-bordered">
			<thead>
				<tr>
					<th class="th1">Property</th>
					<th>Project number</th>
					<th>Project description</th>
					<th>Action Date</th>
					<th>Action Owner</th>
					<th>Follow Up Dates</th>
					<th>Start Date</th>
					<th>End Date</th>
					<th>Cost</th>
					<th>Remarks</th>
					<th>Status</th>
				</tr>
			</thead>
			<tbody>
<?php

	$next_events = !empty($projects_ids) ? sm_special_projects_check_next_events($projects_ids) : array();
	
	foreach ($main_info as $cur_info)
	{
		$work_status_title = isset($work_statuses[$cur_info['work_status']]) ? $work_statuses[$cur_info['work_status']] : '';

		if ($cur_info['work_status'] == 1)
			$css_status = 'fw-bold';
		else if ($cur_info['work_status'] == 2)
			$css_status = 'bg-warning fw-bold';
		else if ($cur_info['work_status'] == 6)
			$css_status = 'bg-primary fw-bold text-white';
		else if ($cur_info['work_status'] == 7)
			$css_status = 'bg-success fw-bold text-white';
		else if ($cur_info['work_status'] == 3)
			$css_status = 'bg-info fw-bold';
		else if ($cur_info['work_status'] == 4)
			$css_status = 'bg-secondary fw-bold text-white';
		else
			$css_status = 'fw-bold';

		if ($permission_level2)
		{
			$Core->add_dropdown_item('<a href="'.$URL->link('sm_special_projects_manage', $cur_info['id']).'"><i class="fas fa-edit"></i> Edit project</a>');
			$Core->add_dropdown_item('<a href="'.$URL->link('sm_special_projects_manage_files', $cur_info['id']).'"><i class="far fa-image"></i> Upload Files</a>');
			$Core->add_dropdown_item('<a href="'.$URL->link('sm_special_projects_manage_invoice', $cur_info['id']).'"><i class="fas fa-file-invoice-dollar"></i> Invoice</a>');
			$Core->add_dropdown_item('<a href="'.$URL->link('sm_special_projects_manage_follow_up', $cur_info['id']).'"><i class="far fa-calendar-alt"></i> Follow-Up Dates</a>');
			$Core->add_dropdown_item('<a href="'.$URL->link('sm_special_projects_manage_recommendations', $cur_info['id']).'"><i class="fas fa-info-circle"></i> Recomendations</a>');

			$dropdown_menu = '<span class="float-end">'.$Core->get_dropdown_menu($cur_info['id']).'</span>';
		}
		else
			$dropdown_menu = '';
		
		$view_files = in_array($cur_info['id'], $uploader_info) ? '<a href="'.$URL->link('sm_special_projects_manage_files', $cur_info['id']).'" class="btn btn-sm btn-success text-white">Files</a>' : '';

		//CHECK NEW EVENT AND SEND EMAIL UPCOMING WORK
		$next_event = isset($next_events[$cur_info['id']]) ? $next_events[$cur_info['id']] : '';
		
		$page_param['td'] = array();
		$page_param['td']['unit_number'] = ($cur_info['unit_number'] != '') ? '<p>Unit#: '.html_encode($cur_info['unit_number']).'</p>' : '';
		$page_param['td']['pending'] = ($cur_info['admin_email_status'] == 1) ? '<strong style="color:green;">Mailed</strong>' : '<strong style="color:blue;">Pending</strong>';
		
		$follow_up_dates = [];
		foreach ($follow_up_info as $cur_follow_up)
		{
			if ($cur_info['id'] == $cur_follow_up['project_id'])
			{
				$follow_up_dates[] = '<div class="alert-info mb-1 p-1">';
				if ($permission_level2)
					$follow_up_dates[] = '<span class="float-end" onclick="getEvent('.$cur_info['id'].', '.$cur_follow_up['id'].')"><i class="fas fa-edit fa-lg" data-bs-toggle="modal" data-bs-target="#exampleModal"></i></span>';
				$follow_up_dates[] = '<ins>'.format_date($cur_follow_up['date_time'], 'n/j/y h:i a').'</ins>';
				$follow_up_dates[] = '<p>'.html_encode($cur_follow_up['message']).'</p>';
				$follow_up_dates[] = '</div>';
			}
		}

		$total_cost = ($cur_info['cost'] != '') ? '<strong>$'.html_encode($cur_info['cost']).'</strong>' : '';
?>
				<tr id="row<?php echo $cur_info['id'] ?>" class="<?php echo ($cur_info['id'] == $pid) ? 'anchor' : '' ?>">
					<td class="td1">
						<?php echo html_encode($cur_info['pro_name']) ?>
						<?php echo $page_param['td']['unit_number'] ?>
						<p>(<?php echo ($cur_info['project_scale'] == 1) ? 'Major' : 'Minor' ?>)</p>
						<?php echo $view_files ?>
						<?php echo $dropdown_menu ?>
					</td>
					<td class="ta-center"><?php echo $cur_info['project_number'] ?></td>
					<td class="min-200"><?php echo html_encode($cur_info['project_desc']) ?></td>
					<td class="ta-center"><?php echo format_date($cur_info['date_action_start'], 'n/j/y') ?></td>
					<td class="ta-center">
						<?php echo html_encode($cur_info['first_manager']) ?>
						<p><?php echo html_encode($cur_info['second_manager']) ?></p>
					</td>
					<td class="min-200" id="follow_up_pid<?php echo $cur_info['id'] ?>">
						<?php echo implode("\n", $follow_up_dates) ?>
<?php if ($permission_level2) : ?>
						<p class="float-end"><i class="fas fa-plus-circle fa-lg text-primary" data-bs-toggle="modal" data-bs-target="#exampleModal" data-bs-whatever="" onclick="followUpWindow(<?php echo $cur_info['id'] ?>)"></i></p>
<?php endif; ?>
					</td>
					<td class="ta-center"><?php echo format_date($cur_info['date_bid_start'], 'n/j/y') ?></td>
					<td class="ta-center"><?php echo format_date($cur_info['date_job_end'], 'n/j/y') ?></td>
					<td class="ta-center" <?php echo ($cur_info['cost'] > $cur_info['budget'] && intval($cur_info['budget']) > 0) ? 'style="background: #f7b7c2;"' : '' ?>><?php echo $total_cost ?></td>
					<td class="min-200"><?php echo $cur_info['remarks'] ?></td>
					<td class="ta-center <?php echo $css_status ?>"><?php echo $work_status_title ?></td>
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
?>

<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method="post" accept-charset="utf-8" action="">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			<input type="hidden" name="project_id" value="0" />
			<input type="hidden" name="event_id" value="0" />
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLabel">Follow Up Date</h5>
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
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_sp_ajax_get_events')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_sp_ajax_get_events') ?>",
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
require SITE_ROOT.'footer.php';
