<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';
require 'functions_generate_pdf.php';

$access = ($User->is_admmod() || $User->get('hca_vcr_access') > 0) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$gid = isset($_GET['gid']) ? intval($_GET['gid']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';
$search_by_vendor_id = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0;
$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$search_by_unit_number = isset($_GET['unit_number']) ? swift_trim($_GET['unit_number']) : '';
$week_of = isset($_GET['week_of']) && $_GET['week_of'] != '' ? strtotime($_GET['week_of']) : time();
$first_day_of_this_week = isset($_GET['week_of']) ? strtotime('Monday this week', $week_of) : strtotime('Monday this week');
$first_day_of_next_week = isset($_GET['week_of']) ? strtotime('Monday next week', $week_of) : strtotime('Monday next week');

$time_slots = array(0 => 'ALL DAY', 1 => 'A.M.', 2 => 'P.M.');
$query = array(
	'SELECT'	=> 'i.*, pj.unit_number, pj.unit_size, pt.pro_name',
	'FROM'		=> 'hca_vcr_invoices AS i',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'hca_vcr_projects AS pj',
			'ON'			=> 'pj.id=i.project_id'
		),
		array(
			'INNER JOIN'	=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=pj.property_id'
		)
	),
	'WHERE'		=> 'pj.status=0 AND i.in_house=1 AND i.date_time >= '.$first_day_of_this_week.' AND i.date_time < '.$first_day_of_next_week,
	'ORDER BY'	=> 'i.date_time',
);

if ($gid == $Config->get('o_hca_fs_painters'))
	$query['WHERE'] .= ' AND i.vendor_group_id=2';
else
	$query['WHERE'] .= ' AND i.vendor_group_id=8';

