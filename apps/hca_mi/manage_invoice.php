<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if (!$User->checkAccess('hca_mi'))
	message($lang_common['No permission']);

$permission7 = ($User->checkPermissions('hca_mi', 7)) ? true : false; // update proj

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
	message($lang_common['Bad request']);

$query = [
	'SELECT'	=> 'pj.*, pj.unit_number AS unit, pt.pro_name, un.unit_number, u1.realname AS project_manager1, u2.realname AS project_manager2, u3.realname AS final_performed_by, v1.vendor_name AS services_vendor_name, v2.vendor_name AS asb_vendor_name, v3.vendor_name AS rem_vendor_name, v4.vendor_name AS cons_vendor_name',
	'FROM'		=> 'hca_5840_projects AS pj',
	'JOINS'		=> [
		[
			'INNER JOIN'	=> 'sm_property_db AS pt',
			'ON'			=> 'pt.id=pj.property_id'
		],
		[
			'LEFT JOIN'		=> 'sm_property_units AS un',
			'ON'			=> 'un.id=pj.unit_id'
		],
		// Get Project Managers
		[
			'LEFT JOIN'		=> 'users AS u1',
			'ON'			=> 'u1.id=pj.performed_uid'
		],
		[
			'LEFT JOIN'		=> 'users AS u2',
			'ON'			=> 'u2.id=pj.performed_uid2'
		],
		[
			'LEFT JOIN'		=> 'users AS u3',
			'ON'			=> 'u3.id=pj.final_performed_uid'
		],
		// Get Vendors
		[
			'LEFT JOIN'		=> 'sm_vendors AS v1',
			'ON'			=> 'v1.id=pj.services_vendor_id'
		],
		[
			'LEFT JOIN'		=> 'sm_vendors AS v2',
			'ON'			=> 'v2.id=pj.asb_vendor_id'
		],
		[
			'LEFT JOIN'		=> 'sm_vendors AS v3',
			'ON'			=> 'v3.id=pj.rem_vendor_id'
		],
		[
			'LEFT JOIN'		=> 'sm_vendors AS v4',
			'ON'			=> 'v4.id=pj.cons_vendor_id'
		],
	],
	'WHERE'		=> 'pj.id='.$id,
];
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$main_info = $DBLayer->fetch_assoc($result);

$asb_total_amount = is_numeric($main_info['asb_total_amount']) ? number_format($main_info['asb_total_amount'], 2, '.', '') : 0;
$rem_total_amount = is_numeric($main_info['rem_total_amount']) ? number_format($main_info['rem_total_amount'], 2, '.', '') : 0;
$cons_total_amount = is_numeric($main_info['cons_total_amount']) ? number_format($main_info['cons_total_amount'], 2, '.', '') : 0;
$total_cost = $asb_total_amount + $rem_total_amount + $cons_total_amount;

if ($total_cost >= 5000)
	$Core->add_warning('The total cost of the project exceeded $ 5,000.');

$Core->set_page_id('hca_mi_manage_invoice', 'hca_mi');
require SITE_ROOT.'header.php';

?>

<style>
.bg-steelblue {
    background-color: steelblue !important;
}
.text-blue {
    color: #478fcc!important;
}
.text-blue-m2 {
    color: #68a3d5!important;
}
</style>

