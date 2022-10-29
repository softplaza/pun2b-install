<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$section = isset($_GET['section']) ? $_GET['section'] : 'active';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$access = ($User->checkAccess('territory', 1)) ? true : false;
//if (!$access)
//	message($lang_common['No permission']);

if (isset($_POST['update_info']))
{
	$tid = isset($_POST['tid']) ? intval($_POST['tid']) : 0;
	$form_data = [];

	if (isset($_POST['ter_number'])) $form_data['ter_number'] = swift_trim($_POST['ter_number']);
	if (isset($_POST['ter_description'])) $form_data['ter_description'] = swift_trim($_POST['ter_description']);

	if ($tid > 0 && !empty($form_data))
	{
		$DBLayer->update_values('swift_territories', $tid, $form_data);

		$flash_message = 'Territory updated.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}	
}

else if (isset($_POST['assign']))
{
	$tid = isset($_POST['tid']) ? intval($_POST['tid']) : 0;
	$form_data = [
		'territory_id'		=> $tid,
		'date_started'		=> isset($_POST['date_started']) ? swift_trim($_POST['date_started']) : '',
		'user_id'			=> isset($_POST['user_id']) ? intval($_POST['user_id']) : 0,
	];

	if ($tid > 0 && !empty($form_data))
	{
		$new_id = $DBLayer->insert('swift_assignments', $form_data);

		$update_data = [
			'ter_status' 		=> 1,
			'last_aid'			=> $new_id,
			'last_uid'			=> $form_data['user_id']
		];

		$DBLayer->update_values('swift_territories', $tid, $update_data);

		$flash_message = 'Territory updated.';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}	
}

else if (isset($_POST['delete_project']))
{
	$tid = isset($_POST['tid']) ? intval($_POST['tid']) : 0;

	if ($tid > 0)
	{
		$DBLayer->delete('swift_territories', $tid);

		$flash_message = 'Project deleted';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

$query = array(
	'SELECT'	=> 'COUNT(t.id)',
	'FROM'		=> 'swift_territories AS t',
//	'WHERE'		=> 'project_status > 0'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$query = array(
	'SELECT'	=> 't.*, u.realname, a.date_started, a.date_completed',
	'FROM'		=> 'swift_territories AS t',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'swift_assignments AS a',
			'ON'			=> 'a.id=t.last_aid'
		),
		array(
			'LEFT JOIN'		=> 'users AS u',
			'ON'			=> 'u.id=t.last_uid'
		),
	),
//	'WHERE'		=> 't.project_status=0',
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

$Core->set_page_id('territory_territories', 'territory');
require SITE_ROOT.'header.php';

if (!empty($main_info)) 
{
?>

<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>Number / Description</th>
			<th>Served By</th>
			<th>Start Date</th>
			<th>End Date</th>
		</tr>
	</thead>
	<tbody>
<?php
	foreach ($main_info as $cur_info)
	{
		$Core->add_dropdown_item('<a href="#" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="editProject('.$cur_info['id'].', 1)"><i class="fas fa-edit"></i> Edit</a>');
		$Core->add_dropdown_item('<a href="#" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="editProject('.$cur_info['id'].', 2)"><i class="fas fa-edit"></i> Assign to</a>');


?>
		<tr id="row<?php echo $cur_info['id'] ?>">
			<td>
				<span class="fw-bold"><?php echo html_encode($cur_info['ter_number']) ?></span>
				<?php echo html_encode($cur_info['ter_description']) ?>
				<span class="float-end"><?php echo $Core->get_dropdown_menu($cur_info['id']) ?></span>
			</td>
			<td><?php echo html_encode($cur_info['realname']) ?></td>
			<td><?php echo format_date($cur_info['date_started']) ?></td>
			<td><?php echo format_date($cur_info['date_completed']) ?></td>
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
function editProject(tid,type) {
	var csrf_token = "<?php echo generate_form_token($URL->link('territory_ajax_get_territory')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('territory_ajax_get_territory') ?>",
		type:	"POST",
		dataType: "json",
		cache: false,
		data: ({tid:tid,type:type,csrf_token:csrf_token}),
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