$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$main_info[$row['id']] = $row;
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
		
		// Add flash message
		$flash_message = 'Weekly schedule has been updated';
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
	if (!hca_sp_gen_inhouse_pdf_shedule())
		$Core->add_error('Can not create PDF file');
	
	if (empty($Core->errors))
	{
/*
		$emails_array = explode(',', $email_list);
		foreach($emails_array as $cur_email) {
			if ($cur_email != '')
				//
		}

		$SwiftMailer = new SwiftMailer;
		//$SwiftMailer->isHTML();
		$SwiftMailer->send($emails, 'Moisture Project', $mail_message);
*/
		if (file_exists('files/weekly_schedule.pdf'))
			unlink('files/weekly_schedule.pdf');
		
		// Add flash message
		$flash_message = 'Email has been sent to: '.$email_list;
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

if (file_exists('files/weekly_schedule.pdf'))
	unlink('files/weekly_schedule.pdf');

if (!hca_sp_gen_inhouse_pdf_shedule())
	$Core->add_warning('Can not create PDF file');
else
	$Core->add_page_action('<a href="files/weekly_schedule.pdf?'.time().'" target="_blank"><i class="fa fa-file-pdf-o fa-2x"></i>Print as PDF</a>');

//$Core->set_page_title(($gid == $Config->get('o_hca_fs_painters')) ? 'Painter Schedule' : 'Maintenance Schedule');

if ($gid == $Config->get('o_hca_fs_painters'))
	$Core->set_page_id('hca_vcr_paint_schedule', 'hca_vcr');
else
	$Core->set_page_id('hca_vcr_maint_schedule', 'hca_vcr');

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
.email-window {width: 300px;position: absolute;background: #dcf5e5;padding: 5px;line-height: 23px;border-radius: 10px;border: 1px solid #A5A5A5;z-index: 300;opacity: 0.9;left: 100px;}
.close-window{cursor: pointer;float: right;position: relative;z-index: 300;}
.close-window img{padding: .4em;}
.email-window textarea{width:97%;}
.mailing-fields{columns: 2;padding-left: 5px;}
.email-window .btn-action{text-align: center;}
.send-email img{width:40px;cursor:pointer;}
.email-window .title{background: #e3eff4;border-style: groove;border-width: 2px;border-color: #f3f8fc;}
.email-window .title p{font-weight: bold;padding-left: .3em;}
.search-box input[type="date"]{font-weight: bold}
.subject input{width: 97%;}
#demo_iframe {width:100%; height:400px; zoom: 2;}
</style>
		
<div class="main-content main-frm">
	<div class="ct-group">
		<div class="search-box">
			<form method="get" accept-charset="utf-8" action="">
				<input type="hidden" name="gid" value="<?php echo $gid ?>" />
				<strong>Week of: </strong><input type="date" name="week_of" value="<?php echo date('Y-m-d', $first_day_of_this_week) ?>"/>
				<input type="submit" value="Search" />
			</form>
		</div>
<?php

	$schedule_type = ($gid == $Config->get('o_hca_fs_painters')) ? 'Painters' : 'Maintenance';
	$manager_name = ($gid == $Config->get('o_hca_fs_painters')) ? 'Jon Lenfestey' : 'Beto Talavera';
	$manager_email = ($gid == $Config->get('o_hca_fs_painters')) ? 'jon@hcares.com' : 'talavera@hcares.com';

	if (!empty($main_info))
	{
?>
		<form method="post" accept-charset="utf-8" action="">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			</div>
			<table>
<?php
		if ($User->is_admmod())
		{
			$btn_send_email = (hca_vcr_check_perms(7)) ? '<button type="button" class="lightseagreen" onclick="emailWindow()">Send Email</button>' : 'Email: ';
?>
				<thead class="header1">
					<tr><th colspan="6"><strong>Information</strong></th></tr>
					<tr><td class="title">Week of: </td><td colspan="5" class="desc"><?php echo format_time($first_day_of_this_week, 1) ?></td></tr>
					<tr><td class="title">Name: </td><td colspan="5" class="desc"><?php echo $manager_name ?></td></tr>
					<tr><td class="title send-email"><?php echo $btn_send_email ?></td><td class="desc"><?php echo $manager_email ?></td><td colspan="5"></td></tr>
				</thead>
<?php
		}
?>	
				<thead class="header2">
					<tr class="sticky-under-menu">
						<th>Employee Name - Date</th>
						<th>Property</th>
						<th>Unit#</th>
						<th>Size</th>
						<th>Remarks</th>
					</tr>
				</thead>
				<tbody>
<?php
		foreach($main_info as $cur_info)
		{
?>
					<tr>
						<td class="date">
							<p><strong><?php echo ($gid == $Config->get('o_hca_fs_painters')) ? 'Painter' : 'Maintenance' ?></strong></p>
							<p><strong><?php echo format_time($cur_info['date_time'], 1) ?></strong></p>
							<p><strong><?php echo $time_slots[$cur_info['shift']] ?></strong></p>
						</td>
						<td><?php echo html_encode($cur_info['pro_name']) ?></td>
						<td><?php echo html_encode($cur_info['unit_number']) ?></td>
						<td><?php echo html_encode($cur_info['unit_size']) ?></td>
						<td class="comment"><?php echo html_encode($cur_info['remarks']) ?></td>
					</tr>
<?php
		}
?>
				</tbody>
			</table>
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

<?php if (file_exists('files/weekly_schedule.pdf')) : ?>
	<div class="content-head">
		<h6 class="hn"><span><?php echo ($gid == $Config->get('o_hca_fs_painters') ? 'Painter Schedule' : 'Maintenance Schedule') ?></span></h6>
	</div>
	<div class="ct-group">
		<iframe name="emergency_schedule" id="demo_iframe" src="files/weekly_schedule.pdf?<?php echo time() ?>"></iframe>
	</div>
<?php endif; ?>
</div>

<?php if ($User->is_admmod()): ?>
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
			<p class="subject"><input type="text" name="mail_subject" value="HCA <?php echo $schedule_type ?> Schedule"></p>
			<p><textarea name="email_list" rows="2" placeholder="Enter emails separated by commas"><?php echo $manager_email ?></textarea></p>
			<p><textarea name="mail_message" rows="8" placeholder="Write your message">Hello <?php echo $manager_name ?>.&#13;&#10;This is <?php echo $schedule_type ?> Schedule for the week of <?php echo format_time($first_day_of_this_week, 1) ?>.</textarea></p>
			<p>*Recipients will receive invoice list.</p>
			<p class="btn-action"><span class="submit primary"><input type="submit" name="send_email" value="Send Email" onclick="closeWindows()"/></span></p>
		</div>
	</form>
</div>

<script>
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

<?php endif;
require SITE_ROOT.'footer.php';