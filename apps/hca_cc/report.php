<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_cc', 2)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$HcaCC = new HcaCC;

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$search_by_frequency = isset($_GET['frequency']) ? intval($_GET['frequency']) : 0;
$search_by_department = isset($_GET['department']) ? intval($_GET['department']) : 0;
$search_by_month = isset($_GET['month']) ? intval($_GET['month']) : 0;
$search_by_required_by = isset($_GET['required_by']) ? intval($_GET['required_by']) : 0;
$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$search_by_sort_by = isset($_GET['sort_by']) ? intval($_GET['sort_by']) : 1;

$item_ids = [];
$search_query = $sort_query = [];
if ($search_by_frequency > 0)
	$search_query[] = 'i.frequency='.$search_by_frequency;
if ($search_by_department > 0)
	$search_query[] = 'i.department='.$search_by_department;
if ($search_by_month > 0)
	$search_query[] = 'MONTH(i.date_due)='.$search_by_month.' AND YEAR(i.date_due)='.date('Y');
if ($search_by_required_by > 0)
	$search_query[] = 'i.required_by='.$search_by_required_by;

if ($search_by_sort_by == 1)
	$sort_query[] = 'i.frequency';
else if ($search_by_sort_by == 4)
	$sort_query[] = 'i.item_name';
else if ($search_by_sort_by == 9)
	$sort_query[] = 'i.date_due';

if ($search_by_property_id > 0)
{
	$query = array(
		'SELECT'	=> 'cp.item_id, cp.property_id',
		'FROM'		=> 'hca_cc_properties AS cp',
		'WHERE'		=> 'cp.property_id='.$search_by_property_id,
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result)) {
		$item_ids[$row['item_id']] = $row['item_id'];
	}
}
if (!empty($item_ids))
	$search_query[] = 'i.id IN('.implode(',', $item_ids).')';

