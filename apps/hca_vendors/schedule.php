<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';
require 'class_get_vendors.php';

$access = ($User->is_admmod() || $User->get('hca_vcr_access') > 0) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$action = isset($_GET['action']) ? $_GET['action'] : '';
$search_by_vendor_id = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0;
$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$search_by_unit_number = isset($_GET['unit_number']) ? swift_trim($_GET['unit_number']) : '';
$week_of = isset($_GET['week_of']) ? strtotime($_GET['week_of']) : time();
$first_day_of_this_week = isset($_GET['week_of']) ? strtotime('Monday this week', $week_of) : strtotime('Monday this week');
$first_day_of_next_week = isset($_GET['week_of']) ? strtotime('Monday next week', $week_of) : strtotime('Monday next week');

$query = array(
	'SELECT'	=> 'COUNT(i.id)',
	'FROM'		=> 'hca_vcr_invoices AS i',
);
if ($search_by_vendor_id > 0)
	$query['WHERE'] .= ' AND i.vendor_id='.$search_by_vendor_id;
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$PagesNavigator->set_total($DBLayer->result($result));

$query = array(
	'SELECT'	=> 'i.*, pj.unit_number, pj.unit_size, pj.property_id, pt.pro_name, v.vendor_name, v.email, v.phone_number',
	'FROM'		=> 'hca_vcr_invoices AS i',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'hca_vcr_projects AS pj',
			'ON'			=> 'pj.id=i.project_id'
		),
		array(
			'LEFT JOIN'		=> 'sm_vendors AS v',
			'ON'			=> 'v.id=i.vendor_id'
		),
		array(
			'LEFT JOIN'		=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=pj.property_id'
		),
	),
	'LIMIT'		=> $PagesNavigator->limit(),
	'WHERE'		=> 'i.id > 0',
	'ORDER BY'	=> 'i.date_time'
);
if ($search_by_vendor_id > 0)
	$query['WHERE'] .= ' AND i.vendor_id='.$search_by_vendor_id;
