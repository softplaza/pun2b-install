<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_hvac_inspections', 4)) ? true : false;
$access11 = ($User->checkAccess('hca_hvac_inspections', 11)) ? true : false;
$access12 = ($User->checkAccess('hca_hvac_inspections', 12)) ? true : false;
$access13 = ($User->checkAccess('hca_hvac_inspections', 13)) ? true : false;
$access15 = ($User->checkAccess('hca_hvac_inspections', 15)) ? true : false; // Reassign
$access20 = ($User->checkAccess('hca_hvac_inspections', 20)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$search_by_unit_number = isset($_GET['unit_number']) ? swift_trim($_GET['unit_number']) : '';
$search_by_date = isset($_GET['date']) ? swift_trim($_GET['date']) : '';
$search_by_status = isset($_GET['status']) ? intval($_GET['status']) : 0;
$search_by_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$search_by_key_word = isset($_GET['key_word']) ? swift_trim($_GET['key_word']) : '';
$search_by_appendixb = isset($_GET['appendixb']) ? intval($_GET['appendixb']) : 0;

$HcaHVACInspections = new HcaHVACInspections;

if (isset($_POST['reassign']))
{
	$checklist_id = intval($_POST['checklist_id']);
	$form_data = [
		'owned_by' => isset($_POST['owned_by']) ? intval($_POST['owned_by']) : 0,
		//'completed_by' => intval($_POST['owned_by'])
	];

	if ($form_data['owned_by'] == 0)
		$Core->add_error('Select a new owner from dropdown list.');

	if (empty($Core->errors))
	{
		$DBLayer->update('hca_hvac_inspections_checklist', $form_data, $checklist_id);

		$query = array(
			'SELECT'	=> 'u.realname, u.email',
			'FROM'		=> 'users AS u',
			'WHERE'		=> 'u.id='.$form_data['owned_by'],
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$user_info = $DBLayer->fetch_assoc($result);

		$mail_message = [];
		$mail_message[] = 'The Work Order #'.$checklist_id.' has been reassigned to '.html_encode($user_info['realname']);
		$mail_message[] = 'Reassigned by: '.html_encode($User->get('realname'));
		$mail_message[] = 'To complete this Work Order follow the link below:';
		$mail_message[] = $URL->link('hca_hvac_inspections_work_order', $checklist_id);

		$SwiftMailer = new SwiftMailer;
		//$SwiftMailer->isHTML();
		$SwiftMailer->send($user_info['email'], 'Plumbing Inspections', implode("\n\n", $mail_message));

		// Add flash message
		$flash_message = 'Work Order was reassigned to '.$user_info['realname'];

		$action_data = [
			'checklist_id'			=> $checklist_id,
			'submitted_by'			=> $User->get('id'),
			'time_submitted'		=> time(),
			'action'				=> $flash_message
		];
		$DBLayer->insert_values('hca_hvac_inspections_actions', $action_data);

		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_hvac_inspections_inspections', $checklist_id).get_to_str(), $flash_message);
	}
}

else if (isset($_POST['send_email']))
{
	$checklist_id = intval($_POST['checklist_id']);
	$emails = isset($_POST['emails']) ? swift_trim($_POST['emails']) : '';
	$mail_message = isset($_POST['mail_message']) ? swift_trim($_POST['mail_message']) : '';
	
	if ($emails == '')
		$Core->add_error('Email field can not be empty. Insert email of recipient.');
	
	if (empty($Core->errors))
	{
		$SwiftMailer = new SwiftMailer;
		//$SwiftMailer->isHTML();
		$SwiftMailer->send($emails, 'Plumbing Inspections', $mail_message);

		$flash_message = 'Email has been sent to: '.$emails;

		$action_data = [
			'checklist_id'			=> $checklist_id,
			'submitted_by'			=> $User->get('id'),
			'time_submitted'		=> time(),
			'action'				=> $flash_message
		];
		$DBLayer->insert_values('hca_hvac_inspections_actions', $action_data);

		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

$search_query = [];
//$search_query[] = 'c.owned_by = 0';

if ($User->get('group_id') == $Config->get('o_hca_fs_maintenance'))
	$search_query[] = '(c.owned_by='.$User->get('id').' OR c.inspected_by='.$User->get('id').' OR c.completed_by='.$User->get('id').')';

if ($User->get('property_access') != '' && $User->get('property_access') != 0)
{
	$property_ids = explode(',', $User->get('property_access'));
	$search_query[] = 'c.property_id IN ('.implode(',', $property_ids).')';
}

if ($search_by_property_id > 0)
	$search_query[] = 'c.property_id='.$search_by_property_id;

if ($search_by_unit_number != '')
	$search_query[] = 'un.unit_number=\''.$DBLayer->escape($search_by_unit_number).'\'';

if ($search_by_date != '')
	$search_query[] = '(DATE(c.datetime_inspection_start)=\''.$DBLayer->escape($search_by_date).'\' OR DATE(c.datetime_completion_end)=\''.$DBLayer->escape($search_by_date).'\')';

// owned_by, inspected_by, completed_by, updated_by
if ($search_by_user_id > 0)
	$search_query[] = '(c.owned_by='.$search_by_user_id.' OR c.inspected_by='.$search_by_user_id.' OR c.completed_by='.$search_by_user_id.' OR c.updated_by='.$search_by_user_id.')';

if ($search_by_status > 0)
{
	if ($search_by_status == 1) // pending inspections
		$search_query[] = 'c.inspection_completed=1';
	else if ($search_by_status == 2) // pending WO
		$search_query[] = 'c.work_order_completed=1 AND c.inspection_completed=2 AND c.num_problem > 0';
	else if ($search_by_status == 3)
		$search_query[] = 'c.inspection_completed=2 AND c.work_order_completed=2';
}

if ($search_by_key_word != '') {
	$search_by_key_word2 = '%'.$search_by_key_word.'%';
	$search_query[] = '(c.work_order_comment LIKE \''.$DBLayer->escape($search_by_key_word2).'\')';
}

if ($search_by_appendixb == 1)
	$search_query[] = 'c.appendixb=1';

$query = [
	'SELECT'	=> 'COUNT(c.id)',
	'FROM'		=> 'hca_hvac_inspections_checklist as c',
	'JOINS'		=> [
		[
			'LEFT JOIN'		=> 'sm_property_units AS un',//used for search by // why LEFT?
			'ON'			=> 'un.id=c.unit_id'
		],
	],
];
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

// 1 - Pendind 2 - Completed Work Prders
$query = [
	'SELECT'	=> 'c.*, p.pro_name, un.unit_number, u1.realname AS owner_name, u2.realname AS inspected_name, u3.realname AS completed_name, u4.realname AS updated_name, u5.realname AS started_name',
	'FROM'		=> 'hca_hvac_inspections_checklist as c',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'sm_property_db AS p',
			'ON'			=> 'p.id=c.property_id'
		],
		[
			'INNER JOIN'	=> 'sm_property_units AS un',
			'ON'			=> 'un.id=c.unit_id'
		],
		[
			'LEFT JOIN'		=> 'users AS u1',
			'ON'			=> 'u1.id=c.owned_by'
		],
		[
			'LEFT JOIN'		=> 'users AS u2',
			'ON'			=> 'u2.id=c.inspected_by'
		],
		[
			'LEFT JOIN'		=> 'users AS u3',
			'ON'			=> 'u3.id=c.completed_by'
		],
		[
			'LEFT JOIN'		=> 'users AS u4',
			'ON'			=> 'u4.id=c.updated_by'
		],
		[
			'LEFT JOIN'		=> 'users AS u5',
			'ON'			=> 'u5.id=c.started_by'
		],
	],
	//'ORDER BY'	=> 'c.completed, c.status, p.pro_name, LENGTH(un.unit_number), un.unit_number',
	'ORDER BY'	=> 'c.inspection_completed, p.pro_name, LENGTH(un.unit_number), un.unit_number', //mycolumn=0 desc, mycolumn desc
	'LIMIT'		=> $PagesNavigator->limit()
];
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $projects_ids = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$main_info[] = $row;
	$projects_ids[] = $row['id'];
}
$PagesNavigator->num_items($main_info);

$query = array(
	'SELECT'	=> 'p.*',
	'FROM'		=> 'sm_property_db AS p',
	'WHERE'		=> 'p.id!=105 AND p.id!=113 AND p.id!=115 AND p.id!=116',
	'ORDER BY'	=> 'p.pro_name'
);
if ($User->get('property_access') != '' && $User->get('property_access') != 0)
{
	$property_ids = explode(',', $User->get('property_access'));
	$query['WHERE'] .= ' AND p.id IN ('.implode(',', $property_ids).')';
}
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = [];
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $fetch_assoc;
}

