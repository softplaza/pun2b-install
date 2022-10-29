<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';
require 'functions_generate_pdf.php';
require 'class_get_vendors.php';
require 'class_hca_vcr_pdf.php';
require 'class_HCAVCR.php';

$access = ($User->is_admmod() || $User->get('sm_pm_property_id') > 0 || $User->get('hca_vcr_access') > 0) ? true : false;
$access5 = ($User->is_admmod() || $User->get('hca_vcr_access') == 5) ? true : false;

if (!$access)
	message($lang_common['No permission']);

$search_by_year = isset($_GET['year']) ? intval($_GET['year']) : 0;
$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
$search_by_unit_number = isset($_GET['unit_number']) ? swift_trim($_GET['unit_number']) : '';

$cur_year = strtotime(($search_by_year).'-01-01');
$next_year = strtotime(($search_by_year + 1).'-01-01');

$search_by_query = [];
if ($search_by_year > 0)
	$search_by_query[] = 'pj.move_out_date >='.$cur_year.' AND pj.move_in_date < '.$next_year;
//	$search_by_query[] = 'pj.move_out_date > 0 AND pj.move_out_date >='.$cur_year.' AND pj.move_in_date > 0 AND pj.move_in_date < '.$next_year;
if ($search_by_property_id > 0) {
	$search_by_query[] = 'pj.property_id=\''.$DBLayer->escape($search_by_property_id).'\'';
}
if (!empty($search_by_unit_number)) {
	$search_by_unit2 = '%'.$search_by_unit_number.'%';
	$search_by_query[] = 'pj.unit_number LIKE \''.$DBLayer->escape($search_by_unit2).'\'';
}

$query = array(
	'SELECT'	=> 'id, pro_name, manager_id',
	'FROM'		=> 'sm_property_db',
	'ORDER BY'	=> 'pro_name',
);
$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
$property_info = array();
while ($row = $DBLayer->fetch_assoc($result)) {
	$property_info[] = $row;
}

if (!empty($search_by_query))
{
	$query = array(
		'SELECT'	=> 'COUNT(pj.id)',
		'FROM'		=> 'hca_vcr_projects AS pj',
	);
	$query['WHERE'] = implode(' AND ', $search_by_query);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$PagesNavigator->set_total($DBLayer->result($result));
}

$main_info = $projects_ids = array();
if (!empty($search_by_query))
{
	$query = array(
		'SELECT'	=> 'pj.*, pt.pro_name',
		'FROM'		=> 'hca_vcr_projects AS pj',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'sm_property_db AS pt',
				'ON'			=> 'pt.id=pj.property_id'
			),
		),
		'ORDER BY'	=> 'pj.property_name, LENGTH(pj.unit_number), pj.unit_number',
		'LIMIT'		=> $PagesNavigator->limit(),
	);
	$query['WHERE'] = implode(' AND ', $search_by_query);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result)) {
		$main_info[] = $row;
		$projects_ids[] = $row['id'];
	}
}
$PagesNavigator->num_items($main_info);

$uploader_info = $vendor_schedule_info = array();
// GET UPLOADED FILES
if (!empty($projects_ids))
{
	// GET SCHEDULED VENDORS
	$query = array(
		'SELECT'	=> 'i.*, v.vendor_name',
		'FROM'		=> 'hca_vcr_invoices AS i',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'	=> 'sm_vendors AS v',
				'ON'		=> 'v.id=i.vendor_id'
			),
		),
		'WHERE'		=> 'project_id IN ('.implode(',', $projects_ids).') AND project_name=\''.$DBLayer->escape('hca_vcr_projects').'\''
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result)) {
		$vendor_schedule_info[] = $row;
	}
	
	$VCRVendors->AddSchedule($vendor_schedule_info);
	
	$query = array(
		'SELECT'	=> 'id, table_id',
		'FROM'		=> 'sm_uploader',
		'WHERE'		=> 'table_id IN ('.implode(',', $projects_ids).') AND table_name=\''.$DBLayer->escape('hca_vcr_projects').'\''
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $DBLayer->fetch_assoc($result)) {
		$uploader_info[] = $row['table_id'];
	}
}

//$Core->set_page_title('Projects');
$Core->set_page_id('hca_vcr_report', 'hca_vcr');
require SITE_ROOT.'header.php';
?>

