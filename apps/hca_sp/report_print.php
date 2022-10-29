<style>
.description{width:300px;min-width:300px;}
</style>

<div class="main-content main-frm">
	<div class="ct-group ">
		<table>
			<thead>
				<tr>
					<th>Property</th>
					<th>Project ID number</th>
					<th>Project description</th>
					<th>Action Date</th>
					<th>Project manager</th>
					<th>Action</th>
					<th>Start Date</th>
					<th>End Date</th>
					<th>Budget</th>
					<th>Remarks</th>
					<th>Status</th>
				</tr>
			</thead>
			<tbody>
<?php
foreach ($projects_info as $cur_info) 
{
	$follow_up_dates = array();
	foreach ($follow_up_info as $key => $val) {
		if ($cur_info['id'] == $val['project_id']) {
			$follow_up_dates[] = '<p>'.format_time($val['e_date']).': '.$val['e_message'].'</p>';
		}
	}
	
	$second_manager = isset($cur_info['second_manager']) ? '<p>'.$cur_info['second_manager'].'</p>' : '';
?>
				<tr>
					<td><p><?php echo $cur_info['pro_name'] ?></p>
						<p>(Scale: <?php echo ($cur_info['project_scale'] == 1 ? 'Major' : 'Minor') ?>)</p>
					</td>
					<td><?php echo $cur_info['project_number'] ?></td>
					<td class="description"><?php echo $cur_info['project_desc'] ?></td>
					<td><?php echo !empty($cur_info['action_date']) ? date('m/d/Y', $cur_info['action_date']) : 'N/A' ?></td>
					<td>
						<p><?php echo $cur_info['project_manager'] ?></p>
						<?php echo $second_manager ?>
					</td>
					<td class="description"><?php echo implode("\n", $follow_up_dates) ?></td>
					<td><?php echo !empty($cur_info['start_date']) ? date('m/d/Y', $cur_info['start_date']) : 'N/A' ?></td>
					<td><?php echo !empty($cur_info['end_date']) ? date('m/d/Y', $cur_info['end_date']) : 'N/A' ?></td>
					<td><strong>$<?php echo gen_number_format($cur_info['budget'], 2) ?></strong></td>
					<td class="description"><?php echo $cur_info['remarks'] ?></td>
					<td><?php echo $work_statuses[$cur_info['work_status']] ?></td>
				</tr>
<?php
}
?>
			</tbody>
		</table>
	</div>
</div>