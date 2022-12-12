<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_mi', 13)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';
if ($id < 1)
	message($lang_common['Bad request']);

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'hca_5840_projects',
	'WHERE'		=> 'id='.$id,
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $DBLayer->fetch_assoc($result);

$query = array(
	'SELECT'	=> 'id, realname, email',
	'FROM'		=> 'users',
	//'WHERE'		=> 'hca_5840_access > 0'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$project_managers = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$project_managers[$row['id']] = $row['email'];
}

if (isset($_POST['form_sent']))
{
	$form_data = array();
	
	if (isset($_POST['form']['asb_vendor'])) $form_data['asb_vendor'] = swift_trim($_POST['form']['asb_vendor']);
	if (isset($_POST['form']['asb_comment'])) $form_data['asb_comment'] = swift_trim($_POST['form']['asb_comment']);
	if (isset($_POST['form']['asb_po_number'])) $form_data['asb_po_number'] = swift_trim($_POST['form']['asb_po_number']);
	if (isset($_POST['form']['asb_total_amount'])) 
		$form_data['asb_total_amount'] = is_numeric($_POST['form']['asb_total_amount']) ? $_POST['form']['asb_total_amount'] : 0;
	else $form_data['asb_total_amount'] = 0;
	
	
	if (isset($_POST['form']['rem_vendor'])) $form_data['rem_vendor'] = swift_trim($_POST['form']['rem_vendor']);
	if (isset($_POST['form']['rem_comment'])) $form_data['rem_comment'] = swift_trim($_POST['form']['rem_comment']);
	if (isset($_POST['form']['rem_po_number'])) $form_data['rem_po_number'] = swift_trim($_POST['form']['rem_po_number']);
	if (isset($_POST['form']['rem_total_amount'])) 
		$form_data['rem_total_amount'] = is_numeric($_POST['form']['rem_total_amount']) ? $_POST['form']['rem_total_amount'] : 0;
	else $form_data['rem_total_amount'] = 0;
	
	if (isset($_POST['form']['cons_vendor'])) $form_data['cons_vendor'] = swift_trim($_POST['form']['cons_vendor']);
	if (isset($_POST['form']['cons_comment'])) $form_data['cons_comment'] = swift_trim($_POST['form']['cons_comment']);
	if (isset($_POST['form']['cons_po_number'])) $form_data['cons_po_number'] = swift_trim($_POST['form']['cons_po_number']);
	if (isset($_POST['form']['cons_total_amount'])) 
		$form_data['cons_total_amount'] = is_numeric($_POST['form']['cons_total_amount']) ? $_POST['form']['cons_total_amount'] : 0;
	else $form_data['cons_total_amount'] = 0;
	
	$total_cost = $form_data['asb_total_amount'] + $form_data['rem_total_amount'] + $form_data['cons_total_amount']; 
	
	if (!empty($form_data))
	{
		$DBLayer->update('hca_5840_projects', $form_data, $id);
		
		if ($total_cost >= 5000)
		{
			$mail_subject = 'HCA: Moisture Inspection';
			$mail_message = 'Hello. The total cost of the project exceeded $ 5,000. See details bellow.'."\n\n";

			$mail_message .= 'Property: '.$main_info['property_name']."\n\n";
			$mail_message .= 'Unit #: '.$main_info['unit_number']."\n\n";
			$mail_message .= 'Location: '.$main_info['location']."\n\n";
			$mail_message .= 'Report Date: '.date('m/d/Y', $main_info['mois_report_date'])."\n\n";
			$mail_message .= 'Performed by: '.$main_info['mois_performed_by']."\n\n";
			$mail_message .= 'Inspection Date: '.date('m/d/Y', $main_info['mois_inspection_date'])."\n\n";
			$mail_message .= 'Source: '.$main_info['mois_source']."\n\n";
			$mail_message .= 'Symptoms: '.$main_info['symptoms']."\n\n";
			$mail_message .= 'Action: '.$main_info['action']."\n\n";
			$mail_message .= 'Remarks: '.$main_info['remarks']."\n\n";
				
			$mail_message .= 'Total cost: $ '.$total_cost."\n\n";
			$mail_message .= 'To view all the details of the project follow this link: '.get_cur_url()."\n\n";
			
			$email_list = implode(',', $project_managers);
			if ($email_list != '' && $main_info['over_price_notified'] == 0)
			{
				$SwiftMailer = new SwiftMailer;
				$SwiftMailer->isHTML();
				$SwiftMailer->send($email_list, $mail_subject, $mail_message);
				
				
				$query = array(
					'UPDATE'	=> 'hca_5840_projects',
					'SET'		=> 'over_price_notified=1',
					'WHERE'		=> 'id='.$id
				);
				$DBLayer->query_build($query) or error(__FILE__, __LINE__);
			}
		}
		
		$flash_message = 'Invoice #'.$id.' has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'sm_vendors',
	'WHERE'		=> 'hca_5840=1',
	'ORDER BY'	=> 'vendor_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$vendors_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$vendors_info[] = $row;
}

$asb_total_amount = is_numeric($main_info['asb_total_amount']) ? number_format($main_info['asb_total_amount'], 2, '.', '') : 0;
$rem_total_amount = is_numeric($main_info['rem_total_amount']) ? number_format($main_info['rem_total_amount'], 2, '.', '') : 0;
$cons_total_amount = is_numeric($main_info['cons_total_amount']) ? number_format($main_info['cons_total_amount'], 2, '.', '') : 0;
$total_cost = $asb_total_amount + $rem_total_amount + $cons_total_amount;
$all_vendor_detector = ($main_info['asb_vendor'] == '' && $main_info['rem_vendor'] == '' && $main_info['cons_vendor'] == '') ? false : true;
$any_vendor_detector = ($main_info['asb_vendor'] == '' || $main_info['rem_vendor'] == '' || $main_info['cons_vendor'] == '') ? false : true;

if ($total_cost >= 5000)
	$Core->add_warning('The total cost of the project exceeded $ 5,000.');

if (!$any_vendor_detector)
	$Core->add_warning('To display more vendors in the invoice, go to Manage Project and select a vendor.');

//$Core->set_page_title('Invoice');
$Core->set_page_id('hca_5840_manage_invoice', 'hca_5840');
require SITE_ROOT.'header.php';

?>
<style>
table {table-layout: initial;}
thead .header1 th {font-weight: bold;padding: 5px;background: #dfe3ff;}
thead .header2 th {font-weight: bold;padding: 5px;background: #dfe3ff;text-align: center;}
tbody td {background: #eceef8;}
tfoot td {background: #e7def4;}
.ct-group .po_number,.ct-group .price{width:170px;}
.ct-group .vendor{min-width:200px;width:200px;}
.ct-group .comment{min-width:250px;}
textarea:focus {height: 40px;}
td input[type="text"], .ct-group textarea{width:95%;}
tbody .total{font-weight: bold;}
thead .title{text-align: right;background: #f1f2ff;}
</style>

	<form method="post" accept-charset="utf-8" action="">
		<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />

			<table class="table table-striped table-bordered">
				<thead>
					<tr class="header1"><th></th><th colspan="3"><strong>Project Information</strong></th></tr>
					<tr><td class="title">Property:</td><td colspan="3"><?php echo html_encode($main_info['property_name']) ?></td></tr>
					<tr><td class="title">Unit #:</td><td colspan="3"><?php echo html_encode($main_info['unit_number']) ?></td></tr>
					<tr><td class="title">Location:</td><td colspan="3"><?php echo html_encode($main_info['location']) ?></td></tr>
					<tr><td class="title">Date Reported:</td><td colspan="3"><?php echo format_time($main_info['mois_report_date'], 1) ?></td></tr>
					<tr><td class="title">Performed by:</td><td colspan="3"><?php echo html_encode($main_info['mois_performed_by']) ?></td></tr>
					<tr class="header2">
						<th>Vendor</th>
						<th>Work Performed</th>
						<th>PO Number</th>
						<th>Cost</th>
					</tr>
				</thead>
				<tbody>
<?php if ($main_info['asb_vendor'] != '') : ?>
					<tr>
						<td class="vendor"><span>Asbestos:</span>
							<select name="form[asb_vendor]">
<?php
echo '<option value="" selected="selected">Select Vendor</option>'."\n";
foreach ($vendors_info as $vendor) {
	if ($main_info['asb_vendor'] == $vendor['vendor_name'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$vendor['vendor_name'].'" selected="selected">'.html_encode($vendor['vendor_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$vendor['vendor_name'].'">'.html_encode($vendor['vendor_name']).'</option>'."\n";
}
?>
							</select>
						</td>
						<td class="comment"><textarea name="form[asb_comment]"><?php echo html_encode($main_info['asb_comment']) ?></textarea></td>
						<td class="po_number"><input type="text" name="form[asb_po_number]" value="<?php echo html_encode($main_info['asb_po_number']) ?>"/></td>
						<td class="price"><input type="text" name="form[asb_total_amount]" value="<?php echo $asb_total_amount ?>"/></td>
					</tr>
<?php endif; ?>
<?php if ($main_info['rem_vendor'] != '') : ?>
					<tr>
						<td class="vendor"><span>Remediation:</span>
							<select name="form[rem_vendor]">
<?php
echo '<option value="" selected="selected">Select Vendor</option>'."\n";
foreach ($vendors_info as $vendor) {
	if ($main_info['rem_vendor'] == $vendor['vendor_name'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$vendor['vendor_name'].'" selected="selected">'.html_encode($vendor['vendor_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$vendor['vendor_name'].'">'.html_encode($vendor['vendor_name']).'</option>'."\n";
}
?>
							</select>
						</td>
						<td class="comment"><textarea name="form[rem_comment]"><?php echo html_encode($main_info['rem_comment']) ?></textarea></td>
						<td class="po_number"><input type="text" name="form[rem_po_number]" value="<?php echo html_encode($main_info['rem_po_number']) ?>"/></td>
						<td class="price"><input type="text" name="form[rem_total_amount]" value="<?php echo $rem_total_amount ?>"/></td>
					</tr>
<?php endif; ?>
<?php if ($main_info['cons_vendor'] != '') : ?>
					<tr>
						<td class="vendor"><span>Constructions:</span>
							<select name="form[cons_vendor]">
<?php
echo '<option value="" selected="selected">Select Vendor</option>'."\n";
foreach ($vendors_info as $vendor) {
	if ($main_info['cons_vendor'] == $vendor['vendor_name'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$vendor['vendor_name'].'" selected="selected">'.html_encode($vendor['vendor_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$vendor['vendor_name'].'">'.html_encode($vendor['vendor_name']).'</option>'."\n";
}
?>
							</select>
						</td>
						<td class="comment"><textarea name="form[cons_comment]"><?php echo html_encode($main_info['cons_comment']) ?></textarea></td>
						<td class="po_number"><input type="text" name="form[cons_po_number]" value="<?php echo html_encode($main_info['cons_po_number']) ?>"/></td>
						<td class="price"><input type="text" name="form[cons_total_amount]" value="<?php echo $cons_total_amount ?>"/></td>
					</tr>
<?php endif; ?>
					<tr>
						<td colspan="2"><?php echo (!$all_vendor_detector) ? 'No Vendors selected in Manage Project' : '' ?></td>
						<td>
<?php if ($access && $all_vendor_detector): ?>
							<span class="submit primary"><input type="submit" name="form_sent" value="Update" /></span>
<?php endif; ?>
						</td>
						<td>Total: $<span class="total"><?php echo gen_number_format($total_cost, 2) ?></span></td>
					</tr>
				</tbody>
			</table>

	</form>

<?php
require SITE_ROOT.'footer.php';