if ($search_by_property_id > 0) {
	$query['WHERE'] .= ' AND pj.property_id=\''.$DBLayer->escape($search_by_property_id).'\'';
}
if ($search_by_unit_number != '') {
	$search_by_unit2 = '%'.$search_by_unit_number.'%';
	$query['WHERE'] .= ' AND pj.unit_number LIKE \''.$DBLayer->escape($search_by_unit2).'\'';
}
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $vendor_counter = $vendor_dupe = $dupe_ids = array();
while ($row = $DBLayer->fetch_assoc($result)) {
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
$PagesNavigator->num_items($main_info);

//print_r($dupe_ids);
if (isset($_POST['update']))
{
	$po_numbers = isset($_POST['po_number']) ? $_POST['po_number'] : '';
	
	if ($po_numbers == '')
		$Core->add_error('PO number cannot be empty.');
	
	if (empty($Core->errors))
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

else if (isset($_POST['duplicate']))
{
	$id = intval(key($_POST['duplicate']));
	
	if ($id > 0)
	{
		$query = array(
			'SELECT'	=> 'i.project_id, i.vendor_id, i.vendor_group_id, i.vendor_name, i.date_time',
			'FROM'		=> 'hca_vcr_invoices AS i',
			'WHERE'		=> 'i.id= '.$id,
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$invoice_info = $DBLayer->fetch_assoc($result);	
		
		$new_id = $DBLayer->insert_values('hca_vcr_invoices', $invoice_info);
		
		$flash_message = 'Invoice #'.$id.' has been duplicated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['send_email']))
{
	$email_list = isset($_POST['email_list']) ? swift_trim($_POST['email_list']) : '';
	$vendor_name = isset($_POST['vendor_name']) ? swift_trim($_POST['vendor_name']) : '';
	$mail_subject = isset($_POST['mail_subject']) ? swift_trim($_POST['mail_subject']) : 'HCA Projects';
	$mail_message = isset($_POST['mail_message']) ? swift_trim($_POST['mail_message']) : 'Hello';
	
	if ($email_list == '')
		$Core->add_error('Email list can not be empty');
	if (!hca_sp_gen_pdf_for_each_vendor())
		$Core->add_error('Can not create PDF file');
	
	if (empty($Core->errors))
	{
		$emails_array = explode(',', $email_list);
		foreach($emails_array as $cur_email) {
			$emails_array[] = $cur_email;
		}
		
		$SwiftMailer = new SwiftMailer;
		//$SwiftMailer->isHTML();
		$SwiftMailer->send(implode(',', $emails_array), 'Vendors', $mail_message);

		if (file_exists('vendors_schedule.pdf'))
			unlink('vendors_schedule.pdf');
		
		$flash_message = 'Vendors schedule has been sent by email to: '.$email_list;
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

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

$Core->set_page_title('Vendors Schedule');
$Core->set_page_id('sm_vendors_schedule', 'sm_vendors');
require SITE_ROOT.'header.php';
?>

<style>
.ct-group th{background: #d1d7fe;}
.ct-group td{background: #eceef8;}
.header1 td{background: #efe3fc;}
.header1 .title{text-align:right;}
.header1 .desc{font-weight:bold;}
.comment{min-width:200px;}
.comment textarea{width:95%;}
.ct-group .new th{background: #99d5ff;}
.ct-group .new td{background: #dff3ff;}
.ct-group .dupe-alert td{background: pink;}
.ct-group .dupe-vendor td{background: #edd5ff;}
.email-window {width: 300px;position: absolute;background: #dcf5e5;padding: 5px;line-height: 23px;border-radius: 10px;border: 1px solid #A5A5A5;z-index: 300;opacity: 0.9;left: 100px;}
.close-window{cursor: pointer;float: right;position: relative;z-index: 300;}
.close-window img{padding: .4em;}
.email-window textarea{width:97%;}
.mailing-fields{columns: 2;padding-left: 5px;}
.email-window .btn-action{text-align: center;}
.send-email img{width:40px;cursor:pointer;}
.email-window .title{background: #e3eff4;border-style: groove;border-width: 2px;border-color: #f3f8fc;}
.email-window .title p{font-weight: bold;padding-left: .3em;}
</style>

<div class="main-content main-frm">
	<div class="ct-group">
		<div class="search-box">
			<form method="get" accept-charset="utf-8" action="">
				<select name="vendor_id"><option value="">All Vendors</option>
<?php 
	foreach ($vendors_info as $info) {
			if ($search_by_vendor_id == $info['id'])
				echo '<option value="'.$info['id'].'" selected="selected">'.$info['vendor_name'].'</option>';
			else
				echo '<option value="'.$info['id'].'">'.$info['vendor_name'].'</option>';
	}
?>
				</select>
				<select name="property_id"><option value="">All Properties</option>
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
				<input type="text" name="unit_number" value="<?php echo $search_by_unit_number ?>" placeholder="Unit #" size="10"/>
				Week of:<input type="date" name="week_of" value="<?php echo date('Y-m-d', $first_day_of_this_week) ?>"/>
				<input type="submit" value="Search" />
			</form>
		</div>
<?php
	if (!empty($main_info))
	{
?>
		<form method="post" accept-charset="utf-8" action="">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			<table class="invoice">
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
		
		$btn_send_email = ($action == 'print') ? 'Email: ' : '<button type="button" class="lightseagreen" onclick="emailWindow()">Send Email</button>';
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
				<thead class="header1">
					<tr><th></th><th colspan="5"><strong>Invoice Information</strong></th></tr>
					<tr><td class="title"><strong>Week of: </strong></td><td colspan="5" class="desc"><?php echo format_time($first_day_of_this_week, 1) ?></td></tr>
					<tr><td class="title">Vendor Name: </td><td colspan="5" class="desc"><?php echo html_encode($vendor_info['vendor_name']) ?></td></tr>
					<tr><td class="title">Payee ID: </td><td colspan="5" class="desc"><?php echo html_encode($vendor_info['payee_id']) ?></td></tr>
					<tr><td class="title">Phone Number: </td><td colspan="5" class="desc"><?php echo html_encode($vendor_info['phone_number']) ?></td></tr>
					<tr><td class="title send-email"><?php echo $btn_send_email ?></td><td class="desc"><?php echo html_encode($vendor_info['email']) ?></td><td colspan="5"></td></tr>
				</thead>
<?php
		}
		else if ($search_by_property_id > 0)
		{
?>
				<thead class="header1">
					<tr><th></th><th colspan="6"><strong>Property Information</strong></th></tr>
					<tr><td class="title"><strong>Week of: </strong></td><td colspan="6" class="desc"><?php echo format_time($first_day_of_this_week, 1) ?></td></tr>
					<tr><td class="title">Property Name: </td><td colspan="6" class="desc"><?php echo html_encode($property['pro_name']) ?></td></tr>
					<tr><td class="title send-email"><?php echo $btn_send_email ?></td><td class="desc"><?php echo html_encode($property['manager_email']) ?></td><td colspan="6"></td></tr>
				</thead>
<?php
		}
		else
		{
?>
				<thead class="header1">
					<tr><th><strong>Information</strong></th><th colspan="6"></th></tr>
					<tr><td class="title"><strong>Week of: </strong></td><td colspan="6" class="desc"><?php echo format_time($first_day_of_this_week, 1) ?></td></tr>
					<tr><td class="title">Recipient: </td><td colspan="6" class="desc">Front Desk</td></tr>
					<tr><td class="title send-email"><?php echo $btn_send_email ?></td><td class="desc"><?php echo html_encode($Config->get('o_hca_vcr_home_office_emails')) ?></td><td colspan="6"></td></tr>
				</thead>
<?php	
		}
		
?>
				<thead class="header2">
					<tr>
						<th>Vendor Schedule Date</th>
						<th>Property</th>
						<th>Unit#</th>
						<th>Size</th>
						<th>P.O #</th>
						<th>Remarks</th>
						<th>Duplicate</th>
					</tr>
				</thead>
				<tbody>
<?php
		$duplicate = array();
		foreach($main_info as $cur_info)
		{
			$tr_css = array();
			$tr_css[] = in_array($cur_info['id'], $dupe_ids) ? 'dupe-alert' : '';
			$tr_css[] = ($cur_info['vendor_group_id'] == 0) ? 'dupe-vendor' : '';
?>
					<tr class="<?php echo implode(' ', $tr_css) ?>">
						<td class="date">
							<p><strong><?php echo html_encode($cur_info['vendor_name']) ?></strong></p>
							<p><?php echo format_time($cur_info['date_time'], 1) ?></p>
							<p><?php echo $VCRVendors->get_servise_name($cur_info['vendor_group_id']) ?></p>
						</td>
						<td class=""><?php echo html_encode($cur_info['pro_name']) ?></td>
						<td class=""><?php echo html_encode($cur_info['unit_number']) ?></td>
						<td class=""><?php echo html_encode($cur_info['unit_size']) ?></td>
						<td class="po"><input type="text" name="po_number[<?php echo $cur_info['id'] ?>]" value="<?php echo html_encode($cur_info['po_number']) ?>" /></td>
						<td class="comment"><textarea name="remarks[<?php echo $cur_info['id'] ?>]"><?php echo html_encode($cur_info['remarks']) ?></textarea></td>
						<td class="actions"><span class="submit primary"><input type="submit" name="duplicate[<?php echo $cur_info['id'] ?>]" value="+" /></span></td>
					</tr>
<?php
		}
?>
				</tbody>
			</table>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="update" value="Save Changes" /></span>
			</div>
		</form>
<?php
	} else {
?>
		<div class="ct-set warn-set">
			<div class="ct-box warn-box">
				<h6 class="ct-legend hn warn"><span>Information:</span></h6>
				<p>The list is empty or not found within your search criteria.</p>
			</div>
		</div>
<?php
	}
?>
	</div>
</div>

<div class="email-window" style="display:none">
	<form method="post" accept-charset="utf-8" action="<?php echo get_current_url() ?>">
		<div class="hidden">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(get_current_url()) ?>" />
		</div>
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
<?php if ($search_by_property_id > 0): ?>
				<input type="radio" name="mail_type" value="3" onchange="mailingType(3)"> Property 
<?php endif; ?>
			</p>
			<p><textarea name="mail_message" rows="8" placeholder="Write your message">Hello.&#13;&#10;This is the Vendor Schedule for the week of <?php echo format_time($first_day_of_this_week, 1) ?>.</textarea></p>
			<p>*Recipients will receive invoice list.</p>
			<p class="btn-action"><span class="submit primary"><input type="submit" name="send_email" value="Send Email" onclick="closeWindows()"/></span></p>
		</div>
	</form>
</div>

<script>
function mailingType(v){
	if (v==1)
		$('.email-window textarea[name="email_list"]').val('<?php echo html_encode($Config->get('o_hca_vcr_home_office_emails')) ?>');
	else if (v==2)
		$('.email-window textarea[name="email_list"]').val('<?php echo ($search_by_vendor_id > 0) ? html_encode($vendor_info['email']) : '' ?>');
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
	$(".email-window").css("display","none");
}
</script>

<?php
require SITE_ROOT.'footer.php';