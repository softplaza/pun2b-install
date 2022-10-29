<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->is_admmod() || $User->get('hca_vcr_access') > 0) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';
if ($id < 1)
	message($lang_common['Bad request']);

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'hca_vcr_projects',
	'WHERE'		=> 'id='.$id,
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$project_info = $DBLayer->fetch_assoc($result);

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'hca_vcr_invoices',
	'WHERE'		=> 'project_id='.$id,
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$main_info[$row['id']] = $row;
}

$query = array(
	'SELECT'	=> 'id, realname, email',
	'FROM'		=> 'users',
	'WHERE'		=> 'hca_5840_access > 0'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$project_managers = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$project_managers[$row['id']] = $row['email'];
}

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'sm_vendors',
	'ORDER BY'	=> 'vendor_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$vendors_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$vendors_info[$row['id']] = $row;
}

if (isset($_POST['form_sent']))
{
	$ids = isset($_POST['ids']) ? $_POST['ids'] : array();
	
	$new_date_time = isset($_POST['new_date_time']) ? strtotime($_POST['new_date_time']) : 0;
	$new_vendor_id = isset($_POST['new_vendor_id']) ? intval($_POST['new_vendor_id']) : 0;
	$new_po_number = isset($_POST['new_po_number']) ? swift_trim($_POST['new_po_number']) : '';
	$new_remarks = isset($_POST['new_remarks']) ? swift_trim($_POST['new_remarks']) : '';
	
	if ($new_vendor_id > 0 || $new_po_number != '' || $new_remarks != '')
	{
		$new_vendor_name = isset($vendors_info[$new_vendor_id]) ? $vendors_info[$new_vendor_id]['vendor_name'] : '';
		$query = array(
			'INSERT'	=> 'project_id, date_time, vendor_id, vendor_name, po_number, remarks',
			'INTO'		=> 'hca_vcr_invoices',
			'VALUES'	=> 
				'\''.$DBLayer->escape($id).'\',
				\''.$DBLayer->escape($new_date_time).'\',
				\''.$DBLayer->escape($new_vendor_id).'\',
				\''.$DBLayer->escape($new_vendor_name).'\',
				\''.$DBLayer->escape($new_po_number).'\',
				\''.$DBLayer->escape($new_remarks).'\''
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$new_id = $DBLayer->insert_id();
	}
	
	if (!empty($ids))
	{
		foreach($ids as $key => $val)
		{
			$form_data = array();
			$form_data['date_time'] = isset($_POST['date_time'][$key]) ? strtotime($_POST['date_time'][$key]) : 0;
			$form_data['vendor_id'] = isset($_POST['vendor_id'][$key]) ? intval($_POST['vendor_id'][$key]) : 0;
			$form_data['po_number'] = isset($_POST['po_number'][$key]) ? swift_trim($_POST['po_number'][$key]) : '';
			$form_data['remarks'] = isset($_POST['remarks'][$key]) ? swift_trim($_POST['remarks'][$key]) : '';
			
			$DBLayer->update('hca_vcr_invoices', $form_data, $key);
		}
		
		$flash_message = 'Invoice has been updated: '.implode(', ', $ids);
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}

//$Core->set_page_title('Invoice');
$Core->set_page_id('hca_vcr_manage_invoice', 'hca_vcr');
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
</style>

<div class="main-content main-frm">
	<div class="ct-group">
		<form method="post" accept-charset="utf-8" action="">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			<table class="invoice">
				<thead class="header1">
					<tr><th></th><th colspan="3"><strong>Project Information</strong></th></tr>
					<tr><td class="title">Property: </td><td colspan="3" class="desc"><?php echo html_encode($project_info['property_name']) ?></td></tr>
					<tr><td class="title">Unit#: </td><td colspan="3" class="desc"><?php echo html_encode($project_info['unit_number']) ?></td></tr>
					<tr><td class="title">Size: </td><td colspan="3" class="desc"><?php echo html_encode($project_info['unit_size']) ?></td></tr>
					<tr><td class="title">Performed by:</td><td colspan="3" class="desc"><?php echo html_encode($project_info['submited_by']) ?></td></tr>
				</thead>
				<thead class="header2">
					<tr>
						<th>Date/Time</th>
						<th>Vendor</th>
						<th>P.O #</th>
						<th>Remarks</th>
					</tr>
				</thead>
				<tbody>
<?php
	if (!empty($main_info))
	{
		foreach($main_info as $cur_info)
		{
?>
					<tr>
						<input type="hidden" name="ids[<?php echo $cur_info['id'] ?>]" value="<?php echo $cur_info['id'] ?>">
						<td class="date"><input type="date" name="date_time[<?php echo $cur_info['id'] ?>]" value="<?php echo sm_date_input($cur_info['date_time']) ?>"/></td>
						<td class="vendor">
							<select name="vendor_id[<?php echo $cur_info['id'] ?>]">
<?php
		echo '<option value="0" selected disabled>List of Vendors</option>';
		foreach($vendors_info as $vendor_info) {
			if ($vendor_info['id'] == $cur_info['vendor_id'])
				echo '<option value="'.$vendor_info['id'].'" selected>'.$vendor_info['vendor_name'].'</option>';
			else
				echo '<option value="'.$vendor_info['id'].'">'.$vendor_info['vendor_name'].'</option>';
		}
?>
							</select>
						</td>
						<td class="po"><input type="text" name="po_number[<?php echo $cur_info['id'] ?>]" value="<?php echo html_encode($cur_info['po_number']) ?>"/></td>
						<td class="comment"><textarea name="remarks[<?php echo $cur_info['id'] ?>]"><?php echo html_encode($cur_info['remarks']) ?></textarea></td>
					</tr>
<?php
		}
	}
?>

					<tr class="new">
						<th>New Date/Time</th>
						<th>New Vendor</th>
						<th>New P.O #</th>
						<th>New Remarks</th>
					</tr>

					<tr class="new">
						<td class="date"><input type="date" name="new_date_time" value="<?php echo date('Y-m-d', time()) ?>"/></td>
						<td class="vendor"><select name="new_vendor_id">
<?php
		echo '<option value="0" selected disabled>Select New Vendor</option>';
		foreach($vendors_info as $vendor_info) {
			echo '<option value="'.$vendor_info['id'].'">'.$vendor_info['vendor_name'].'</option>';
		}
?>
							</select>
							<input type="hidden" name="new_vendor_name" value=""/>
						</td>
						<td class="po"><input type="text" name="new_po_number" value="" placeholder="New PO Nmber"/></td>
						<td class="comment"><textarea name="new_remarks" placeholder="Write your comment"></textarea></td>
					</tr>
					
					<tr>
						<td colspan="3"></td>
						<td><span class="submit primary"><input type="submit" name="form_sent" value="Update" /></span></td>
					</tr>
				</tbody>
			</table>
		</form>
	</div>
</div>

<?php
require SITE_ROOT.'footer.php';