$query = array(
	'SELECT'	=> 'u.id, u.realname',
	'FROM'		=> 'users AS u',
	'WHERE'		=> 'u.group_id=3 OR u.group_id=9',
	//'WHERE'		=> 'u.group_id='.$Config->get('o_hca_fs_maintenance'),
	'ORDER BY'	=> 'u.realname'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users_info = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$users_info[] = $row;
}

// Serch by: 1 -Property 2 - Unit 3 - Status (pending, completed)
$Core->set_page_id('hca_hvac_inspections_inspections', 'hca_hvac_inspections');
require SITE_ROOT.'header.php';
?>

<nav class="navbar search-bar">
	<form method="get" accept-charset="utf-8" action="" class="d-flex">
		<div class="container-fluid justify-content-between">
			<div class="row">
				<div class="col-md-auto pe-0 mb-1">
					<select name="property_id" class="form-select-sm">
						<option value="">All Properties</option>
<?php
foreach ($property_info as $val)
{
	if ($search_by_property_id == $val['id'])
		echo '<option value="'.$val['id'].'" selected>'.$val['pro_name'].'</option>';
	else
		echo '<option value="'.$val['id'].'">'.$val['pro_name'].'</option>';
}
?>
					</select>
				</div>
				<div class="col-md-auto pe-0 mb-1">
					<input name="unit_number" type="text" value="<?php echo isset($_GET['unit_number']) ? $_GET['unit_number'] : '' ?>" placeholder="Unit #" class="form-control-sm" size="5">
				</div>
				<div class="col-md-auto pe-0 mb-1">
					<select name="user_id" class="form-select-sm">
						<option value="0">All technicians</option>
