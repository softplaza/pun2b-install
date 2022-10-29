<style>
.total-desc{text-align: right;}
.total-price{text-align: left;font-weight: bold;}
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
				</tr>
			</thead>
			<tbody>
<?php
	$count = 0;
	foreach ($invoice_info as $cur_info)
	{
?>
				<tr>
					<td><strong><?php echo html_encode($cur_info['vendor']) ?></strong></td>
					<td><?php echo html_encode($cur_info['work_performed']) ?></td>
					<td><?php echo html_encode($cur_info['po_number']) ?></td>
					<td><?php echo html_encode($cur_info['price']) ?></td>
					<td><?php echo ($cur_info['change_order'] == 1 ? 'YES' : 'NO') ?></td>
					<td><?php echo ($cur_info['lean_release'] == 1 ? 'YES' : 'NO') ?></td>
					<td><?php echo ($cur_info['ok_to_pay'] == 1 ? 'YES' : 'NO') ?></td>
					<td><?php echo ($cur_info['completed'] == 1 ? 'YES' : 'NO') ?></td>
				</tr>
<?php
		$count = $count + $cur_info['price'];
	}
?>
			</tbody>
			<tfoot>
				<tr class="table-footer">
					<td colspan="2"></td>
					<td class="total-desc">TOTAL PRICE:</td>
					<td class="total-price">$ <?php echo gen_number_format($count, 2) ?></td>
					<td colspan="5"></td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>