<div class="card">
	<div class="card-header d-flex justify-content-between">
		<h6 class="card-title mb-0">Moisture Project Invoice</h6>
		<div>
			<a href="<?=$URL->link('hca_5840_manage_project', $id)?>" class="badge bg-primary text-white">Project</a>
			<a href="<?=$URL->link('hca_5840_manage_files', $id)?>" class="badge bg-primary text-white">Files</a>
			<a href="<?=$URL->link('hca_5840_manage_appendixb', $id)?>" class="badge bg-primary text-white">+ Appendix-B</a>
		</div>
	</div>
	<div class="card-body">
		<div class="container px-0">
			<div class="row">
				<div class="col-12 col-lg-12">
					<div class="row mb-3">
						<div class="col-sm-6">
							<div>
								<span class="text-sm text-secondary align-middle">Property:</span>
								<span class="text-600 text-110 text-blue align-middle"><?php echo html_encode($main_info['pro_name']) ?></span>
							</div>
							<div>
								<span class="text-sm text-secondary align-middle">Unit #</span>
								<span class="text-600 text-110 text-blue align-middle"><?php echo html_encode($main_info['unit_number']) ?></span>
							</div>
							<div>
								<span class="text-sm text-secondary align-middle">Location:</span>
								<span class="text-600 text-110 text-blue align-middle"><?php echo html_encode($main_info['location']) ?></span>
							</div>
							<div>
								<span class="text-sm text-secondary align-middle">Project Manager:</span>
								<span class="text-600 text-110 text-blue align-middle"><?php echo html_encode($main_info['project_manager1']) ?></span>
							</div>
<?php if ($main_info['project_manager2'] != ''): ?>
							<div>
								<span class="text-sm text-secondary align-middle">Project Manager 2:</span>
								<span class="text-600 text-110 text-blue align-middle"><?php echo html_encode($main_info['project_manager2']) ?></span>
							</div>
<?php endif; ?>
						</div>

						<div class="text-95 col-sm-6 align-self-start d-sm-flex justify-content-end">
							<div class="text-secondary">
								<div class="mt-1 mb-2 text-600 text-125">Invoice</div>
								<div class="my-2"><i class="fa fa-circle text-info"></i> <span class="text-600 text-90">ID:</span> #<?php echo $main_info['id'] ?></div>
								<div class="my-2"><i class="fa fa-circle text-info"></i> <span class="text-600 text-90">Date Reported:</span> <?php echo format_time($main_info['mois_report_date'], 1) ?></div>
								<div class="my-2"><i class="fa fa-circle text-info"></i> <span class="text-600 text-90">Status:</span> <span class="badge badge-warning badge-pill px-25">Unpaid</span></div>
							</div>
						</div>
					</div>

					<table class="table table-striped table-bordered">
						<thead class="bg-steelblue">
							<tr>
								<th>#</th>
								<th>Vendor Name</th>
								<th>Work Performed</th>
								<th>PO Number</th>
								<th>Amount</th>
							</tr>
						</thead>
						<tbody>
						<tr>
							<td>1</td>
							<td><?php echo html_encode($main_info['services_vendor_name']) ?></td>
							<td><?php echo html_encode($main_info['afcc_comment']) ?></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td>2</td>
							<td><?php echo html_encode($main_info['asb_vendor_name']) ?></td>
							<td><?php echo html_encode($main_info['asb_comment']) ?></td>
							<td class="ta-center"><?php echo html_encode($main_info['asb_po_number']) ?></td>
							<td class="ta-center"><?php echo $asb_total_amount ?></td>
						</tr>
						<tr>
							<td>3</td>
							<td><?php echo html_encode($main_info['rem_vendor_name']) ?></td>
							<td><?php echo html_encode($main_info['rem_comment']) ?></td>
							<td class="ta-center"><?php echo html_encode($main_info['rem_po_number']) ?></td>
							<td class="ta-center"><?php echo $rem_total_amount ?></td>
						</tr>
						<tr>
							<td>4</td>
							<td><?php echo html_encode($main_info['cons_vendor_name']) ?></td>
							<td><?php echo html_encode($main_info['cons_comment']) ?></td>
							<td class="ta-center"><?php echo html_encode($main_info['cons_po_number']) ?></td>
							<td class="ta-center"><?php echo $cons_total_amount ?></td>
						</tr>
						</tbody>
						<tfoot>
							<tr>
								<th></th>
								<th></th>
								<th></th>
								<th>Total Amount</th>
								<th>$ <?php echo $total_cost ?>.00</th>
							</tr>
						</tfoot>
					</table>

				</div>
			</div>
		</div>
	</div>
</div>

<?php
require SITE_ROOT.'footer.php';
