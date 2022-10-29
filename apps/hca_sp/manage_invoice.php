<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$access = ($User->checkAccess('hca_sp', 12)) ? true : false;
if (!$access)
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = isset($_GET['action']) ? swift_trim($_GET['action']) : '';
if ($id < 1)
	message($lang_common['Bad request']);

$query = array(
	'SELECT'	=> 'id, realname, email, sm_sp_mailing',
	'FROM'		=> 'users',
	'ORDER BY'	=> 'realname',
	'WHERE'		=> 'sm_special_projects_access > 0'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$project_managers = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$project_managers[] = $fetch_assoc;
}



$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'sm_property_db',
	'ORDER BY'	=> 'pro_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $fetch_assoc;
}

//Get cur project info
$query = array(
	'SELECT'	=> 'pj.*, pt.pro_name, u1.realname AS first_manager, u2.realname AS second_manager',
	'FROM'		=> 'sm_special_projects_records AS pj',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=pj.property_id'
		),
		array(
			'LEFT JOIN'		=> 'users AS u1',
			'ON'			=> 'u1.id=pj.project_manager_id'
		),
		array(
			'LEFT JOIN'		=> 'users AS u2',
			'ON'			=> 'u2.id=pj.second_manager_id'
		),
	),
	'WHERE'		=> 'pj.id='.$id,
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$project_info = $DBLayer->fetch_assoc($result);

if (empty($project_info))
	message('Sorry, this Special Project does not exist or has been removed.');

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'sm_special_projects_invoices',
	'WHERE'		=> 'project_id='.$id,
	'ORDER BY'	=> 'id'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$invoice_info = array();
while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
	$invoice_info[] = $fetch_assoc;
}

$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'sm_vendors',
	'WHERE'		=> 'hca_sp=1',
	'ORDER BY'	=> 'vendor_name'
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$vendors_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$vendors_info[$row['id']] = $row;
}

