<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_ui', 5)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

if (isset($_POST['send_email']))
{
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
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$search_by_job_type = isset($_GET['job_type']) ? intval($_GET['job_type']) : 0;

$search_query = [];
if ($search_by_property_id > 0)
	$search_query[] = 'w.property_id='.$search_by_property_id;

if ($User->get('property_access') != '' && $User->get('property_access') != 0)
{
	$property_ids = explode(',', $User->get('property_access'));
	$search_query[] = 'w.property_id IN ('.implode(',', $property_ids).')';
}

if ($User->get('group_id') == $Config->get('o_hca_fs_maintenance'))
	$search_query[] = 'w.completed_by='.$User->get('id');

$query = [
	'SELECT'	=> 'COUNT(w.id)',
	'FROM'		=> 'hca_ui_water_pressure AS w',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'u.id=w.completed_by'
		],
		[
			'INNER JOIN'	=> 'sm_property_db AS p',
			'ON'			=> 'p.id=w.property_id'
		],
	],
];
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$main_info = [];
$query = array(
	'SELECT'	=> 'w.*, u.realname, p.pro_name',
	'FROM'		=> 'hca_ui_water_pressure AS w',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'users AS u',
			'ON'			=> 'u.id=w.completed_by'
		],
		[
			'INNER JOIN'	=> 'sm_property_db AS p',
			'ON'			=> 'p.id=w.property_id'
		],
	],
	'ORDER BY'	=> 'p.pro_name, w.building_number',
	'LIMIT'		=> $PagesNavigator->limit()
);
if (!empty($search_query)) $query['WHERE'] = implode(' AND ', $search_query);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
while ($row = $DBLayer->fetch_assoc($result)) {
	$main_info[] = $row;
}
$PagesNavigator->num_items($main_info);

$query = array(
	'SELECT'	=> 'p.*',
	'FROM'		=> 'sm_property_db AS p',
	'WHERE'		=> 'p.id!=105 AND p.id!=113 AND p.id!=115 AND p.id!=116',
	'ORDER BY'	=> 'p.pro_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $fetch_assoc;
}

$Core->set_page_id('hca_ui_water_pressure_report', 'hca_ui');
require SITE_ROOT.'header.php';
?>

<nav class="navbar search-bar mb-2">
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
				<div class="col-md-auto">
					<button type="submit" class="btn btn-sm btn-outline-success">Search</button>
					<a href="<?php echo $URL->link('hca_ui_water_pressure_report', 0) ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
				</div>
			</div>
		</div>
	</form>
</nav>

<div class="card-header">
	<h6 class="card-title mb-0">Water Pressure Report</h6>
</div>
<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>Property</th>
			<th>BLDG #</th>
			<th>Current value (psi)</th>
			<th>Adjusted value (psi)</th>
			<th>Comment</th>
			<th>Submitted by</th>
		</tr>
	</thead>
	<tbody>

<?php
if (!empty($main_info))
{
	foreach($main_info as $cur_info)
	{
		if ($User->get('id') == $cur_info['completed_by'] || $User->is_admmod())
			$Core->add_dropdown_item('<a href="'.$URL->link('hca_ui_water_pressure', $cur_info['id']).'"><i class="fas fa-edit"></i> Edit</a>');

		$Core->add_dropdown_item('<a href="#" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="getPropertyInfo('.$cur_info['id'].')"><i class="fas fa-at"></i> Send Email</a>');
?>
		<tr class="<?php echo ($id == $cur_info['id']) ? 'anchor' : '' ?>">
			<td class="fw-bold">
				<?php echo html_encode($cur_info['pro_name']) ?>
				<span class="float-end"><?php echo $Core->get_dropdown_menu($cur_info['id']) ?></span>
			</td>
			<td class="fw-bold ta-center"><?php echo html_encode($cur_info['building_number']) ?></td>
			<td class="fw-bold ta-center"><?php echo html_encode($cur_info['pressure_current']) ?></td>
			<td class="fw-bold ta-center"><?php echo html_encode($cur_info['pressure_adjusted']) ?></td>
			<td class=""><?php echo html_encode($cur_info['comment']) ?></td>
			<td class="ta-center">
				<span class="fw-bold"><?php echo html_encode($cur_info['realname']) ?></span>
				<p><?php echo format_date($cur_info['date_completed'], 'n/j/Y') ?></p>
			</td>
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
function getPropertyInfo(id) {
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_ui_ajax_get_water_pressure')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_ui_ajax_get_water_pressure') ?>",
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