<?php
foreach ($users_info as $user_info)
{
	if ($search_by_user_id == $user_info['id'] || $User->get('id') == $user_info['id'])
		echo '<option value="'.$user_info['id'].'" selected>'.$user_info['realname'].'</option>';
	else
		echo '<option value="'.$user_info['id'].'">'.$user_info['realname'].'</option>';
}
?>
					</select>
				</div>
				<div class="col-md-auto pe-0 mb-1">
					<input name="date" type="date" value="<?php echo $search_by_date ?>" class="form-control-sm">
				</div>
				<div class="col-md-auto pe-0 mb-1">
					<div class="mb-0">
						<input name="appendixb" type="checkbox" value="1" <?php echo ($search_by_appendixb == 1) ? 'checked' : '' ?> class="form-check-input" id="fld_appendixb">  
						<label class="form-check-label" for="fld_appendixb">Appendix-B</label>
					</div>
				</div>
				<div class="col-md-auto">
					<button type="submit" class="btn btn-sm btn-outline-success">Search</button>
					<a href="<?php echo $URL->link('hca_hvac_inspections_inspections', 0) ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
				</div>
			</div>
		</div>
	</form>
</nav>

<form method="post" accept-charset="utf-8" action="">
	<table class="table table-striped table-bordered">
		<thead>
			<tr>
				<th class="min-w-10">Property</th>
				<th class="min-w-10">Inspected by</th>
				<th class="min-w-10">Completed by</th>
				<th class="">Comments</th>
			</tr>
		</thead>
		<tbody>
<?php
$owner_id = $User->get('id');