if (isset($_POST['update']))
{
	$total = 0;
	if (isset($_POST['form']) && !empty($_POST['form']))
	{
		foreach($_POST['form'] as $inv_id => $data)
		{
			$form_data = array();
			
			if (isset($data['vendor_id'])) 
				$form_data['vendor_id'] = intval($data['vendor_id']);
			if ($form_data['vendor_id'] > 0) 
				$form_data['vendor'] = isset($vendors_info[$form_data['vendor_id']]) ? swift_trim($vendors_info[$form_data['vendor_id']]['vendor_name']) : '';
			if (isset($data['work_performed'])) $form_data['work_performed'] = swift_trim($data['work_performed']);
			if (isset($data['po_number'])) $form_data['po_number'] = swift_trim($data['po_number']);
			if (isset($data['price'])) $form_data['price'] = is_numeric($data['price']) ? $data['price'] : 0;
			if (isset($data['change_order'])) $form_data['change_order'] = intval($data['change_order']);
			if (isset($data['lean_release'])) $form_data['lean_release'] = intval($data['lean_release']);
			if (isset($data['ok_to_pay'])) $form_data['ok_to_pay'] = intval($data['ok_to_pay']);
			if (isset($data['completed'])) $form_data['completed'] = intval($data['completed']);
			
			$DBLayer->update_values('sm_special_projects_invoices', $inv_id, $form_data);
			
			$total = $total + (isset($form_data['price']) ? $form_data['price'] : 0);
		}
	}
	
	if (isset($_POST['new']) && !empty($_POST['new']))
	{
		$vendor_id = isset($_POST['new']['vendor_id']) ? intval($_POST['new']['vendor_id']) : 0;
		$new_data = array(
			'vendor_id'			=> $vendor_id,
			'vendor'			=> isset($vendors_info[$vendor_id]) ? swift_trim($vendors_info[$vendor_id]['vendor_name']) : '',
			'work_performed'	=> isset($_POST['new']['work_performed']) ? swift_trim($_POST['new']['work_performed']) : '',
			'po_number'			=> isset($_POST['new']['po_number']) ? swift_trim($_POST['new']['po_number']) : '',
			'price'				=> isset($_POST['new']['price']) && is_numeric($_POST['new']['price']) ? $_POST['new']['price'] : 0,
			'change_order'		=> isset($_POST['new']['change_order']) ? intval($_POST['new']['change_order']) : 0,
//			'lean_release'		=> isset($_POST['new']['lean_release']) ? intval($_POST['new']['lean_release']) : 0,
//			'ok_to_pay'			=> isset($_POST['new']['ok_to_pay']) ? intval($_POST['new']['ok_to_pay']) : 0,
			'completed'			=> isset($_POST['new']['completed']) ? intval($_POST['new']['completed']) : 0,
			'project_id'		=> $project_info['id']
		);
		
		if ($new_data['vendor_id'] > 0 || $new_data['work_performed'] != '' || $new_data['po_number'] != '' || $new_data['price'] != '')
			$new_id = $DBLayer->insert_values('sm_special_projects_invoices', $new_data);
		
		$total = $total + $new_data['price'];
	}
	
	if (empty($Core->errors))
	{
		$query = array(
			'UPDATE'	=> 'sm_special_projects_records',
			'SET'		=> 'cost=\''.$DBLayer->escape($total).'\'',
			'WHERE'		=> 'id='.$id
		);
		$DBLayer->query_build($query) or error(__FILE__, __LINE__);
		
		// Send email if Over-budget
		if ($total > $project_info['budget'])
		{
			if ($project_info['project_manager_id'] > 0)
			{
				$subject = 'HCA: Over-Budget in Special Project';

				$mail_message = 'The total cost of the invoices exceeds the project budget. '."\n\n";
				$mail_message .= 'Property name: '.$project_info['pro_name']."\n";
				$mail_message .= 'Project Manager: '.$project_info['project_manager']."\n";
				$mail_message .= 'Description: '.(($project_info['project_desc'] != '') ? html_encode($project_info['project_desc']) : 'n/a')."\n";
				$mail_message .= 'Remarks: '.(($project_info['remarks'] != '') ? html_encode($project_info['remarks']) : 'n/a')."\n";
				$mail_message .= 'Start Date: '.format_time($project_info['start_date'],1)."\n";
				$mail_message .= 'End Date: '.format_time($project_info['end_date'],1)."\n";
				$mail_message .= 'Budget: $'.gen_number_format($project_info['budget'], 2)."\n";
				$mail_message .= 'Total cost: $'.gen_number_format($project_info['cost'], 2)."\n";
				$mail_message .= "\n".'Follow this link to view the invoice: '.$URL->link('sm_special_projects_manage_invoice', $id)."\n\n";
				
				if (!empty($project_managers))
				{
					foreach($project_managers as $cur_manager)
					{
						if (sm_sp_check_mailing(3, $cur_manager['sm_sp_mailing']))
						{
							$SwiftMailer = new SwiftMailer;
							$SwiftMailer->send($cur_manager['email'], $subject, $mail_message);
						}
					}
				}
			}
		}
		
		// Add flash message
		$flash_message = 'Invoice of Project #'.$id.' has been updated';
		$FlashMessenger->add_info($flash_message);
		redirect('', $flash_message);
	}
}
else if (isset($_POST['delete']))
{
	$invid = intval(key($_POST['delete']));
	
	$query = array(
		'DELETE'	=> 'sm_special_projects_invoices',
		'WHERE'		=> 'id='.$invid
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	
	$total = 0;
	if (isset($_POST['form']) && !empty($_POST['form'])){
		foreach($_POST['form'] as $inv_id => $data){
			$form_price = is_numeric($data['price']) ? $data['price'] : 0;
			$total = $total + $form_price;
		}
	}
	
	// UPDATE TOTAL PRICE
	$query = array(
		'UPDATE'	=> 'sm_special_projects_records',
		'SET'		=> 'cost='.$total,
		'WHERE'		=> 'id='.$id
	);
	$DBLayer->query_build($query) or error(__FILE__, __LINE__);
	
	// Add flash message
	$flash_message = 'Invoice of Project #'.$id.' has been deleted';
	$FlashMessenger->add_info($flash_message);
	redirect('', $flash_message);
}

$next_event = sm_special_projects_check_next_event($project_info['id'], $event_alert);
if ($event_alert)
	$Core->add_warning('Upcoming Work! '.$next_event);
		
if ($project_info['cost'] > $project_info['budget'])
	$Core->add_warning('Total price $'.gen_number_format($project_info['cost'], 2).' is more than an Budget $'.gen_number_format($project_info['budget'], 2));

if ($action == 'print' || $project_info['work_status'] != 1)
{
	$Core->set_page_title('Invoice');
	$Core->set_page_id('sm_special_projects_manage_invoice', 'hca_sp');
	
	require SITE_ROOT.'header.php';
	require 'manage_invoice_print.php';
	require SITE_ROOT.'footer.php';
}
else
{
	$Core->set_page_title('Invoice');
	$Core->set_page_id('sm_special_projects_manage_invoice', 'hca_sp');
	require SITE_ROOT.'header.php';
?>

<style>
tbody textarea, tbody input[type="text"], tfoot textarea, tfoot input[type="text"]{width:95%}
tbody textarea{min-width:250px;}
tbody input[type="text"]{min-width:100px;}
tbody td{padding:0;}
textarea:focus {height: 40px;}
.action{text-align:center;}
.total .total-desc{text-align: right;}
.total .total-price{text-align: left;}
.total-price, .total-desc{font-weight: bold;}
.search-box{float:right;margin: 3px 10px 0 0;}
.red-alert{color:red;}
tbody .total td{background: #d1d7fe;padding:5px;}
.new-row{text-align:center;}
.invoice .new-head td{background: #8fb7ff;}
</style>

<div class="main-content main-frm">
	<div class="ct-group">
		<div class="ct-set warn-set">
			<div class="ct-box warn-box">
				<h6 class="ct-legend hn warn"><span>Project Information:</span></h6>
				<p>Created: <strong><?php echo format_time($project_info['created_date']) ?></strong></p>
				<p>Project ID: <strong><?php echo html_encode($project_info['project_number']) ?></strong></p>
				<p>Property: <strong><?php echo html_encode($project_info['pro_name']) ?></strong></p>
	<?php if ($project_info['unit_number'] != ''): ?>
				<p>Unit number: <strong><?php echo html_encode($project_info['unit_number']) ?></strong></p>
	<?php endif; ?>
				<p>Description: <strong><?php echo html_encode($project_info['project_desc']) ?></strong></p>
				<p>Budget: <strong>$<?php echo gen_number_format($project_info['budget'], 2) ?></strong></p>
			</div>
		</div>
		
		<form method="post" accept-charset="utf-8" action="">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			</div>
			<table class="invoice">
				<thead>
					<tr>
						<th>Vendor</th>
						<th>Work Performed</th>
						<th>PO Number</th>
						<th>Price</th>
						<th>Change Order</th>
						<th>Prelim Release</th>
						<th>OK to Pay</th>
						<th>Contract Completed</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
<?php
	$price = 0;
	if (!empty($invoice_info))
	{
		foreach ($invoice_info as $cur_info)
		{
			$disabled_field = $disabled_dropdown = '';
			$cur_price = number_format($cur_info['price'], 2, '.', '');
?>
					<tr>
						<td>

							<select name="form[<?php echo $cur_info['id'] ?>][vendor_id]">
<?php
			echo '<option value="0" selected="selected">Select a Vendor</option>'."\n";
			foreach ($vendors_info as $vendor)
			{
				if ($cur_info['vendor_id'] == $vendor['id'])
					echo "\t\t\t\t\t\t\t".'<option value="'.$vendor['id'].'" selected="selected">'.html_encode($vendor['vendor_name']).'</option>'."\n";
				else
					echo "\t\t\t\t\t\t\t".'<option value="'.$vendor['id'].'">'.html_encode($vendor['vendor_name']).'</option>'."\n";
			}
?>
							</select>

						</td>
						<td>

							<textarea name="form[<?php echo $cur_info['id'] ?>][work_performed]" rows="2" cols="20"><?php echo html_encode($cur_info['work_performed']) ?></textarea>

						</td>
						<td>

							<input type="text" name="form[<?php echo $cur_info['id'] ?>][po_number]" value="<?php echo html_encode($cur_info['po_number']) ?>" size="15" <?php echo $disabled_field ?>/>
						
						</td>

						<td>

							<input type="text" name="form[<?php echo $cur_info['id'] ?>][price]" value="<?php echo $cur_price ?>" size="10" placeholder="$ 0.00" />

						</td>
						<td>
<?php 
$change_order = array(0 => 'NO', 1 => 'YES');
?>
							<select name="form[<?php echo $cur_info['id'] ?>][change_order]">
<?php 
			foreach ($change_order as $key => $val)
			{
				if ($key == $cur_info['change_order']) {
					echo '<option value="'.$key.'" selected="selected">'.$val.'</option>';
				} else { echo '<option value="'.$key.'">'.$val.'</option>'; }
			}
?>
							</select>

						</td>
						<td>
<?php
$lean_release = array(0 => 'NO', 1 => 'YES');
?>
							<select name="form[<?php echo $cur_info['id'] ?>][lean_release]">
<?php 
			foreach ($lean_release as $key => $val)
			{
				if ($key == $cur_info['lean_release']) {
					echo '<option value="'.$key.'" selected="selected">'.$val.'</option>';
				} else { echo '<option value="'.$key.'">'.$val.'</option>'; }
			}
?>
							</select>

						</td>
						<td>
<?php 
$ok_to_pay = array(0 => 'NO', 1 => 'YES');
if ($cur_info['lean_release'] == 1): ?>
							<select name="form[<?php echo $cur_info['id'] ?>][ok_to_pay]">
<?php 
			foreach ($ok_to_pay as $key => $val)
			{
				if ($key == $cur_info['ok_to_pay']) {
					echo '<option value="'.$key.'" selected="selected">'.$val.'</option>';
				} else { echo '<option value="'.$key.'">'.$val.'</option>'; }
			}
?>
							</select>
<?php else: ?>
							<strong><?php echo $ok_to_pay[$cur_info['ok_to_pay']] ?></strong>
<?php endif; ?>
						</td>
						<td>
<?php 
$completed = array(0 => 'NO', 1 => 'YES');
?>
							<select name="form[<?php echo $cur_info['id'] ?>][completed]">
<?php
			foreach ($completed as $key => $val)
			{
				if ($key == $cur_info['completed']) {
					echo '<option value="'.$key.'" selected="selected">'.$val.'</option>';
				} else { echo '<option value="'.$key.'">'.$val.'</option>'; }
			}
?>
							</select>

						</td>
						<td>

							<span class="submit primary caution"><input type="submit" name="delete[<?php echo $cur_info['id'] ?>]" value="X" onclick="return confirm('Are you sure you want to delete this item?')"/></span>

						</td>
					</tr>
<?php
			$price = $price + $cur_info['price'];
		}
	}
?>
					<tr class="total">
						<td colspan="2"></td>
						<td class="total-desc">Total Cost:</td>
						<td class="total-price <?php echo ($project_info['cost'] > $project_info['budget']) ? 'red-alert' : '' ?>">$ <?php echo $cur_price = gen_number_format($price, 2); ?></td>
						<td colspan="5"></td>
					</tr>
				</tbody>
				<tfoot>

					<tr><td colspan="9" class="new-head"><strong>To add a new row, just fill in row below and press "Update".</strong></td></tr>
					<tr class="new-row">
						<td><select name="new[vendor_id]">
<?php
echo '<option value="" selected="selected">Select a Vendor</option>'."\n";
foreach ($vendors_info as $vendor) {
	if (isset($_POST['new']['vendor_id']) && $_POST['new']['vendor_id'] == $vendor['id'])
		echo "\t\t\t\t\t\t\t".'<option value="'.$vendor['id'].'" selected="selected">'.html_encode($vendor['vendor_name']).'</option>'."\n";
	else
		echo "\t\t\t\t\t\t\t".'<option value="'.$vendor['id'].'">'.html_encode($vendor['vendor_name']).'</option>'."\n";
}
?>
						</select>
					</td>
						<td><textarea name="new[work_performed]" rows="2" cols="20"><?php echo isset($_POST['new']['work_performed']) ? html_encode($_POST['new']['work_performed']) : '' ?></textarea></td>
						<td><input type="text" name="new[po_number]" value="<?php echo isset($_POST['new']['po_number']) ? html_encode($_POST['new']['po_number']) : '' ?>"/></td>
						<td><input type="text" name="new[price]" placeholder="$ 0.00" value="<?php echo isset($_POST['new']['new_price']) ? html_encode($_POST['new']['price']) : '' ?>"/></td>
						<td><select name="new[change_order]">
							<option value="0">NO</option>
							<option value="1">YES</option>
						</select></td>
						<td>NO</td>
						<td>NO</td>
						<td>NO</td>
						<td></td>
					</tr>

					<tr><td colspan="9" class="action"><span class="submit primary"><input type="submit" name="update" value="Update" /></span></td></tr>
				</tfoot>
			</table>
		</form>
	</div>
</div>

<?php
	require SITE_ROOT.'footer.php';
}