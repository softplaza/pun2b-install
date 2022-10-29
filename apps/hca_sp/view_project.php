<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$permission = ($User->is_admmod()) ? true : false;
if (!$permission)
	message($lang_common['No view']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = isset($_GET['action']) ? swift_trim($_GET['action']) : '';

//Get cur form info
$query = array(
	'SELECT'	=> 'pj.*, pt.pro_name',
	'FROM'		=> 'sm_special_projects_records AS pj',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=pj.property_id'
		),
	),
	'WHERE'		=> 'pj.id='.$id,
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$form_info = $DBLayer->fetch_assoc($result);

if (empty($form_info))
	message('Sorry, this Special Project does not exist or has been removed.');

$work_statuses = array(
	1 => 'WORK STARTED',
	2 => 'BID',
	3 => 'WISH LIST',
	4 => 'ON HOLD',
	5 => 'COMPLETED',
);

// Setup the form
$page_param['fld_count'] = $page_param['group_count'] = $page_param['item_count'] = 0;

$Core->set_page_title('Project');
define('PAGE_ID', 'sm_special_projects_view');
require SITE_ROOT.'header.php';

if ($action == 'invoice')
{
	$query = array(
		'SELECT'	=> 'id, project_manager, property, vendor, work_performed, po_number, price, lean_release, change_order, ok_to_pay, payed, completed, project_id',
		'FROM'		=> 'sm_special_projects_invoices',
		'WHERE'		=> 'project_id='.$id,
		'ORDER BY'	=> 'id'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$invoice_info = array();
	while ($fetch_assoc = $DBLayer->fetch_assoc($result)) {
		$invoice_info[] = $fetch_assoc;
	}
?>

<style>
.ct-group {border-style: hidden;}
.brd .frm-group {border-color: #ffffff;}
.content-menu{background: #fff;}
.content-menu ul{padding: .1em 0;}
.content-menu li{display: inline;margin: -.2em;padding: .2em .5em;text-transform: uppercase;}
.content-menu .active{background: aliceblue;}
.content-menu .normal{padding: .1em .5em;border: 1px solid #8d999c;border-top: none;background: gainsboro;}
</style>

<div class="main-content main-frm">
	<div class="ct-group">
		<table>
			<thead>
				<tr>
					<th>Property</th>
					<th>Vendor</th>
					<th>Work Performed</th>
					<th>PO Number</th>
					<th>Price</th>
					<th>Change Order</th>
					<th>Lean Release</th>
					<th>OK to Pay</th>
					<th>Contract Completed</th>
					<th>Payed</th>
				</tr>
			</thead>
			<tbody>
<?php
		$count = 0;
		foreach ($invoice_info as $cur_info)
		{
?>
				<tr>
					<td><?php echo $cur_info['property'] ?></td>
					<td><?php echo $cur_info['vendor'] ?></td>
					<td><?php echo $cur_info['work_performed'] ?></td>
					<td><?php echo $cur_info['po_number'] ?></td>
					<td><?php echo $cur_info['price'] ?></td>
					<td><?php echo ($cur_info['change_order'] == 0 ? 'NO' : 'YES') ?></td>
					<td><?php echo ($cur_info['lean_release'] == 0 ? 'NO' : 'YES') ?></td>
					<td><?php echo ($cur_info['ok_to_pay'] == 0 ? 'NO' : 'YES') ?></td>
					<td><?php echo ($cur_info['completed'] == 0 ? 'NO' : 'YES') ?></td>
					<td><?php echo ($cur_info['payed'] == 0 ? 'NO' : 'YES') ?></td>
				</tr>
<?php
			$count = $count + $cur_info['price'];
		}

?>
				<tr class="table-footer">
					<td></td>
					<td></td>
					<td></td>
					<td class="total-desc">TOTAL:</td>
					<td class="total-price"><?php echo $count; ?></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
				</tr>
			</tbody>
		</table>
	</div>
</div>

<?php
}
else
{
?>

<style>
.ct-group {
	border-style: hidden;
}
.brd .frm-group {
    border-color: #ffffff;
}
.content-menu{background: #fff;}
.content-menu ul{padding: .1em 0;}
.content-menu li{
	display: inline;
	margin: -.2em;
	padding: .2em .5em;
	text-transform: uppercase;
}
.content-menu .active{
	background: aliceblue;
}
.content-menu .normal{
	padding: .1em .5em;
	border: 1px solid #8d999c;
	border-top: none;
	background: gainsboro;
}
</style>

<div class="main-content main-frm">
	<div class="ct-group">
		<fieldset class="frm-group group1">
			<div class="ct-set group-item<?php echo ++$page_param['item_count'] ?>">
				<div class="ct-box">
					<h6 class="ct-legend hn"><span>Created</span></h6>
					<p><span><strong><?php echo format_time($form_info['created_date']) ?></strong></span></p>
				</div>
			</div>
			<div class="ct-set group-item<?php echo ++$page_param['item_count'] ?>">
				<div class="ct-box">
					<h6 class="ct-legend hn"><span>Property</span></h6>
					<p><span><strong><?php echo html_encode($form_info['pro_name']) ?></strong></span></p>
				</div>
			</div>
			<div class="ct-set group-item<?php echo ++$page_param['item_count'] ?>">
				<div class="ct-box">
					<h6 class="ct-legend hn"><span>Project Managers</span></h6>
					<p><span><strong><?php echo html_encode($form_info['project_manager']) ?><?php echo ($form_info['second_manager_id'] > 0) ? ', '.html_encode($form_info['second_manager']) : '' ?></strong></span></p>
				</div>
			</div>
			<div class="ct-set group-item<?php echo ++$page_param['item_count'] ?>">
				<div class="ct-box">
					<h6 class="ct-legend hn"><span>Project scale</span></h6>
					<p><span><strong><?php echo ($form_info['project_scale'] == 1 ? 'MAJOR' : 'MINOR') ?></strong></span></p>
				</div>
			</div>
			<div class="ct-set group-item<?php echo ++$page_param['item_count'] ?>">
				<div class="ct-box">
					<h6 class="ct-legend hn"><span>Project description</span></h6>
					<p><span><?php echo ($form_info['project_desc'] != '') ? html_encode($form_info['project_desc']) : 'N/A' ?></span></p>
				</div>
			</div>
			<div class="ct-set group-item<?php echo ++$page_param['item_count'] ?>">
				<div class="ct-box">
					<h6 class="ct-legend hn"><span>Remarks</span></h6>
					<p><span><?php echo ($form_info['remarks'] != '') ? html_encode($form_info['remarks']) : 'N/A' ?></span></p>
				</div>
			</div>
			<div class="ct-set group-item<?php echo ++$page_param['item_count'] ?>">
				<div class="ct-box">
					<h6 class="ct-legend hn"><span>Notice for Admin</span></h6>
					<p><span><?php echo $form_info['admin_notice'] ?></span></p>
				</div>
			</div>
			<div class="ct-set group-item<?php echo ++$page_param['item_count'] ?>">
				<div class="ct-box">
					<h6 class="ct-legend hn"><span>Notice approved</span></h6>
					<p><span><strong><?php echo ($form_info['admin_approved'] == 1) ? 'YES' : 'NO' ?></strong></span></p>
				</div>
			</div>
			<div class="ct-set group-item<?php echo ++$page_param['item_count'] ?>">
				<div class="ct-box">
					<h6 class="ct-legend hn"><span>Action date</span></h6>
					<p><span><strong><?php echo format_time($form_info['action_date']) ?></strong></span></p>
				</div>
			</div>
			<div class="ct-set group-item<?php echo ++$page_param['item_count'] ?>">
				<div class="ct-box">
					<h6 class="ct-legend hn"><span>Start date</span></h6>
					<p><span><strong><?php echo format_time($form_info['start_date']) ?></strong></span></p>
				</div>
			</div>
			<div class="ct-set group-item<?php echo ++$page_param['item_count'] ?>">
				<div class="ct-box">
					<h6 class="ct-legend hn"><span>End date</span></h6>
					<p><span><strong><?php echo format_time($form_info['end_date']) ?></strong></span></p>
				</div>
			</div>
			<div class="ct-set group-item<?php echo ++$page_param['item_count'] ?>">
				<div class="ct-box">
					<h6 class="ct-legend hn"><span>Budget</span></h6>
					<p><span>$.<strong><?php echo $form_info['budget'] ?></strong></span></p>
				</div>
			</div>
			<div class="ct-set group-item<?php echo ++$page_param['item_count'] ?>">
				<div class="ct-box">
					<h6 class="ct-legend hn"><span>Cost</span></h6>
					<p><span>$.<strong><?php echo $form_info['cost'] ?></strong></span></p>
				</div>
			</div>
			<div class="ct-set group-item<?php echo ++$page_param['item_count'] ?>">
				<div class="ct-box">
					<h6 class="ct-legend hn"><span>Work status</span></h6>
					<p><span><strong><?php echo $work_statuses[$form_info['work_status']] ?></strong></span></p>
				</div>
			</div>
		</fieldset>
	</div>
</div>

<?php
}
require SITE_ROOT.'footer.php';