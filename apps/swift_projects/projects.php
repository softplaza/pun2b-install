<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$section = isset($_GET['section']) ? $_GET['section'] : 'active';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$access = ($User->checkAccess('swift_projects', 1)) ? true : false;
//if (!$access)
//	message($lang_common['No permission']);

$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$search_by_manager = isset($_GET['performed_by']) ? swift_trim($_GET['performed_by']) : '';
$search_by_unit = isset($_GET['unit_number']) ? swift_trim($_GET['unit_number']) : '';


$query = array(
	'SELECT'	=> 'COUNT(id)',
	'FROM'		=> 'swift_projects',
	'WHERE'		=> 'project_status > 0'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$query = array(
	'SELECT'	=> 'p.*, u.realname',
	'FROM'		=> 'swift_projects AS p',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'users AS u',
			'ON'			=> 'u.id=p.requested_by'
		),
	),
	'WHERE'		=> 'p.project_status=0',
//	'ORDER BY'	=> 'pt.pro_name',
	'LIMIT'		=> $PagesNavigator->limit(),
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $projects_ids = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$main_info[] = $row;
	$projects_ids[] = $row['id'];
}
$PagesNavigator->num_items($main_info);

if (!empty($projects_ids))
{
	$query = array(
		'SELECT'	=> 'id, table_id',
		'FROM'		=> 'sm_uploader',
		'WHERE'		=> 'table_id IN ('.implode(',', $projects_ids).') AND table_name=\'swift_projects\''
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$uploader_info = array();
	while ($row = $DBLayer->fetch_assoc($result)) {
		$uploader_info[] = $row['table_id'];
	}
}

$SwiftMenu->addNavAction('<li><a class="dropdown-item" href="mailto:@hcares?subject=HCA Projects&body='.get_current_url().'" target="_blank"><i class="fas fa-share-alt"></i> Share link</a></li>');

$Core->set_page_id('swift_projects_list', 'swift_projects');
require SITE_ROOT.'header.php';

if (!empty($main_info)) 
{
	$tasks_info = [
		0 => 'Request',
		1 => 'Bug fix',
		2 => 'Improvement',
		3 => 'Testing'
	];
	$urgency_info = [
		0 => 'Low',
		1 => 'Middle',
		2 => 'High',
	];
?>

<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>Title</th>
			<th>Project Description</th>
			<th>Type</th>
			<th>Urgency</th>
			<th>Status</th>
		</tr>
	</thead>
	<tbody>
<?php
	foreach ($main_info as $cur_info)
	{
		if ($User->checkAccess('swift_projects', 12))
			$Core->add_dropdown_item('<a href="#" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="editProjectInfo('.$cur_info['id'].')"><i class="fas fa-edit"></i> Edit</a>');

		$task_type = isset($tasks_info[$cur_info['task_type']]) ? $tasks_info[$cur_info['task_type']] : 'N/A';
		$urgency = isset($urgency_info[$cur_info['urgency']]) ? $urgency_info[$cur_info['urgency']] : 'N/A';
?>
		<tr id="row<?php echo $cur_info['id'] ?>">
			<td>
				<?php echo html_encode($cur_info['project_desc']) ?>
				<span class="float-end"><?php echo $Core->get_dropdown_menu($cur_info['id']) ?></span>
			</td>
			<td><?php echo html_encode($cur_info['requested_work']) ?></td>
			<td><?php echo $task_type ?></td>
			<td><?php echo $urgency ?></td>
			<td><?php echo '' ?></td>
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
					<h5 class="modal-title">Edit information</h5>
					<button type="button" class="btn-close bg-danger" data-bs-dismiss="modal" aria-label="Close" onclick="closeModalWindow()"></button>
				</div>
				<div class="modal-body">
					<!--modal_fields-->
					<textarea class="form-control"></textarea>
				</div>
				<div class="modal-footer">
					<!--modal_buttons-->
				</div>
			</form>
		</div>
	</div>
</div>

<script>
function editProject(pid) {
	var csrf_token = "<?php echo generate_form_token($URL->link('swift_projects_ajax_get_project_fields')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('swift_projects_ajax_get_project_fields') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({pid:pid,csrf_token:csrf_token}),
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
} else {
?>
	<div class="alert alert-warning my-3" role="alert">You have no items on this page or not found within your search criteria.</div>
<?php
}
require SITE_ROOT.'footer.php';