if (!empty($main_info)) 
{
	$uploader_info = $hca_hvac_inspections_checklist_items = [];
	$query = array(
		'SELECT'	=> 'id, table_id',
		'FROM'		=> 'sm_uploader',
		'WHERE'		=> 'table_id IN ('.implode(',', $projects_ids).') AND table_name=\'hca_hvac_inspections_checklist\''
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result)) {
		$uploader_info[] = $row['table_id'];
	}

	$query = array(
		'SELECT'	=> 'ci.*, i.item_name, i.req_appendixb',
		'FROM'		=> 'hca_hvac_inspections_checklist_items AS ci',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'hca_hvac_inspections_items AS i',
				'ON'			=> 'i.id=ci.item_id'
			],
		],
		//'WHERE'		=> 'ci.checklist_id IN ('.implode(',', $projects_ids).') AND (ci.job_type=0 OR ci.job_type=4)'
		'WHERE'		=> 'ci.checklist_id IN ('.implode(',', $projects_ids).')'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result)) {
		$hca_hvac_inspections_checklist_items[] = $row;
	}

	foreach($main_info as $cur_info)
	{
		$td1 = [];
		if ($access11)
			$Core->add_dropdown_item('<a href="'.$URL->link('hca_hvac_inspections_checklist', $cur_info['id']).'"><i class="fas fa-edit"></i> CheckList</a>');

		if ($access20)
			$Core->add_dropdown_item('<a href="'.$URL->link('hca_hvac_inspections_checklist2', $cur_info['id']).'"><i class="fas fa-edit"></i> CheckList 2</a>');

		if ($access12 && $cur_info['work_order_completed'] > 0) //  && $cur_info['num_problem'] > 0
			$Core->add_dropdown_item('<a href="'.$URL->link('hca_hvac_inspections_work_order', $cur_info['id']).'"><i class="fas fa-file-alt"></i> Work Order</a>');

		$Core->add_dropdown_item('<a href="'.$URL->link('hca_hvac_inspections_files', $cur_info['id']).'"><i class="fas fa-file-upload"></i> Upload Files</a>');

		if ($access15 || ($owner_id == $cur_info['owned_by']))
		{
			$Core->add_dropdown_item('<a href="#" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="reassignProject('.$cur_info['id'].')"><i class="fas fa-share"></i> Reassign to</a>');

			//$Core->add_dropdown_item('<a href="#" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="getPropertyInfo('.$cur_info['id'].')"><i class="fas fa-at"></i> Send Email</a>');
		}

		if ($cur_info['inspection_completed'] == 2)
			$td1[] = '<p class="badge bg-success mb-1">Completed</p>';
		else
			$td1[] = '<p class="badge bg-primary mb-1">Pending</p>';

		if (in_array($cur_info['id'], $uploader_info))
			$td1[] = '<p><a href="'.$URL->link('hca_hvac_inspections_files', $cur_info['id']).'" class="btn btn-sm btn-outline-success">Files</a></p>';

		$list_of_problems = [];
		$req_appendixb = false;
		if (!empty($hca_hvac_inspections_checklist_items))
		{
			foreach($hca_hvac_inspections_checklist_items as $checklist_items)
			{
				if ($cur_info['id'] == $checklist_items['checklist_id'])
				{
					$problem_names = $HcaHVACInspections->getItemProblems($checklist_items['problem_ids']);
					$list_of_problems[] = '<p class="text-primary">'.$checklist_items['item_name'].($problem_names != '' ? ' (<span class="text-danger">'.$problem_names.'</span>)' : '').'</p>';

					if ($checklist_items['req_appendixb'] == 1)
						$req_appendixb = true;
				}
			}
/*
			if ($access12 && $cur_info['inspection_completed'] == 2 && $cur_info['num_problem'] > 0)
			{
				if ($req_appendixb)
					$Core->add_dropdown_item('<a href="'.$URL->link('hca_hvac_inspections_appendixb', $cur_info['id']).'"><i class="fas fa-file-pdf"></i> Add Appendix-B</a>');
			}
*/
		}

		$datetime_completion_end = (strtotime($cur_info['datetime_completion_end']) > 0) ? ' - '.format_date($cur_info['datetime_completion_end'], 'H:i') : '';

		$started_name = (format_date($cur_info['datetime_completion_start']) != '') ? html_encode($cur_info['started_name']) : '';
		//$completed_name = (format_date($cur_info['datetime_completion_end']) > '') ? html_encode($cur_info['completed_name']) : '';

		$owned_by = ($cur_info['owner_name'] != '' && $cur_info['completed_by'] != $cur_info['owned_by']) ? '<p>Reassigned to: <span class="fw-bold">'.html_encode($cur_info['owner_name']).'</span></p>' : '';

		$search_str = '<span class="fw-bold text-danger">'.$search_by_key_word.'</span>';
		$work_order_comment = ($search_by_key_word != '') ? preg_replace('/'.$search_by_key_word.'/i', $search_str, $cur_info['work_order_comment']) : html_encode($cur_info['work_order_comment']);
?>
			<tr id="row<?php echo $cur_info['id'] ?>" class="<?php echo ($cur_info['id'] == $id ? 'anchor' : '') ?>">
				<td>
					<p class="fw-bold"><?php echo html_encode($cur_info['pro_name']) ?>, <?php echo $cur_info['unit_number'] ?></p>
					<span class="float-start"><?php echo implode("\n", $td1) ?></span>
					<span class="float-end"><?php echo $Core->get_dropdown_menu($cur_info['id']) ?></span>
				</td>
				<td class="ta-center">
					<p class="fw-bold"><?php echo html_encode($cur_info['inspected_name']) ?></p>
					<p><?php echo format_date($cur_info['datetime_inspection_start'], 'n/j/Y H:i') ?></p>
				</td>
				<td class="ta-center">
					<p class="fw-bold"><?php echo html_encode($cur_info['completed_name']) ?></p>
					<p><?php echo format_date($cur_info['datetime_inspection_end'], 'n/j/Y H:i') ?></p>
				</td>
				<td class=""><p><?php echo $work_order_comment ?></p></td>
			</tr>
<?php
	}
}
?>
		</tbody>
	</table>
</form>

<?php
if (empty($main_info)) 
	echo '<div class="alert alert-warning my-3" role="alert">You have no items on this page or not found within your search criteria.</div>';
?>

<div class="modal fade" id="modalWindow" tabindex="-1" aria-labelledby="modalWindowLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method="post" accept-charset="utf-8" action="">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
				<div class="modal-header">
					<h5 class="modal-title">Send Email</h5>
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
function reassignProject(id) {
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_hvac_inspections_ajax_reassign_project')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_hvac_inspections_ajax_reassign_project') ?>",
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
function getPropertyInfo(id) {
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_hvac_inspections_ajax_get_property_info')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_hvac_inspections_ajax_get_property_info') ?>",
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
</script>

<?php
require SITE_ROOT.'footer.php';