if (isset($_POST['update_project']))
{
	$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;

	$form_data = [
		'completed'			=> isset($_POST['completed']) ? intval($_POST['completed']) : 0,
		'date_completed'	=> isset($_POST['date_completed']) ? swift_trim($_POST['date_completed']) : '',
		'frequency'			=> isset($_POST['frequency']) ? intval($_POST['frequency']) : 0,
		'date_due'			=> isset($_POST['date_due']) ? swift_trim($_POST['date_due']) : '',
		'months_due'		=> isset($_POST['months_due']) ? swift_trim($_POST['months_due']) : '',
		'notes'				=> isset($_POST['notes']) ? swift_trim($_POST['notes']) : '',
		'last_track_id'		=> isset($_POST['last_track_id']) ? intval($_POST['last_track_id']) : 0
	];

	if ($form_data['completed'] == 1 && $form_data['date_completed'] == '')
		$Core->add_error('If project is completed please set "Completed on".');

	if ($form_data['completed'] == 1 && $form_data['frequency'] < 3 && $form_data['months_due'] == '')
		$Core->add_error('To complete the project add months.');

	if (empty($Core->errors))
	{
		$item_data = [];

		// If project completed
		if ($form_data['completed'] == 1 && $form_data['frequency'] > 0 && $form_data['date_completed'] != '')
		{
			$action_data = [
				'item_id'			=> $item_id,
				'track_id'			=> $form_data['last_track_id'],
				'time_updated'		=> time(),
				'updated_by'		=> $User->get('id'),
				'notes'				=> isset($_POST['notes']) ? swift_trim($_POST['notes']) : '',
			];
			if ($form_data['last_track_id'] > 0)
				$DBLayer->insert_values('hca_cc_actions', $action_data);

			// getting previews 'date_completed'
			$item_data['date_completed'] = $form_data['date_completed'];
			$item_data['date_last_completed'] = isset($_POST['date_last_completed']) ? swift_trim($_POST['date_last_completed']) : '';
			$item_data['date_due'] = $HcaCC->genDueDate($form_data);

			// New track
			$item_data['last_track_id'] = $DBLayer->insert_values('hca_cc_tracks', ['item_id' => $item_id]);
		}
		// If just update
		else
		{
			if ($form_data['last_track_id'] == 0)
				$item_data['last_track_id'] = $DBLayer->insert_values('hca_cc_tracks', ['item_id' => $item_id]);
			else
				$item_data['last_track_id'] = $form_data['last_track_id'];

			$action_data = [
				'item_id'			=> $item_id,
				'track_id'			=> $item_data['last_track_id'],
				'time_updated'		=> time(),
				'updated_by'		=> $User->get('id'),
				'notes'				=> isset($_POST['notes']) ? swift_trim($_POST['notes']) : '',
			];
			$DBLayer->insert_values('hca_cc_actions', $action_data);
		}

		$DBLayer->update('hca_cc_items', $item_data, $item_id);

		// Add flash message
		$flash_message = 'Tracking of item has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['update_action']))
{
	$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
	$action_id = isset($_POST['action_id']) ? intval($_POST['action_id']) : 0;
	$track_id = isset($_POST['track_id']) ? intval($_POST['track_id']) : 0;
	$notes = isset($_POST['notes']) ? swift_trim($_POST['notes']) : '';
	$time_updated = isset($_POST['time_updated']) ? strtotime($_POST['time_updated']) : time();
	
	if ($notes == '')
		$Core->add_error('Event message can not by empty. Write your message.');
	//if ($date_time == '')
	//	$Core->add_error('Incorrect Date. Set the date for the event.');
	
	if (empty($Core->errors))
	{
		if ($track_id == 0)
		{
			$track_data = [
				'item_id'		=> $item_id,
				'time_updated'	=> $notes,
				'updated_by'	=> $User->get('id')
			];
			$track_id = $DBLayer->insert('hca_cc_tracks', $track_data);

			$DBLayer->update('hca_cc_items', ['last_track_id' => $track_id], $item_id);
		}

		if ($action_id > 0)
		{
			$form_data = [
				'time_updated'	=> $time_updated,
				'updated_by'	=> $User->get('id'),
				'notes'			=> $notes,
			];
			$DBLayer->update('hca_cc_actions', $form_data, $action_id);
		}
		else
		{
			$action_data = [
				'item_id' 		=> $item_id,
				'time_updated' 	=> $time_updated,
				'updated_by'	=> $User->get('id'),
				'notes'			=> $notes,
				'track_id'		=> $track_id
			];
			$DBLayer->insert('hca_cc_actions', $action_data);
		}

		$flash_message = 'Action has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['delete_action']))
{
	$action_id = isset($_POST['action_id']) ? intval($_POST['action_id']) : 0;

	$DBLayer->delete('hca_cc_actions', $action_id);

	$flash_message = 'Action has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

$query = array(
	'SELECT'	=> 'p.*',
	'FROM'		=> 'sm_property_db AS p',
	//'WHERE'		=> 'p.id!=105 AND p.id!=113 AND p.id!=115 AND p.id!=116',
	'ORDER BY'	=> 'p.pro_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $row;
}

$query = array(
	'SELECT'	=> 'u.id, u.group_id, u.username, u.realname, u.email, g.g_id, g.g_title',
	'FROM'		=> 'groups AS g',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'g.g_id=u.group_id'
		)
	),
	'WHERE'		=> 'group_id > 2',
	'ORDER BY'	=> 'g.g_id, u.realname',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users_info = [];
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$users_info[] = $fetch_assoc;
}

$query = [
	'SELECT'	=> 'i.*, pt.pro_name, u.realname',//pj.date_last_completed, pj.date_completed, pj.notes
	'FROM'		=> 'hca_cc_items AS i',
	'JOINS'		=> [
		[
			'LEFT JOIN'		=> 'hca_cc_items_tracking AS pj',
			'ON'			=> 'pj.id=i.last_tracking_id'
		],	
		[
			'LEFT JOIN'		=> 'users AS u',
			'ON'			=> 'u.id=i.action_owner'
		],	
		[
			'LEFT JOIN'		=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=i.property_id'
		],
		//
	],
	//'ORDER BY'	=> 'i.date_due',
];
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
if (!empty($sort_query)) $query['ORDER BY'] = implode(', ', $sort_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $main_ids = [];
while ($row = $DBLayer->fetch_assoc($result))
{
	$main_info[] = $row;
	$main_ids[] = $row['id'];
}

$follow_up_info = $hca_cc_owners = $hca_cc_properties = [];

if (!empty($main_ids))
{
	$query = array(
		'SELECT'	=> 'a.*',
		'FROM'		=> 'hca_cc_actions AS a',
		'WHERE'		=> 'a.item_id IN('.implode(',', $main_ids).')',// replace on tracks
		'ORDER BY'	=> 'a.time_updated'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result)) {
		$follow_up_info[] = $row;
	}

	$query = [
		'SELECT'	=> 'o.*, u.realname',
		'FROM'		=> 'hca_cc_owners AS o',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'users AS u',
				'ON'			=> 'u.id=o.user_id'
			],
		],
		'WHERE'		=> 'o.item_id IN ('.implode(',', $main_ids).')'
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		$hca_cc_owners[] = $fetch_assoc;
	}

	$query = [
		'SELECT'	=> 'p.*, p2.pro_name',
		'FROM'		=> 'hca_cc_properties AS p',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'sm_property_db AS p2',
				'ON'			=> 'p2.id=p.property_id'
			]
		],
		'WHERE'		=> 'p.item_id IN ('.implode(',', $main_ids).')'
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		$hca_cc_properties[] = $fetch_assoc;
	}
}


