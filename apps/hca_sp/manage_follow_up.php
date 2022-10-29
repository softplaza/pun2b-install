<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_sp', 12)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1) 
	message($lang_common['Bad request']);

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
		redirect('', $flash_message);
	}
}

if (isset($_POST['delete_event']))
{
	$project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
	$event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;

	$DBLayer->delete('sm_calendar_events', $event_id);

	$flash_message = 'Action has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}


$query = array(
	'SELECT'	=> 'e.id, e.project_id, e.date_time, e.message, u.realname',
	'FROM'		=> 'sm_calendar_events AS e',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'sm_special_projects_records AS pj',
			'ON'			=> 'pj.id=e.project_id'
		),
		array(
			'LEFT JOIN'		=> 'users AS u',
			'ON'			=> 'u.id=pj.project_manager_id'
		),
	),
	'WHERE'		=> 'project_name=\'hca_sp\' AND e.project_id='.$id,
	'ORDER BY'	=> 'e.date_time DESC'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
while ($row = $DBLayer->fetch_assoc($result)) {
	$follow_up_info[] = $row;
}

$Core->set_page_id('sm_special_projects_manage_follow_up', 'hca_sp');
require SITE_ROOT.'header.php';

if (!empty($follow_up_info))
{
?>
	<div class="card-header">
		<h6 class="card-title mb-0">Follow Up Dates</h6>
	</div>

	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<table class="table table-sm table-striped table-bordered">
			<thead class="sticky-under-menu">
				<tr>
					<th>Date and time</th>
					<th>Message</th>
					<th>Submitted by</th>
				</tr>
			</thead>
			<tbody>
<?php
	foreach($follow_up_info as $cur_info)
	{
?>
		<tr>
			<td>
				<span class="float-end" onclick="getEvent(<?php echo $id ?>, <?php echo $cur_info['id'] ?>)"><i class="fas fa-edit fa-2x text-primary" data-bs-toggle="modal" data-bs-target="#exampleModal"></i></span>
				<?php echo format_date($cur_info['date_time'], 'n/j/y h:i a') ?>
			</td>
			<td class="min-200"><?php echo html_encode($cur_info['message']) ?></td>
			<td class="min-200"><?php echo html_encode($cur_info['realname']) ?></td>
		</tr>
<?php
	}
?>
			</tbody>
		</table>
	</form>
<?php
}
?>

<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal" onclick="followUpWindow(<?php echo $id ?>)">Add action</button>


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