<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access3 = ($User->checkAccess('swift_events', 3)) ? true : false;//edit
$access4 = ($User->checkAccess('swift_events', 4)) ? true : false;//add
//if (!$access)
//	message($lang_common['No permission']);

$search_by_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$search_by_event_type = isset($_GET['event_type']) ? intval($_GET['event_type']) : -1;
$search_by_event_status = isset($_GET['event_status']) ? intval($_GET['event_status']) : -1;

$SwiftEvents = new SwiftEvents;

if (isset($_POST['update_event']))
{
	$event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
	$form_data = [
		'datetime_created' => isset($_POST['datetime_created']) ? $_POST['datetime_created'] : date('Y-m-d H:i:s'),
		'message' => isset($_POST['message']) ? swift_trim($_POST['message']) : '',
		'event_type' => isset($_POST['event_type']) ? intval($_POST['event_type']) : 0,
	];
	
	if (isset($_POST['event_status']))
		$form_data['event_status'] = intval($_POST['event_status']);

	if ($form_data['message'] == '')
		$Core->add_error('Message cannot be empty.');
	
	if (empty($Core->errors) && $event_id > 0)
	{
		$DBLayer->update('swift_events', $form_data, $event_id);

		$flash_message = 'Event was updated.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['delete_event']))
{
	$event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
	
	if ($event_id > 0)
	{
		$DBLayer->delete('swift_events', $event_id);

		$flash_message = 'Event was deleted.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

$query = array(
	'SELECT'	=> 'u.id, u.realname',
	'FROM'		=> 'users AS u',
	'WHERE'		=> 'u.id > 1 AND u.id < 4',
	///'WHERE'		=> 'u.group_id='.$Config->get('o_hca_fs_maintenance'),
	'ORDER BY'	=> 'u.realname'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users_info = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$users_info[] = $row;
}

$swift_events = $search_query = [];

if ($search_by_user_id > 0)
	$search_query[] = 'e.user_id='.$search_by_user_id;
if ($search_by_event_type > -1)
	$search_query[] = 'e.event_type='.$search_by_event_type;
if ($search_by_event_status > -1)
	$search_query[] = 'e.event_status='.$search_by_event_status;


$query = [
	'SELECT'	=> 'COUNT(e.id)',
	'FROM'		=> 'swift_events as e',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'u.id=e.user_id'
		],
	],
	'LIMIT'		=> $PagesNavigator->limit()
];
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$query = [
	'SELECT'	=> 'e.*, u.realname',
	'FROM'		=> 'swift_events as e',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'u.id=e.user_id'
		],
	],
	'ORDER BY'	=> 'e.datetime_created DESC',
	'LIMIT'		=> $PagesNavigator->limit()
];
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
while ($row = $DBLayer->fetch_assoc($result)) {
	$swift_events[] = $row;
}
$PagesNavigator->num_items($swift_events);

$Core->set_page_id('swift_events_calendar', 'swift_events');
require SITE_ROOT.'header.php';
?>

<nav class="navbar search-bar mb-1">
	<form method="get" accept-charset="utf-8" action="" class="d-flex">
		<div class="container-fluid justify-content-between">
			<div class="row">
				<div class="col-md-auto pe-0 mb-1">
					<select name="user_id" class="form-select-sm">
						<option value="0">All members</option>
<?php
foreach ($users_info as $user_info)
{
	if ($search_by_user_id == $user_info['id'])
		echo '<option value="'.$user_info['id'].'" selected>'.$user_info['realname'].'</option>';
	else
		echo '<option value="'.$user_info['id'].'">'.$user_info['realname'].'</option>';
}
?>
					</select>
				</div>
				<div class="col-md-auto pe-0 mb-1">
					<select name="event_type" class="form-select-sm">
						<option value="-1">All events</option>
<?php
foreach ($SwiftEvents->event_types as $key => $value)
{
	if ($search_by_event_type == $key)
		echo '<option value="'.$key.'" selected>'.$value.'</option>';
	else
		echo '<option value="'.$key.'">'.$value.'</option>';
}
?>
					</select>
				</div>
				<div class="col-md-auto pe-0 mb-1">
					<select name="event_status" class="form-select-sm">
						<option value="-1">Any status</option>
<?php
$event_statuses = [0 => 'In progress', 1 => 'Completed'];
foreach ($event_statuses as $key => $value)
{
	if ($search_by_event_status == $key)
		echo '<option value="'.$key.'" selected>'.$value.'</option>';
	else
		echo '<option value="'.$key.'">'.$value.'</option>';
}
?>
					</select>
				</div>
				<div class="col-md-auto">
					<button type="submit" class="btn btn-sm btn-outline-success">Search</button>
				</div>
			</div>
		</div>
	</form>
</nav>

<?php echo $PagesNavigator->getNavi(); ?>
<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th class="min-w-10">Time</th>
			<th class="min-w-20">Message</th>
			<th class="min-w-10">Posted by</th>
		</tr>
	</thead>
	<tbody>
		
<?php
if (!empty($swift_events)) 
{
	foreach($swift_events as $cur_info)
	{
		$event_status = ($cur_info['event_status'] == 1) ? '<i class="fas fa-check-circle text-success ms-1"></i>' : '';

		if ($cur_info['event_type'] == 1)
			$event_type = '<i class="fas fa-thumbtack text-primary me-1"></i>';
		else if ($cur_info['event_type'] == 2)
			$event_type = '<i class="fas fa-bell text-danger me-1"></i>';
		else
			$event_type = '<i class="fas fa-file-alt text-warning me-1"></i>';

		$actions = ($access3 && $cur_info['user_id'] == $User->get('id')) ? '<span class="float-end"><i class="fas fa-edit" onclick="editEvent('.$cur_info['id'].')" data-bs-toggle="modal" data-bs-target="#modalWindow"></i><span>' : '<span class="float-end text-muted">'.html_encode($cur_info['realname']).'<span>';

?>
		<tr>
			<td><p><?php echo format_date($cur_info['datetime_created'], 'Y-m-d H:i') ?></p></td>
			<td>
				<span><?php echo $event_type ?></span>
				<span><?php echo html_encode($cur_info['message']) ?></span>
				<span class="float-end"><?php echo $event_status ?></span>
			</td>
			<td><?php echo $actions ?></td>
		</tr>
<?php
	}
}
?>
	</tbody>
</table>

<div class="modal fade" id="modalWindow" tabindex="-1" aria-labelledby="modalWindowLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method="post" accept-charset="utf-8" action="">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
				<div class="modal-header">
					<h5 class="modal-title">Add action</h5>
					<button type="button" class="btn-close bg-danger" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<!--modal_fields-->
				</div>
				<div class="modal-footer">
					<!--modal_buttons-->
				</div>
			</form>
		</div>
	</div>
</div>

<script>
function editEvent(id)
{
	var csrf_token = "<?php echo generate_form_token($URL->link('swift_events_ajax_edit_event')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('swift_events_ajax_edit_event') ?>",
		type:	"POST",
		dataType: "json",
		data: ({id:id,csrf_token:csrf_token}),
		success: function(re){
			$("#modalWindow .modal-title").empty().html(re.modal_title);
			$("#modalWindow .modal-body").empty().html(re.modal_body);
			$("#modalWindow .modal-footer").empty().html(re.modal_footer);
		},
		error: function(re){
			$("#modalWindow .modal-body").empty().html('Error: No events.');
		}
	});
}
</script>

<?php
require SITE_ROOT.'footer.php';