$Core->set_page_title('Tracking of items');
$Core->set_page_id('hca_cc_projects', 'hca_cc');
require SITE_ROOT.'header.php';
?>

<style>
.popover {
	color: #664d03;
    background-color: #fff3cd;
    border-color: #ffecb5;
}
</style>

<nav class="navbar search-bar mb-3">
	<form method="get" accept-charset="utf-8" action="" class="d-flex">
		<div class="container-fluid justify-content-between">
			<div class="row">
				<div class="col-md-auto pe-0 mb-1">
					<select name="frequency" class="form-select-sm">
						<option value="0">Frequency</option>
<?php
foreach($HcaCC->frequency as $key => $value)
{
	if ($search_by_frequency == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.html_encode($value).'</option>'."\n";
	else
		echo '<option value="'.$key.'">'.html_encode($value).'</option>';
}
?>
					</select>
				</div>
				<div class="col-md-auto pe-0 mb-1">
					<select name="department" class="form-select-sm">
						<option value="0">Departments</option>
<?php
foreach($HcaCC->departments as $key => $value)
{
	if ($search_by_department == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.html_encode($value).'</option>'."\n";
	else
		echo '<option value="'.$key.'">'.html_encode($value).'</option>';
}
?>
					</select>
				</div>
				<div class="col-md-auto pe-0 mb-1">
					<select name="month" class="form-select-sm">
						<option value="0">Months</option>
<?php
foreach($HcaCC->months as $key => $value)
{
	if ($search_by_month == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.html_encode($value).'</option>'."\n";
	else
		echo '<option value="'.$key.'">'.html_encode($value).'</option>';
}
?>
					</select>
				</div>
				<div class="col-md-auto pe-0 mb-1">
					<select name="required_by" class="form-select-sm">
						<option value="0">Required by</option>
<?php
foreach($HcaCC->required_by as $key => $value)
{
	if ($search_by_required_by == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.html_encode($value).'</option>'."\n";
	else
		echo '<option value="'.$key.'">'.html_encode($value).'</option>';
}
?>
					</select>
				</div>
				<div class="col-md-auto pe-0 mb-1">
					<select name="property_id" class="form-select-sm">
						<option value="0">Properties</option>
<?php
foreach($property_info as $cur_info)
{
	if ($search_by_property_id == $cur_info['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_info['id'].'" selected>'.html_encode($cur_info['pro_name']).'</option>'."\n";
	else
		echo '<option value="'.$cur_info['id'].'">'.html_encode($cur_info['pro_name']).'</option>';
}
?>
					</select>
				</div>

				<div class="col-md-auto pe-0 mb-1">
					<select name="sort_by" class="form-select-sm">
						<option value="0">Sort by</option>
<?php
$sort_by_arr = [
	1 => 'Frequency (1-9)',
	4 => 'Item Name (A-Z)',
	9 => 'Due Date (1-9)',
];

foreach($sort_by_arr as $key => $value)
{
	if ($search_by_sort_by == $key)
		echo "\t\t\t\t\t\t\t".'<option value="'.$key.'" selected>'.$value.'</option>'."\n";
	else
		echo '<option value="'.$key.'">'.$value.'</option>';
}
?>
					</select>
				</div>

				<div class="col-md-auto">
					<button type="submit" class="btn btn-sm btn-outline-success">Search</button>
					<a href="<?php echo $URL->link('hca_cc_report') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
				</div>
			</div>
		</div>
	</form>
</nav>

<?php
if (!empty($main_info)) 
{
?>

<div class="card-header">
	<h6 class="card-title mb-0">Items Tracking (<?php echo count($main_info) ?>)</h6>
</div>
<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th class="min-w-10">Frequency</th>
			<th>Department</th>
			<th>Action Owner</th>
			<th class="min-w-10">Item/Description</th>
			<th>Property</th>
			<th class="min-w-10">Action</th>
			<th>Required By</th>
			<th class="min-w-6">Date Last Completed</th>
			<th class="min-w-6">Date Completed</th>
			<th class="min-w-6">Due Date</th>
		</tr>
	</thead>
	<tbody>

<?php
	foreach($main_info as $cur_info)
	{
		$Core->add_dropdown_item('<a href="#" onclick="editItem('.$cur_info['id'].')" data-bs-toggle="modal" data-bs-target="#modalWindow"><i class="far fa-check-circle"></i> Complete</a>');
		$Core->add_dropdown_item('<a href="'.$URL->link('hca_cc_item', $cur_info['id']).'"><i class="fas fa-edit"></i> Edit item</a>');

		$item_desc = ($cur_info['item_desc'] != '') ? '<p class="float-end"><a tabindex="0" class="text-info" role="button" data-bs-toggle="popover" data-bs-trigger="focus" data-bs-content="'.html_encode($cur_info['item_desc']).'"><i class="fas fa-info-circle"></i></a></p>' : '';

		$follow_up_dates = [];
		foreach ($follow_up_info as $cur_follow_up)
		{
			if ($cur_info['id'] == $cur_follow_up['item_id'] && $cur_info['last_track_id'] == $cur_follow_up['track_id'])
			{
				//$status = (format_date($cur_follow_up['time_updated'], 'Y-m-d') == date('Y-m-d')) ? 'alert-danger' : 'alert-info';
				$follow_up_dates[] = '<div class="alert-info mb-1 p-1">';
				$follow_up_dates[] = '<span class="float-end" onclick="getAction('.$cur_info['id'].', '.$cur_follow_up['id'].')"><i class="fas fa-edit fa-lg" data-bs-toggle="modal" data-bs-target="#modalWindow"></i></span>';
				$follow_up_dates[] = '<ins class="text-muted">'.format_time($cur_follow_up['time_updated']).'</ins>';
				$follow_up_dates[] = '<p>'.html_encode($cur_follow_up['notes']).'</p>';
				$follow_up_dates[] = '</div>';
			}
		}

		$frequency = isset($HcaCC->frequency[$cur_info['frequency']]) ? $HcaCC->frequency[$cur_info['frequency']] : 'n/a';
		$department = ($cur_info['department'] > 0) ? $HcaCC->departments[$cur_info['department']] : '';
		$required_by = ($cur_info['required_by'] > 0) ? $HcaCC->required_by[$cur_info['required_by']] : '';

		$owners = $HcaCC->getOwners($cur_info['id'], $hca_cc_owners);
		$properties = $HcaCC->getProperties($cur_info['id'], $hca_cc_properties);
		$months = ($cur_info['months_due'] != '') ? '<p>'.$HcaCC->getMonths($cur_info['months_due']).'</p>' : '';


		$time_now = time();
		$date_due_time = strtotime($cur_info['date_due']);
		$next_month = $date_due_time - 2592000;

		$last_notified = ($cur_info['last_notified'] > 0) ? '<p class="float-end"><a tabindex="0" class="text-info" role="button" data-bs-toggle="popover" data-bs-trigger="focus" data-bs-content="Last notified on '.format_time($cur_info['last_notified']).'"><i class="fas fa-info-circle"></i></a></p>' : '';

		if ($time_now > $date_due_time)
			$status = '<div class="mb-1 p-1 alert-danger"><p>'.format_date($cur_info['date_due'], 'F Y').'</p></div>';
		else if ($time_now > $next_month)
			$status = '<div class="mb-1 p-1 alert-warning"><p>'.format_date($cur_info['date_due'], 'F Y').'</p></div>';	
		else
			$status = '<div class="mb-1 p-1 alert-success"><p>'.format_date($cur_info['date_due'], 'F Y').'</p></div>';
?>
		<tr>
			<td>
				<p class="fw-bold"><?php echo html_encode($frequency) ?></p>
				<?php echo $months ?>
				<span class="float-end"><?php echo $Core->get_dropdown_menu($cur_info['id']) ?></span>
			</td>
			<td class="ta-center"><?php echo html_encode($department) ?></td>
			<td class="ta-center"><?php echo $owners ?></td>
			<td class="">
				<p class="fw-bold text-primary"><?php echo html_encode($cur_info['item_name']) ?></p>
				<?php echo $item_desc ?>
			</td>
			<td class="ta-center"><?php echo $properties ?></td>
			<td>
				<?php echo implode("\n", $follow_up_dates) ?>
				<p class="float-end"><i class="fas fa-plus-circle fa-lg text-primary" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="addAction(<?php echo $cur_info['id'] ?>)"></i></p>
			</td>
			<td class="ta-center"><?php echo $required_by ?></td>
			<td class="ta-center"><?php echo format_date($cur_info['date_last_completed'], 'F Y') ?></td>
			<td class="ta-center"><?php echo format_date($cur_info['date_completed'], 'F Y') ?></td>
			<td class="ta-center">
				<?php echo $last_notified ?>
				<?php echo $status ?>
				
			</td>
		</tr>
<?php
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
					<h5 class="modal-title">Edit item</h5>
					<button type="button" class="btn-close bg-danger" data-bs-dismiss="modal" aria-label="Close" onclick="closeModalWindow()"></button>
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
function addAction(item_id){
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_cc_ajax_get_action')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_cc_ajax_get_action') ?>",
		type:	"POST",
		dataType: "json",
		data: ({item_id:item_id,csrf_token:csrf_token}),
		success: function(re){
			$('.modal .modal-title').empty().html(re.modal_title);
			$('.modal .modal-body').empty().html(re.modal_body);
			$('.modal .modal-footer').empty().html(re.modal_footer);
		},
		error: function(re){
			$(".modal .alert").css('display', 'block');
		}
	});
}
function getAction(item_id,id)
{
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_cc_ajax_get_action')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_cc_ajax_get_action') ?>",
		type:	"POST",
		dataType: "json",
		data: ({item_id:item_id,id:id,csrf_token:csrf_token}),
		success: function(re){
			$('.modal .modal-title').empty().html(re.modal_title);
			$('.modal .modal-body').empty().html(re.modal_body);
			$('.modal .modal-footer').empty().html(re.modal_footer);
		},
		error: function(re){
			$(".modal .alert").css('display', 'block');
		}
	});
}
document.addEventListener('DOMContentLoaded', function()
{
	var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
	var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
		return new bootstrap.Popover(popoverTriggerEl)
	})

}, false);

function editItem(id) {
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_cc_ajax_get_item')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_cc_ajax_get_item') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({id:id,csrf_token:csrf_token}),
		success: function(re){
			$('.modal .modal-title').empty().html(re.modal_title);
			$('.modal .modal-body').empty().html(re.modal_body);
			$('.modal .modal-footer').empty().html(re.modal_footer);
		},
		error: function(re){
			$('.msg-section').empty().html('<div class="alert alert-danger" role="alert">Error: No data received.</div>');
		}
	});
}
function closeModalWindow(){
	$('.modal .modal-title').empty().html('');
		$('.modal .modal-body').empty().html('');
		$('.modal .modal-footer').empty().html('');
}
function showField(id,v)
{
	if (v == 1)
		$('#'+id).css('display', 'block');
	else
		$('#'+id).css('display', 'none');
}
</script>

<?php
}
else
	echo '<div class="alert alert-warning my-3" role="alert">You have no items on this page or not found within your search criteria.</div>';

require SITE_ROOT.'footer.php';
