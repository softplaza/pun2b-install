<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_vcr', 5)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

require 'functions_generate_pdf.php';
require 'class_get_vendors.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';
$search_by_vendor_id = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0;
$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$search_by_unit_number = isset($_GET['unit_number']) ? swift_trim($_GET['unit_number']) : '';
$week_of = isset($_GET['week_of']) && $_GET['week_of'] != '' ? strtotime($_GET['week_of']) : time();
$first_day_of_this_week = isset($_GET['week_of']) ? strtotime('Monday this week', $week_of) : strtotime('Monday this week');
$first_day_of_next_week = isset($_GET['week_of']) ? strtotime('Monday next week', $week_of) : strtotime('Monday next week');
$time_slots = array(0 => 'ALL DAY', 1 => 'A.M.', 2 => 'P.M.');

$query = array(
	'SELECT'	=> 'i.*, p.unit_number, p.unit_size, p.property_id, p.status, pt.pro_name, v.vendor_name, v.email, v.phone_number, v.orders_limit',
	'FROM'		=> 'hca_vcr_invoices AS i',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'hca_vcr_projects AS p',
			'ON'			=> 'p.id=i.project_id'
		),
		array(
			'LEFT JOIN'		=> 'sm_vendors AS v',
			'ON'			=> 'v.id=i.vendor_id'
		),
		array(
			'LEFT JOIN'		=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=p.property_id'
		),
	),
	'WHERE'		=> 'p.status=0 AND i.in_house=0 AND i.date_time >= '.$first_day_of_this_week.' AND i.date_time < '.$first_day_of_next_week,
	'ORDER BY'	=> 'v.vendor_name, pt.pro_name, LENGTH(p.unit_number), p.unit_number'
);
if ($search_by_vendor_id > 0)
	$query['WHERE'] .= ' AND vendor_id='.$search_by_vendor_id;
if ($search_by_property_id > 0) {
	$query['WHERE'] .= ' AND property_id=\''.$DBLayer->escape($search_by_property_id).'\'';
}
if ($search_by_unit_number != '') {
	$search_by_unit2 = '%'.$search_by_unit_number.'%';
	$query['WHERE'] .= ' AND p.unit_number LIKE \''.$DBLayer->escape($search_by_unit2).'\'';
}
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $vendor_counter = $vendor_dupe = $dupe_ids = array();
while ($row = $DBLayer->fetch_assoc($result))
{
	$main_info[$row['id']] = $row;
	
	if (isset($vendor_counter[$row['vendor_id']][$row['date_time']]))
		$vendor_counter[$row['vendor_id']][$row['date_time']] = ++$vendor_counter[$row['vendor_id']][$row['date_time']];
	else
		$vendor_counter[$row['vendor_id']][$row['date_time']] = 1;
		
	if (isset($vendor_dupe[$row['project_id']][$row['vendor_group_id']])) {
		$dupe_ids[] = $vendor_dupe[$row['project_id']][$row['vendor_group_id']];
		$dupe_ids[] = $row['id'];
	} else {
		$vendor_dupe[$row['project_id']][$row['vendor_group_id']] = $row['id'];
	}
}
$PagesNavigator->total_items = count($main_info);
$PagesNavigator->num_items($main_info);

