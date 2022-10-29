<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';
require 'functions_generate_pdf.php';
require 'class_get_vendors.php';

$access = ($User->is_admmod() || $User->get('hca_vcr_access') > 0) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$action = isset($_GET['action']) ? $_GET['action'] : '';
$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$week_of = isset($_GET['week_of']) && $_GET['week_of'] != '' ? strtotime($_GET['week_of']) : time();
$first_day_of_this_week = isset($_GET['week_of']) ? strtotime('Monday this week', $week_of) : strtotime('Monday this week');
$first_day_of_next_week = isset($_GET['week_of']) ? strtotime('Monday next week', $week_of) : strtotime('Monday next week');

$query = array(
	'SELECT'	=> 'i.*, pj.unit_number, pj.unit_size, pj.property_id, pj.paint_inhouse, pj.status, pt.pro_name, pt.manager_email, v.vendor_name, v.email, v.phone_number',
	'FROM'		=> 'hca_vcr_invoices AS i',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'hca_vcr_projects AS pj',
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
	'WHERE'		=> 'pj.status=0 AND i.date_time >= '.$first_day_of_this_week.' AND i.date_time < '.$first_day_of_next_week,
	'ORDER BY'	=> 'pt.pro_name, LENGTH(pj.unit_number), pj.unit_number'
);
if ($search_by_property_id > 0) {
	$query['WHERE'] .= ' AND property_id=\''.$DBLayer->escape($search_by_property_id).'\'';
}
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = array();
while ($row = $DBLayer->fetch_assoc($result))
{
	$in_house = ($row['vendor_group_id'] == 8) ? 'In-House Maintenance' : 'In-House Painter';

	$main_info[] = array(
		'id'				=> $row['id'],
		'vendor_group_id'	=> $row['vendor_group_id'],
		'vendor_id'			=> $row['vendor_id'],
		'vendor_name'		=> ($row['in_house'] == 0) ? $row['vendor_name'] : $in_house,
		'date_time'			=> $row['date_time'],
		'property_id'		=> $row['property_id'],
		'pro_name'			=> $row['pro_name'],
		'unit_number'		=> $row['unit_number'],
		'unit_size'			=> $row['unit_size'],
		'po_number'			=> $row['po_number'],
		'remarks'			=> $row['remarks'],
	);
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
		
		$flash_message = 'Property schedule has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

else if (isset($_POST['send_email']))
{
	$mail_type = isset($_POST['mail_type']) ? intval($_POST['mail_type']) : 0;
	$email_list = isset($_POST['email_list']) ? swift_trim($_POST['email_list']) : '';
	$vendor_name = isset($_POST['vendor_name']) ? swift_trim($_POST['vendor_name']) : '';
	$mail_subject = isset($_POST['mail_subject']) ? swift_trim($_POST['mail_subject']) : 'HCA Projects';
	$mail_message = isset($_POST['mail_message']) ? swift_trim($_POST['mail_message']) : 'Hello';
	
	if ($email_list == '' && $mail_type != 4)
		$Core->add_error('Email list can not be empty');
	
	$query = array(
		'SELECT'	=> 'pt.id, pt.pro_name, pt.manager_email',
		'FROM'		=> 'sm_property_db AS pt',
		'ORDER BY'	=> 'pt.pro_name'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$property_info = array();
	while ($row = $DBLayer->fetch_assoc($result)) {
		$property_info[] = $row;
	}
	
	if (empty($Core->errors))
	{
		if ($mail_type == 4)
		{
			foreach($property_info as $cur_info)
			{
				if (!file_exists('files/schedule_for_property.pdf'))
				{
					$pdf_file = hca_vcr_send_schedule_each_property($cur_info['id']);
					
					if ($pdf_file)
					{
						$email_list .= (($email_list != '') ? ',' : '').$cur_info['manager_email'];
					}
				}
				
				if (file_exists('files/schedule_for_property.pdf'))
					unlink('files/schedule_for_property.pdf');
			}
/*
			$SwiftMailer = new SwiftMailer;
			//$SwiftMailer->isHTML();
			$SwiftMailer->send($emails, 'Moisture Project', $mail_message);
*/
		}

		$flash_message = 'Vendors schedule has been sent by email to: '.$email_list;
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

if ($search_by_property_id > 0)
{
	if (file_exists('files/schedule_for_property.pdf'))
		unlink('files/schedule_for_property.pdf');
	if (!hca_vcr_send_schedule_each_property($search_by_property_id))
		$Core->add_warning('Can not create PDF file');
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

//$Core->set_page_title('Vendor\'s schedule');
$Core->set_page_id('hca_vcr_property_schedule', 'hca_vcr');
require SITE_ROOT.'header.php';
?>

<style>
.search-box input[type="date"]{font-weight: bold;}
.ct-group th{background: #d1d7fe;}
.ct-group td{background: #eceef8;}
.header1 td{background: #efe3fc;}
.header1 .title{text-align:right;}
.header1 .desc{font-weight:bold;}
.invoice .comment{min-width:200px;text-align:left;}
.comment textarea{width:95%;}
.ct-group .new th{background: #99d5ff;}
.ct-group .new td{background: #dff3ff;}
.email-window {width: 300px;position: absolute;background: #dcf5e5;padding: 5px;line-height: 23px;border-radius: 10px;border: 1px solid #A5A5A5;z-index: 300;opacity: 0.9;left: 100px;}
.close-window{cursor: pointer;float: right;position: relative;z-index: 300;}
.close-window img{padding: .4em;}
.email-window textarea{width:97%;}
.mailing-fields{columns: 2;padding-left: 5px;}
.email-window .btn-action{text-align: center;}
.send-email img{width:40px;cursor:pointer;}
.email-window .title{background: #e3eff4;border-style: groove;border-width: 2px;border-color: #f3f8fc;}
.email-window .title p{font-weight: bold;padding-left: .3em;}
.subject input{width: 97%;}
#demo_iframe {width:100%; height:400px; zoom: 2;}
</style>

<div class="main-content main-frm">
	<div class="ct-group">
		<div class="search-box">
			<form method="get" accept-charset="utf-8" action="">
				<select name="property_id">
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
				<strong>Week of: </strong><input type="date" name="week_of" value="<?php echo date('Y-m-d', $first_day_of_this_week) ?>"/>
				<input type="submit" value="Search" />
			</form>
		</div>
<?php
	if (!empty($main_info))
	{
?>
		<form method="post" accept-charset="utf-8" action="">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			</div>

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
		if ($search_by_property_id > 0)
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
					<tr><td class="title send-email"><?php echo $btn_send_email ?></td><td class="desc">ALL PROPERTIES</td><td colspan="6"></td></tr>
				</thead>
<?php	
		}
?>
				<thead class="header2">
					<tr class="sticky-under-menu">
						<th>Vendor Date</th>
						<th>Property</th>
						<th>Unit#</th>
						<th>Size</th>
						<th>P.O #</th>
						<th>Remarks</th>
					</tr>
				</thead>
				<tbody>
<?php
		foreach($main_info as $cur_info)
		{
			$po_number = ($cur_info['vendor_id'] > 0) ? '<input type="text" name="po_number['.$cur_info['id'].']" value="'.html_encode($cur_info['po_number']).'" />' : '';
			$remarks = ($cur_info['vendor_id'] > 0) ? '<textarea name="remarks['.$cur_info['id'].']">'.html_encode($cur_info['remarks']).'</textarea>' : html_encode($cur_info['remarks']);
?>
					<tr>
						<td class="date">
							<p><strong><?php echo html_encode($cur_info['vendor_name']) ?></strong></p>
							<p><strong><?php echo format_time($cur_info['date_time'], 1) ?></strong></p>
							<p><?php echo $VCRVendors->get_servise_name($cur_info['vendor_group_id']) ?></p>
						</td>
						<td class=""><?php echo html_encode($cur_info['pro_name']) ?></td>
						<td class=""><?php echo html_encode($cur_info['unit_number']) ?></td>
						<td class=""><?php echo html_encode($cur_info['unit_size']) ?></td>
						<td class="po"><?php echo $po_number ?></td>
						<td class="comment"><?php echo $remarks ?></td>
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
		<div class="ct-box warn-box">
			<p>The list is empty or not found within your search criteria.</p>
		</div>
<?php
	}
?>
	</div>
	
<?php if ($search_by_property_id > 0 && file_exists('files/schedule_for_property.pdf')) : ?>
	<div class="ct-group">
		<div class="main-subhead">
			<a href="files/schedule_for_property.pdf?<?php echo time() ?>" target="_blank"><img class="print" src="<?php echo BASE_URL ?>/img/print.png" title="Print page"></a>
			<h2 class="hn"><span>Schedule Preview</span></h2>
		</div>
		<iframe name="emergency_schedule" id="demo_iframe" src="files/schedule_for_property.pdf?<?php echo time() ?>"></iframe>
	</div>
<?php else: ?>
	<div class="ct-group">
		<div class="main-subhead">
			<h2 class="hn"><span>Schedule Preview</span></h2>
		</div>
		<div class="ct-box warn-box">
			<p>PDF preview is available only when you select one property.</p>
		</div>
	</div>
<?php endif; ?>
</div>


<div class="email-window" style="display:none">
	<form method="post" accept-charset="utf-8" action="">
		<div class="hidden">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
		</div>
		<div class="edit-assign">
			<div class="title">
				<span class="close-window"><img src="<?php echo BASE_URL ?>/img/close.png" width="18px" onclick="closeWindows()"></span>
				<p>Send Invoice to Email</p>
			</div>
			<p class="subject"><input type="text" name="mail_subject" value="HCA Vendor Schedule"></p>
			<p style="display:none"><textarea name="email_list" rows="2" placeholder="Enter emails separated by commas"></textarea></p>
			<p>
				<input type="radio" name="mail_type" value="4" checked onchange="mailingType(4)"> Each Property 
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
	else if (v==3)
		$('.email-window textarea[name="email_list"]').val('<?php echo isset($property['manager_email']) ? html_encode($property['manager_email']) : '' ?>');
	else if (v==4)
		$('.email-window textarea[name="email_list"]').val('');
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