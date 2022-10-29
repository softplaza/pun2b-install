<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access2 = ($User->checkAccess('hca_repipe', 2)) ? true : false;
$access4 = ($User->checkAccess('hca_repipe', 4)) ? true : false;
if (!$access4)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$search_by_unit_number = isset($_GET['unit_number']) ? swift_trim($_GET['unit_number']) : '';
$search_by_date = isset($_GET['date']) ? swift_trim($_GET['date']) : '';
$search_by_status = isset($_GET['status']) ? intval($_GET['status']) : 0;
$search_by_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$search_by_key_word = isset($_GET['key_word']) ? swift_trim($_GET['key_word']) : '';
$search_by_appendixb = isset($_GET['appendixb']) ? intval($_GET['appendixb']) : 0;

$statuses = [
	//0 => 'On Hold',
	1 => 'Pending',
	2 => 'Completed for Hot',
	3 => 'Completed for Cold',
	4 => 'Completed',
];

if (isset($_POST['add_action']))
{
	$form_data = [
		'project_id' => isset($_POST['project_id']) ? intval($_POST['project_id']) : 0,
		'date_submitted' => isset($_POST['date_submitted']) ? swift_trim($_POST['date_submitted']) : '',
		'comment' => isset($_POST['comment']) ? swift_trim($_POST['comment']) : '',
	];

	if ($form_data['date_submitted'] == '')
		$Core->add_error('Incorrect Follow-up Date. Set the date.');
	if ($form_data['comment'] == '')
		$Core->add_error('Message can not by empty. Leave your comment.');
		
	if (empty($Core->errors))
	{
		$new_id = $DBLayer->insert('hca_repipe_actions', $form_data);
		
		$flash_message = 'Folow-up #'.$new_id.' was created.';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_repipe_projects').'#row'.$form_data['project_id'], $flash_message);
	}
}

else if (isset($_POST['update_action']))
{
	$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
	$form_data = [
		'project_id' => isset($_POST['project_id']) ? intval($_POST['project_id']) : 0,
		'date_submitted' => isset($_POST['date_submitted']) ? swift_trim($_POST['date_submitted']) : '',
		'comment' => isset($_POST['comment']) ? swift_trim($_POST['comment']) : '',
	];

	if ($form_data['date_submitted'] == '')
		$Core->add_error('Incorrect Follow-up Date. Set the date.');
	if ($form_data['comment'] == '')
		$Core->add_error('Message can not by empty. Leave your comment.');
		
	if (empty($Core->errors) && $id > 0)
	{
		$DBLayer->update('hca_repipe_actions', $form_data, $id);
		
		$flash_message = 'Folow-up #'.$new_id.' was updated.';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_repipe_projects').'#id'.$project_id, $flash_message);
	}
}

else if (isset($_POST['delete_action']))
{
	$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
		
	if ($id > 0)
	{
		$DBLayer->delete('hca_repipe_actions', $id);
		
		$flash_message = 'Folow-up #'.$id.' was deleted.';
		$FlashMessenger->add_info($flash_message);
		redirect($URL->link('hca_repipe_projects').'#id'.$project_id, $flash_message);
	}
}


$search_query = [];
if ($search_by_property_id > 0)
	$search_query[] = 'pj.property_id='.$search_by_property_id;

$query = [
	'SELECT'	=> 'COUNT(pj.id)',
	'FROM'		=> 'hca_repipe_projects as pj',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'sm_property_db AS p',
			'ON'			=> 'p.id=pj.property_id'
		],
		[
			'LEFT JOIN'		=> 'sm_property_units AS un',
			'ON'			=> 'un.id=pj.unit_id'
		],
	],
];
//if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

// 1 - Pendind 2 - Completed Work Prders
$query = [
	'SELECT'	=> 'pj.*, p.pro_name, un.unit_number, u1.realname AS created_name, u2.realname AS project_manager, v1.vendor_name AS vendor_name1',
	'FROM'		=> 'hca_repipe_projects as pj',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'sm_property_db AS p',
			'ON'			=> 'p.id=pj.property_id'
		],
		[
			'INNER JOIN'	=> 'sm_property_units AS un',
			'ON'			=> 'un.id=pj.unit_id'
		],
		[
			'LEFT JOIN'		=> 'users AS u1',
			'ON'			=> 'u1.id=pj.created_by'
		],
		[
			'LEFT JOIN'		=> 'users AS u2',
			'ON'			=> 'u2.id=pj.project_manager_id'
		],
		[
			'LEFT JOIN'		=> 'sm_vendors AS v1',
			'ON'			=> 'v1.id=pj.vendor_id'
		],
	],
	'ORDER BY'	=> 'p.pro_name, LENGTH(un.unit_number), un.unit_number',
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
	//'WHERE'		=> 'u.id > 2',
	'WHERE'		=> 'u.group_id='.$Config->get('o_hca_fs_maintenance'),
	'ORDER BY'	=> 'u.realname'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$users_info = [];
while ($row = $DBLayer->fetch_assoc($result)) {
	$users_info[] = $row;
}

$uploader_info = [];
if (!empty($projects_ids))
{
	$query = array(
		'SELECT'	=> 'id, table_id',
		'FROM'		=> 'sm_uploader',
		'WHERE'		=> 'table_id IN ('.implode(',', $projects_ids).') AND table_name=\'hca_repipe_projects\''
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result)) {
		$uploader_info[] = $row['table_id'];
	}
}

