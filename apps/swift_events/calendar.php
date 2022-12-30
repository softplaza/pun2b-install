<?php
/**
 * @copyright (C) 2020 SwiftProjectManager.Com, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Swift Events
 */

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

//$access = ($User->checkAccess('swift_events', 1)) ? true : false;
$access2 = ($User->checkAccess('swift_events', 2)) ? true : false;//add
//if (!$access)
//	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$search_by_type = isset($_GET['type']) ? swift_trim($_GET['type']) : 'year';
$search_by_date = isset($_GET['date']) ? swift_trim($_GET['date']) : date('Y-m-d');
$search_by_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

$SwiftEvents = new SwiftEvents;

if (isset($_POST['create_event']))
{
	$form_data = [
		'user_id'	=> $User->get('id'),
		'datetime_created' => isset($_POST['datetime_created']) ? $_POST['datetime_created'] : date('Y-m-d H:i:s'),
		'message' => isset($_POST['message']) ? swift_trim($_POST['message']) : '',
		'event_type' => isset($_POST['event_type']) ? intval($_POST['event_type']) : 0,
	];

	if ($form_data['message'] == '')
		$Core->add_error('Message cannot be empty.');

	if (empty($Core->errors))
	{
		$DBLayer->insert_values('swift_events', $form_data);

		$flash_message = 'Event was added.';
		$FlashMessenger->add_info($flash_message);
		//redirect($URL->genLink('swift_events_calendar', ['type' => 'year']), $flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['update_event']))
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

$Core->set_page_id('swift_events_calendar', 'swift_events');
require SITE_ROOT.'header.php';
?>

<style>
.card-month{width: 260px;}
</style>

<nav class="navbar search-bar mb-1">
	<form method="get" accept-charset="utf-8" action="" class="d-flex">
		<input type="hidden" name="type" value="<?=$search_by_type?>">
		<input type="hidden" name="date" value="<?=$search_by_date?>">
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
	//else if ($User->get('id') == $user_info['id'] && $search_by_user_id == 0)
	//	echo '<option value="'.$user_info['id'].'" selected>'.$user_info['realname'].'</option>';
	else
		echo '<option value="'.$user_info['id'].'">'.$user_info['realname'].'</option>';
}
?>
					</select>
				</div>
				<div class="col-md-auto">
					<button type="submit" class="btn btn-sm btn-outline-success">Search</button>
					<?php if ($access2): ?>
					<a href="#" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="editEvent(0)"><i class="fas fa-plus-circle fa-lg"></i></a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</form>
</nav>

<?php
$SwiftEvents->getEvents();

if ($search_by_type == 'month')
	$SwiftEvents->showMonth();
else // full year
	$SwiftEvents->showYear();
?>

<div id="ajax_content">
	<?php $SwiftEvents->showEvents(); ?>
</div>


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
function getEventsOfWeek(date)
{
	var user_id = <?php echo $search_by_user_id ?>;
	var csrf_token = "<?php echo generate_form_token($URL->link('swift_events_ajax_get_events')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('swift_events_ajax_get_events') ?>",
		type:	"POST",
		dataType: "json",
		data: ({date:date,user_id:user_id,csrf_token:csrf_token}),
		success: function(re){
			$("#ajax_content").empty().html(re.ajax_content);
		},
		error: function(re){
			$("#ajax_content").empty().html('Error: No events.');
		}
	});
}
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