<style>
.ct-group table{table-layout: initial;}
.ct-group td{vertical-align:top;padding:0 1px;text-align: center;}
.active td{background: #a8e6ff;}
.ct-group th {text-transform: uppercase;}
.ct-group p {padding: .15em 0;}
.ct-group .td1{min-width: 130px;}
.td-date{width:90px;min-width:90px;}
.td-comment{min-width:120px;}
.td-final-walk{min-width:200px;}
.td-final-walk .comment{white-space: pre-wrap;color: #9e4100;}
.search-box img{width: 18px;margin: 0 8px 3px 0;cursor: pointer;vertical-align: middle;}
.img-edit{float: right;margin: 0 5px;padding: 0;}
.img-edit img{vertical-align: middle;width: 20px;cursor: pointer;}
.date{font-weight:bold;color:brown;}
.vendor{font-weight:bold;color:#a319fa;}
.ct-group .move{width:100px;min-width:100px;}
.ct-group .alert-date{background: pink;}
</style>

<div class="main-content main-frm">
	<div class="ct-group">
		<div class="search-box">
			<form method="get" accept-charset="utf-8" action="">
				<select name="year">
					<option value="0">Years</option>
<?php for ($year = 2021; $year <= date('Y', time()); $year++){
			if ($search_by_year == $year)
				echo '<option value="'.$year.'" selected="selected">'.$year.'</option>';
			else
				echo '<option value="'.$year.'">'.$year.'</option>';
} ?>
				</select>
				<select name="property_id">
					<option value="">Select property</option>
<?php
	foreach ($property_info as $val)
	{
				if ($search_by_property_id == $val['id'])
					echo '<option value="'.$val['id'].'" selected="selected">'.html_encode($val['pro_name']).'</option>';
				else
					echo '<option value="'.$val['id'].'">'.html_encode($val['pro_name']).'</option>';
	}
?>
				</select>
				<input name="unit_number" type="text" value="<?php echo isset($_GET['unit_number']) ? $_GET['unit_number'] : '' ?>" placeholder="Enter Unit #" size="10"/>
				<input type="submit" value="Search" />
			</form>
		</div>
<?php
	if (!empty($main_info))
	{
?>
		<form method="post" accept-charset="utf-8" action="">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			<table>
				<thead>
					<tr class="sticky-under-menu">
						<th class="th1">Property</th>
						<th>Move-Out</th>
						<th>Pre Walk</th>
						<th>Maintenance</th>
						<th>Urine Scan</th>
						<th>Painter</th>
						<th>Cleaning Service</th>
						<th>Vinyl</th>
						<th>Carpet</th>
						<th>Carpet Cleaning</th>
						<th>Refinish</th>
						<th>Pest-Control</th>
						<th>Final Walk</th>
						<th>Move-In</th>
					</tr>
				</thead>
				<tbody>
<?php

		foreach ($main_info as $cur_info)
		{
			$page_param['td'] = array();
			$page_param['td']['property_name'] = html_encode($cur_info['pro_name']);
			$page_param['td']['unit_number'] = !empty($cur_info['unit_number'])? 'Unit: '.html_encode($cur_info['unit_number']) : '';
			$page_param['td']['unit_size'] = !empty($cur_info['unit_size'])? 'Size: '.html_encode($cur_info['unit_size']) : '';
			$page_param['td']['move_out_date'] = format_time($cur_info['move_out_date'], 1);
			$page_param['td']['move_out_comment'] = html_encode($cur_info['move_out_comment']);
			$page_param['td']['move_in_date'] = format_time($cur_info['move_in_date'], 1);
			$page_param['td']['move_in_comment'] = html_encode($cur_info['move_in_comment']);
			$page_param['td']['pre_walk_date'] = format_time($cur_info['pre_walk_date'], 1);
			$page_param['td']['pre_walk_name'] = html_encode($cur_info['pre_walk_name']);
			$page_param['td']['pre_walk_comment'] = html_encode($cur_info['pre_walk_comment']);
			$page_param['td']['walk'] = html_encode($cur_info['walk']);
			$page_param['td']['walk_date'] = format_time($cur_info['walk_date'], 1);
			$page_param['td']['walk_comment'] = html_encode($cur_info['walk_comment']);
			//$page_param['td']['remarks'] = html_encode($cur_info['remarks']);
			$page_param['td']['status'] = ($cur_info['status'] == 1 ? ' checked="checked"' : '');
			$page_param['btn_actions'] = array();
			$page_param['btn_actions'][] = '<p><span class="submit primary"><input type="submit" name="update['.$cur_info['id'].']" value="Update" /></span></p>';
			
			$page_param['btn_actions'][] = '<p><button type="button" class="lightseagreen" onclick="emailProperty('.$cur_info['id'].')">Send Email</button></p>';
			
			$view_files = in_array($cur_info['id'], $uploader_info) ? '<button class="lightseagreen"><a href="'.$URL->link('hca_vcr_manage_files', $cur_info['id']).'">Files</a></button>' : '';
			
			$css_alert_final_walk = ($FormatDateTime->is_today($cur_info['walk_date']) && $User->get('realname') == $cur_info['walk']) ? 'alert-date' : '';
			
?>
					<tr id="row<?php echo $cur_info['id'] ?>">
						<td class="td1">
							<p><?php echo $page_param['td']['property_name'] ?></p>
							<p><?php echo $page_param['td']['unit_number'] ?></p>
							<p><?php echo $page_param['td']['unit_size'] ?></p>
						<?php if (hca_vcr_check_perms(7)) : ?>
							<p><?php echo $view_files ?></p>
						<?php endif; ?>
						</td>
						<td class="td-date">
							<p class="date"><?php echo $page_param['td']['move_out_date'] ?></p>
							<p><?php echo $page_param['td']['move_out_comment'] ?></p>
						</td>
						<td class="td-date" id="pre_pid<?php echo $cur_info['id'] ?>">
							<p class="date"><?php echo $page_param['td']['pre_walk_date'] ?></p>
							<p class="vendor"><?php echo $page_param['td']['pre_walk_name'] ?></p>
							<p><?php echo $page_param['td']['pre_walk_comment'] ?></p>
						</td>
						<td class="td-comment"><!--MAINTENANCE-->
							<?php echo $VCRVendors->GetVendorDate($cur_info['id'], 8) ?>
							<p class="vendor"><?php echo $VCRVendors->GetVendorName($cur_info['id'], 8) ?></p>
							<p><?php echo $VCRVendors->GetVendorComment($cur_info['id'], 8) ?></p>
						</td>
						<td class="td-date"><!--URINE SCAN-->
							<?php echo $VCRVendors->GetVendorDate($cur_info['id'], 1, '<p class="default" style="font-weight:bold">N/A</p>') ?>
							<p class="vendor"><?php echo $VCRVendors->GetVendorName($cur_info['id'], 1) ?></p>
							<p><?php echo $VCRVendors->GetVendorComment($cur_info['id'], 1) ?></p>
						</td>
						<td class="td-comment"><!--PAINTER SERVICE-->
							<?php echo $VCRVendors->GetVendorDate($cur_info['id'], 2) ?>
							<p class="vendor"><?php echo $VCRVendors->GetVendorName($cur_info['id'], 2) ?></p>
							<p><?php echo $VCRVendors->GetVendorComment($cur_info['id'], 2) ?></p>
						</td>
						<td class="td-date"><!--CLEANING SERVICE-->
							<?php echo $VCRVendors->GetVendorDate($cur_info['id'], 6) ?>
							<p class="vendor"><?php echo $VCRVendors->GetVendorName($cur_info['id'], 6) ?></p>
							<p><?php echo $VCRVendors->GetVendorComment($cur_info['id'], 6) ?></p>
						</td>
						<td class="td-date"><!--VINYL SERVICE-->
							<?php echo $VCRVendors->GetVendorDate($cur_info['id'], 3) ?>
							<p class="vendor"><?php echo $VCRVendors->GetVendorName($cur_info['id'], 3) ?></p>
							<p><?php echo $VCRVendors->GetVendorComment($cur_info['id'], 3) ?></p>
						</td>
						<td class="td-date"><!--CARPET SERVICE-->
							<?php echo $VCRVendors->GetVendorDate($cur_info['id'], 4) ?>
							<p class="vendor"><?php echo $VCRVendors->GetVendorName($cur_info['id'], 4) ?></p>
							<p><?php echo $VCRVendors->GetVendorComment($cur_info['id'], 4) ?></p>
						</td>
						<td class="td-date"><!--CARPET CLEAN-->
							<?php echo $VCRVendors->GetVendorDate($cur_info['id'], 9, '<p class="default" style="font-weight:bold">N/A</p>') ?>
							<p class="vendor"><?php echo $VCRVendors->GetVendorName($cur_info['id'], 9) ?></p>
							<p><?php echo $VCRVendors->GetVendorComment($cur_info['id'], 9) ?></p>
						</td>
						<td class="td-comment"><!--REFINISH-->
							<?php echo $VCRVendors->GetVendorDate($cur_info['id'], 7) ?>
							<p class="vendor"><?php echo $VCRVendors->GetVendorName($cur_info['id'], 7) ?></p>
							<p><?php echo $VCRVendors->GetVendorComment($cur_info['id'], 7) ?></p>
						</td>
						<td class="td-date"><!--PEST CONTROL-->
							<?php echo $VCRVendors->GetVendorDate($cur_info['id'], 5) ?>
							<p class="vendor"><?php echo $VCRVendors->GetVendorName($cur_info['id'], 5) ?></p>
							<p><?php echo $VCRVendors->GetVendorComment($cur_info['id'], 5) ?></p>
						</td>
						<td class="td-final-walk <?php echo $css_alert_final_walk ?>" id="pid<?php echo $cur_info['id'] ?>"><!--FINAL WALK-->
							<p class="date"><?php echo $page_param['td']['walk_date'] ?></p>
							<p class="vendor"><?php echo $page_param['td']['walk'] ?></p>
							<p class="comment"><?php echo $page_param['td']['walk_comment'] ?></p>
						</td>
						<td class="td-date">
							<p class="date"><?php echo $page_param['td']['move_in_date'] ?></p>
							<p><?php echo $page_param['td']['move_in_comment'] ?></p>
						</td>
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
			<p>No projects were found for your search query.</p>
		</div>
<?php
	}
?>
	</div>
</div>

<?php
require SITE_ROOT.'footer.php';