// Serch by: 1 -Property 2 - Unit 3 - Status (pending, completed)
$Core->set_page_id('hca_repipe_projects', 'hca_repipe');
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
				<div class="col-md-auto pe-0 mb-1 hidden">
					<select name="user_id" class="form-select-sm">
						<option value="0">All technicians</option>
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
				<div class="col-md-auto">
					<button type="submit" class="btn btn-sm btn-outline-success">Search</button>
					<a href="<?php echo $URL->link('hca_repipe_projects') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
				</div>
			</div>
		</div>
	</form>
</nav>

<?php
if (!empty($main_info)) 
{
?>

<form method="post" accept-charset="utf-8" action="">
	<table class="table table-striped table-bordered">
		<thead>
			<tr>
				<th>Property</th>
				<th>Description</th>
				<th>Project manager</th>
				<th class="min-w-10">Follow-Up</th>
				<th>Vendor name</th>
				<th>
					<p class="text-primary">Start Date</p>
					<p class="text-success">End Date</p>
				</th>
			</tr>
		</thead>
		<tbody>

<?php

	$hca_repipe_actions = [];
	$query = array(
		'SELECT'	=> 'a.*',
		'FROM'		=> 'hca_repipe_actions AS a',
		'WHERE'		=> 'a.project_id IN('.implode(',', $projects_ids).')',
		'ORDER BY'	=> 'a.date_submitted'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		$hca_repipe_actions[] = $fetch_assoc;
	}

	foreach($main_info as $cur_info)
	{
		//$building_numbers = ($cur_info['building_numbers'] != '') ? '<p class="fw-bold">BLDG: '.html_encode($cur_info['building_numbers']).'</p>' : '';
		//$unit_numbers = ($cur_info['unit_numbers'] != '') ? '<p class="fw-bold">Units: '.html_encode($cur_info['unit_numbers']).'</p>' : '';

		if ($access2)
			$Core->add_dropdown_item('<a href="'.$URL->link('hca_repipe_project', $cur_info['id']).'"><i class="fas fa-edit"></i> Edit project</a>');

		//$Core->add_dropdown_item('<a href="'.$URL->link('hca_repipe_files', $cur_info['id']).'"><i class="fas fa-file-upload"></i> Upload Files</a>');

		$repipe_actions = [];
		if (!empty($hca_repipe_actions))
		{
			foreach($hca_repipe_actions as $cur_action)
			{
				$css = [];
				if ($cur_action['project_id'] == $cur_info['id'])
				{
					if ($cur_action['submitted_by'] == date('Y-m-d')) $css[] = 'alert-danger mb-1 p-1';

					$repipe_actions[] = '<div class="'.implode(' ', $css).'">';
					$repipe_actions[] = '<p style="float:right" onclick="getEvent('.$cur_info['id'].', '.$cur_action['id'].')" data-bs-toggle="modal" data-bs-target="#modalWindow"><i class="fas fa-edit fa-lg"></i></p>';
					$repipe_actions[] = '<p class="text-decoration-underline">'.format_date($cur_action['date_submitted'], 'Y-m-d').'</p>';
					$repipe_actions[] = '<p>'.html_encode($cur_action['comment']).'</p>';
					$repipe_actions[] = '</div>';
				}
			}
		}

		if ($cur_info['status'] == 2)
			$status = '<span class="text-primary">Completed for Hot</span>';
		else if ($cur_info['status'] == 3)
			$status = '<span class="text-primary">Completed for Hot and Cold</span>';
		else if ($cur_info['status'] == 4)
			$status = '<span class="text-success">Completed</span>';
		else
			$status = '<span class="text-warning">Pending</span>';
?>
			<tr id="row<?php echo $cur_info['id'] ?>" class="<?php echo ($cur_info['id'] == $id ? 'anchor' : '') ?>">
				<td>
					<p class="fw-bold"><?php echo html_encode($cur_info['pro_name']) ?></p>
					<p class="fw-bold"><?php echo html_encode($cur_info['unit_number']) ?></p>
					<p class="fw-bold"><?php echo $status ?></p>
					<span class="float-end"><?php echo $Core->get_dropdown_menu($cur_info['id']) ?></span>
				</td>
				<td class=""><?php echo html_encode($cur_info['project_description']) ?></td>
				<td class="">
					<p class="fw-bold"><?php echo html_encode($cur_info['project_manager']) ?></p>
					<p class="text-muted"><?php echo format_time($cur_info['created_time'], 1) ?></p>
				</td>
				<td class="">
					<?php echo implode("\n", $repipe_actions) ?>
					<p style="float:right" onclick="getEvent(<?php echo $cur_info['id'] ?>, 0)" data-bs-toggle="modal" data-bs-target="#modalWindow"><i class="fas fa-plus-circle fa-lg"></i></p>
				</td>
				<td class="">
					<p class="fw-bold"><?php echo html_encode($cur_info['vendor_name1']) ?></p>
					<p class=""><?php echo html_encode($cur_info['vendor_comment']) ?></p>
				</td>
				<td class="ta-center">
					<p class="fw-bold text-primary"><?php echo format_date($cur_info['date_start'], 'n/j/Y') ?></p>
					<p class="fw-bold text-success"><?php echo format_date($cur_info['date_end'], 'n/j/Y') ?></p>
				</td>
			</tr>
<?php
	}
?>
		</tbody>
	</table>
</form>

<?php
}
else
	echo '<div class="alert alert-warning my-3" role="alert">You have no items on this page or not found within your search criteria.</div>';
?>

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
function getEvent(project_id,id) {
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_repipe_ajax_get_followup')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_repipe_ajax_get_followup') ?>",
		type:	"POST",
		dataType: "json",
		data: ({project_id:project_id,id:id,csrf_token:csrf_token}),
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
	$('.modal .modal-body"]').val("");
	$('.modal .modal-footer"]').val("");
}
</script>

<?php
require SITE_ROOT.'footer.php';