if (isset($_POST['update']))
{
	$po_numbers = isset($_POST['po_number']) ? $_POST['po_number'] : '';
	
	if (empty($Core->errors) && !empty($po_numbers))
	{
		foreach($po_numbers as $id => $value)
		{
			$form_data = array(
				'po_number'		=> swift_trim($value),
				'remarks'		=> isset($_POST['remarks'][$id]) ? $_POST['remarks'][$id] : ''
			);
			$DBLayer->update_values('hca_vcr_invoices', $id, $form_data);
		}
		
		$flash_message = 'Vendors schedule has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['renew']))
{
	$id = intval(key($_POST['renew']));
	
	$form_data = array(
		'vendor_group_id'	=> 0,
	);
	$DBLayer->update_values('hca_vcr_invoices', $id, $form_data);
	
	$flash_message = 'Vendors schedule has been updated';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

else if (isset($_POST['change_date']))
{
	$schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;
	$time_shift = isset($_POST['time_shift']) ? intval($_POST['time_shift']) : 0;
	$vendor_id = isset($_POST['vendor_id']) ? intval($_POST['vendor_id']) : 0;
	
	if ($schedule_id < 1)
		$Core->add_error('Wrong schedule ID number.');
	
	if (empty($Core->errors))
	{
		$form_data = [
			'vendor_id' => $vendor_id, 
			'shift'		=> $time_shift
		];
		$DBLayer->update_values('hca_vcr_invoices', $schedule_id, $form_data);
		
		$flash_message = 'Vendor date has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['duplicate']))
{
	$id = intval(key($_POST['duplicate']));
	
	if ($id > 0)
	{
		$query = array(
			'SELECT'	=> 'i.project_id, i.vendor_id, i.vendor_name, i.date_time',
			'FROM'		=> 'hca_vcr_invoices AS i',
			'WHERE'		=> 'i.id= '.$id,
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$invoice_info = $DBLayer->fetch_assoc($result);	
		
		$invoice_info['vendor_group_id'] = 0;
		$new_id = $DBLayer->insert_values('hca_vcr_invoices', $invoice_info);
		
		$flash_message = 'Invoice #'.$id.' has been duplicated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['delete']))
{
	$id = intval(key($_POST['delete']));
	
	if ($id > 0)
	{
		$query = array(
			'DELETE'	=> 'hca_vcr_invoices',
			'WHERE'		=> 'id= '.$id,
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
		$flash_message = 'Invoice #'.$id.' has been deleted';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['send_email']))
{
	$email_list = isset($_POST['email_list']) ? swift_trim($_POST['email_list']) : '';
	$vendor_name = isset($_POST['vendor_name']) ? swift_trim($_POST['vendor_name']) : '';
	$mail_subject = isset($_POST['mail_subject']) ? swift_trim($_POST['mail_subject']) : 'Vendor schedule';
	$mail_message = isset($_POST['mail_message']) ? swift_trim($_POST['mail_message']) : 'Hello';
	
	if ($email_list == '')
		$Core->add_error('Email list can not be empty');
	if (!hca_sp_gen_pdf_for_each_vendor())
		$Core->add_error('Can not create PDF file');
	if (!file_exists('files/vendors_schedule.pdf'))
		$Core->add_error('No vendor schedule found.');

	if (empty($Core->errors))
	{
		$mail_file = 'files/vendors_schedule.pdf';

		$SwiftMailer = new SwiftMailer;
		$SwiftMailer->send($email_list, 'Vendor schedule', $mail_message, [$mail_file]);
		
		$flash_message = 'Vendors schedule has been sent by email to: '.$email_list;
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

if (file_exists('files/vendors_schedule.pdf'))
	unlink('files/vendors_schedule.pdf');

if (!hca_sp_gen_pdf_for_each_vendor())
	$Core->add_warning('There are no vendors scheduled for this week.');

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'sm_vendors',
	'ORDER BY'	=> 'vendor_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$vendors_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$vendors_info[] = $row;
}

$query = array(
	'SELECT'	=> 'id, pro_name',
	'FROM'		=> 'sm_property_db',
	'ORDER BY'	=> 'pro_name',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $row;
}

$Core->add_page_action('<a href="files/vendors_schedule.pdf?'.time().'" target="_blank"><i class="fa fa-file-pdf-o fa-2x"></i>Print as PDF</a>');

$Core->set_page_id('hca_vcr_vendor_schedule', 'hca_vcr');
require SITE_ROOT.'header.php';
?>
		
<nav class="navbar container-fluid search-box">
	<form method="get" accept-charset="utf-8" action="">
		<div class="row">
			<div class="col pe-0">
				<select name="vendor_id" class="form-select">
					<option value="">All Vendors</option>
<?php 
	foreach ($vendors_info as $info) {
			if ($search_by_vendor_id == $info['id'])
				echo '<option value="'.$info['id'].'" selected="selected">'.$info['vendor_name'].'</option>';
			else
				echo '<option value="'.$info['id'].'">'.$info['vendor_name'].'</option>';
	}
?>
				</select>
			</div>

<?php if ($User->get('sm_pm_property_id') == 0): ?>
			<div class="col pe-0">
				<select name="property_id" class="form-select">
					<option value="">All Properties</option>
<?php 
	foreach ($property_info as $cur_info)
	{
				if ($search_by_property_id == $cur_info['id'])
					echo '<option value="'.$cur_info['id'].'" selected="selected">'.html_encode($cur_info['pro_name']).'</option>';
				else
					echo '<option value="'.$cur_info['id'].'">'.html_encode($cur_info['pro_name']).'</option>';
	}
?>
				</select>
			</div>
<?php endif; ?>

			<div class="col pe-0">
				<input type="text" name="unit_number" value="<?php echo $search_by_unit_number ?>" placeholder="Unit #" class="form-control">
			</div>
			<div class="col pe-0">
				<input type="date" name="week_of" value="<?php echo date('Y-m-d', $first_day_of_this_week) ?>" class="form-control">
			</div>
			<div class="col pe-0">
				<button type="submit" class="btn btn-outline-success">Search</button>
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
		<table class="table table-striped table-bordered">
			<div class="card-header">
				<h6 class="card-title mb-0">Vendor schedule</h6>
			</div>
<?php
		if ($search_by_property_id > 0)
		{
			$query = array(
				'SELECT'	=> '*',
				'FROM'		=> 'sm_property_db',
				'WHERE'		=> 'id='.$search_by_property_id
			);
			$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
			$property = $DBLayer->fetch_assoc($result);
		}
		
		$btn_send_email = ($action == 'print') ? '' : '<button type="button" class="badge bg-primary" data-bs-toggle="modal" data-bs-target="#modalEmailWindow">Send Schedule</button>';
		if ($search_by_vendor_id > 0)
		{
			$query = array(
				'SELECT'	=> '*',
				'FROM'		=> 'sm_vendors',
				'WHERE'		=> 'id='.$search_by_vendor_id
			);
			$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
			$vendor_info = $DBLayer->fetch_assoc($result);
?>
			<thead>
				<tr><td colspan="7">Week of: <?php echo format_time($first_day_of_this_week, 1) ?></td></tr>
				<tr><td colspan="7">Vendor Name: <?php echo html_encode($vendor_info['vendor_name']) ?></td></tr>
				<tr><td colspan="7">Payee ID: <?php echo html_encode($vendor_info['payee_id']) ?></td></tr>
				<tr><td colspan="7">Phone Number: <?php echo html_encode($vendor_info['phone_number']) ?></td></tr>
				<tr><td colspan="7">Email: <?php echo html_encode($vendor_info['email']) ?></td></tr>
			</thead>
<?php
		}
		else
		{
?>
			<thead>
				<tr><td colspan="7">Week of: <?php echo format_time($first_day_of_this_week, 1) ?></td></tr>
				<tr><td colspan="7">Recipient: Front Desk</td></tr>
				<tr><td colspan="7">Email: <?php echo html_encode($Config->get('o_hca_vcr_home_office_emails')) ?> <span class="float-end"><?php echo $btn_send_email ?></span></td></tr>
			</thead>
<?php
		}
?>
			<thead>
				<tr>
					<th class="min-150">Vendor / Date</th>
					<th>Property</th>
					<th>Unit#</th>
					<th>Size</th>
					<th class="min-150">P.O. #</th>
					<th class="min-150">Remarks</th>
				</tr>
			</thead>
			<tbody>
<?php
		foreach($main_info as $cur_info)
		{
			$Core->add_dropdown_item('<a href="#" data-bs-toggle="modal" data-bs-target="#modalWindow" onclick="editVendorInfo('.$cur_info['id'].')"><i class="fas fa-edit fa-lg"></i> Edit</a>');
			$Core->add_dropdown_item('<button type="submit" name="duplicate['.$cur_info['id'].']" class="badge bg-primary">Duplicate</button>');
			$Core->add_dropdown_item('<button type="submit" name="delete['.$cur_info['id'].']" class="badge bg-danger" onclick="return confirm(\'Are you sure you want to remove this row?\')">Delete</button>');

			$tr_css = [];
			$num_jobs_per_day = isset($vendor_counter[$cur_info['vendor_id']][$cur_info['date_time']]) ? $vendor_counter[$cur_info['vendor_id']][$cur_info['date_time']] : 0;
			
			if ($num_jobs_per_day > $cur_info['orders_limit'] && $cur_info['orders_limit'] != 0)
				$alert_jobs_per_day = '<span class="position-absolute top-0 start-100 ms-2 translate-middle badge rounded-pill bg-danger">'.$num_jobs_per_day.'</span>';
			else if ($num_jobs_per_day == $cur_info['orders_limit'] && $cur_info['orders_limit'] != 0)
				$alert_jobs_per_day = '<span class="position-absolute top-0 start-100 ms-2 translate-middle badge rounded-pill bg-warning">'.$num_jobs_per_day.'</span>';
			else
				$alert_jobs_per_day = '<span class="position-absolute top-0 start-100 ms-2 translate-middle badge rounded-pill bg-success">'.$num_jobs_per_day.'</span>';

			$tr_css[] = in_array($cur_info['id'], $dupe_ids) ? 'text-danger' : '';
			$tr_css[] = ($cur_info['vendor_group_id'] == 0) ? 'text-primary' : '';
?>
			<tr id="row<?php echo $cur_info['id'] ?>" class="<?php echo implode(' ', $tr_css) ?>">
				<td>
					<strong class="position-relative"><?php echo html_encode($cur_info['vendor_name']) . $alert_jobs_per_day ?></strong>
					<p><strong><?php echo format_time($cur_info['date_time'], 1) ?>, <?php echo $time_slots[$cur_info['shift']] ?></strong></p>
					<p><?php echo $VCRVendors->get_servise_name($cur_info['vendor_group_id']) ?></p>
					<span class="float-end"><?php echo $Core->get_dropdown_menu($cur_info['id']) ?></span>
				</td>
				<td class="fw-bold"><?php echo html_encode($cur_info['pro_name']) ?></td>
				<td class="fw-bold"><?php echo html_encode($cur_info['unit_number']) ?></td>
				<td><?php echo html_encode($cur_info['unit_size']) ?></td>
				<td><input type="text" name="po_number[<?php echo $cur_info['id'] ?>]" value="<?php echo html_encode($cur_info['po_number']) ?>" class="form-control"></td>
				<td><textarea name="remarks[<?php echo $cur_info['id'] ?>]" class="form-control"><?php echo html_encode($cur_info['remarks']) ?></textarea></td>
			</tr>
<?php
		}
?>
		</tbody>
	</table>
	<button type="submit" name="update" class="btn btn-primary">Save changes</button>
</form>
		
<div class="alert alert-info my-3" role="alert">
	<p class="fw-bold">Limit of orders per day:</p>
	<span class="badge bg-success">5</span> <span>Below limit</span>
	<span class="badge bg-warning ms-1">10</span> <span>Limit reached</span>
	<span class="badge bg-danger ms-1">15</span> <span>Limit exceeded</span>
</div>
<?php
	} else {
?>

<div class="alert alert-warning my-3" role="alert">The list is empty or not found within your search criteria.</div>

<?php
}

if (file_exists('files/vendors_schedule.pdf')) : ?>
<iframe class="iframe-full" src="files/vendors_schedule.pdf?<?php echo time() ?>"></iframe>
<?php endif; ?>

<div class="email-window" style="display:none">
	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		<div class="edit-assign">
			<div class="title">
				<span class="close-window"><img src="<?php echo BASE_URL ?>/img/close.png" width="18px" onclick="closeWindows()"></span>
				<p>Send Invoice to Email</p>
			</div>

			<p class="subject"><input type="text" name="mail_subject" value="HCA Vendor Schedule"></p>
			<p><textarea name="email_list" rows="2" placeholder="Enter emails separated by commas"><?php echo html_encode($Config->get('o_hca_vcr_home_office_emails')) ?></textarea></p>
			<p>
				<input type="radio" name="mail_type" value="1" checked onchange="mailingType(1)"> Home Office 
<?php if ($search_by_vendor_id > 0): ?>
				<input type="radio" name="mail_type" value="2" onchange="mailingType(2)"> Vendor 
<?php endif; ?>
			</p>
			<p><textarea name="mail_message" rows="8" placeholder="Write your message">Hello.&#13;&#10;&#13;&#10;This is the Vendor Schedule for the week of <?php echo format_time($first_day_of_this_week, 1) ?>.</textarea></p>
			<p>*Recipients will receive invoice list.</p>
			<p class="btn-action"><span class="submit primary"><input type="submit" name="send_email" value="Send Email" onclick="closeWindows()"/></span></p>
		</div>
	</form>
</div>

<div class="modal fade" id="modalWindow" tabindex="-1" aria-labelledby="modalWindowLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method="post" accept-charset="utf-8" action="">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
				<div class="modal-header">
					<h5 class="modal-title" id="modalWindowLabel">Vendor information</h5>
					<button type="button" class="btn-close bg-danger" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<!--body_fields-->
				</div>
				<div class="modal-footer">
					<!--buttons-->
				</div>
			</form>
		</div>
	</div>
</div>

<div class="modal fade" id="modalEmailWindow" tabindex="-1" aria-labelledby="modalEmailWindowLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method="post" accept-charset="utf-8" action="">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>">
				<div class="modal-header">
					<h5 class="modal-title" id="modalEmailWindowLabel">Send </h5>
					<button type="button" class="btn-close bg-danger" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="mb-3">
						<label class="form-label">Subject</label>
						<input type="text" name="mail_subject" value="Vendor schedule" class="form-control">
					</div>
					<div class="mb-3">
						<label class="form-label">Recipients</label>
						<textarea name="email_list" class="form-control" placeholder="Enter emails separated by commas"><?php echo html_encode($Config->get('o_hca_vcr_home_office_emails')) ?></textarea>
					</div>
					<div class="mb-3">
						<label class="form-label"></label>
						<textarea name="mail_message" class="form-control" placeholder="Write your message" rows="6">Hello.&#13;&#10;&#13;&#10;This is the Vendor Schedule for the week of <?php echo format_time($first_day_of_this_week, 1) ?>.</textarea>
					</div>
				</div>
				<div class="modal-footer">
					<button type="submit" name="send_email" class="btn btn-sm btn-primary">Send Email</button>
				</div>
			</form>
		</div>
	</div>
</div>

<script>
function editVendorInfo(id) {
	var pos = $("#row"+id).position();
	var posT = pos.top - 115;
	var posL = pos.left + 150;
	var csrf_token = "<?php echo generate_form_token($URL->link('hca_vcr_ajax_get_vendor_schedule')) ?>";
	jQuery.ajax({
		url:	"<?php echo $URL->link('hca_vcr_ajax_get_vendor_schedule') ?>",
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
			$(".msg-section").empty().html('Error: No data.');
		}
	});
}

function mailingType(v){
	if (v==1)
		$('.email-window textarea[name="email_list"]').val('<?php echo html_encode($Config->get('o_hca_vcr_home_office_emails')) ?>');
	else if (v==2)
		$('.email-window textarea[name="email_list"]').val('<?php echo isset($vendor_info['email']) ? html_encode($vendor_info['email']) : '' ?>');
	else if (v==3)
		$('.email-window textarea[name="email_list"]').val('<?php echo isset($property['manager_email']) ? html_encode($property['manager_email']) : '' ?>');
}
function emailWindow(){
	var pos = $(".send-email").position();
	var posT = pos.top - 145;
	
	$(".email-window").slideDown("2000");
	$(".email-window").css("top", posT + "px");
}
function closeWindows(){
	$(".email-window, .pop-up-window").css("display","none");
	$('#manage_vendor_schedule input[name="schedule_id"]').val('0');
}
</script>

<?php
require SITE_ROOT.'footer.php';