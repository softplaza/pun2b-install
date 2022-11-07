<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

$section = isset($_GET['section']) ? $_GET['section'] : '';

$Core->set_page_id('print', 'hca_ui');
require SITE_ROOT.'header.php';

if ($section == 'pending_items')
{
	$search_by_inspection_type = isset($_GET['inspection_type']) ? intval($_GET['inspection_type']) : 0; // 0 all, 1 audit, 2 flapper
	$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
	$search_by_item_id  = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;
	$search_by_job_type  = isset($_GET['job_type']) ? intval($_GET['job_type']) : 0;
	$search_by_year = isset($_GET['year']) ? intval($_GET['year']) : 0;
	$search_by_date_inspected = isset($_GET['date_inspected']) ? swift_trim($_GET['date_inspected']) : '';

	$HcaUnitInspection = new HcaUnitInspection;
	$HcaUiPDF = new HcaUiPDF;

	$search_query = [];
	$search_query[] = 'i.summary_report=1';
	if ($search_by_job_type == 0) {
		$search_query[] = 'ch.inspection_completed=2';
		$search_query[] = 'ch.work_order_completed=1';
	}
	$search_query[] = 'ch.num_problem > 0';
	if ($search_by_property_id > 0)
		$search_query[] = 'ch.property_id='.$search_by_property_id;

	if ($search_by_inspection_type == 1)
		$search_query[] = 'ch.type_audit=1';
	if ($search_by_inspection_type == 2)
		$search_query[] = 'ch.type_flapper=1';

	if ($search_by_item_id > 0)
		$search_query[] = 'ci.item_id='.$search_by_item_id;
	if ($search_by_date_inspected != '')
		$search_query[] = 'DATE(ch.date_inspected)=\''.$DBLayer->escape($search_by_date_inspected).'\'';
	if ($search_by_year > 0)
		$search_query[] = 'YEAR(ch.date_inspected)=\''.$DBLayer->escape($search_by_year).'\'';

	if ($search_by_job_type == 0)
		$search_query[] = 'ci.job_type=0';
	else if ($search_by_job_type == 1)
		$search_query[] = 'ci.job_type=1';

	$query = [
		'SELECT'	=> 'ci.item_id, ci.job_type, ci.checklist_id, ch.property_id, ch.num_problem, ch.num_pending, ch.num_replaced, ch.num_repaired, ch.num_reset, ch.inspection_completed, ch.work_order_completed, ch.work_order_comment, ch.date_inspected, p.pro_name, un.unit_number, un.mbath, un.hbath',
		'FROM'		=> 'hca_ui_checklist_items AS ci',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'hca_ui_checklist AS ch',
				'ON'			=> 'ch.id=ci.checklist_id'
			],
			[
				'INNER JOIN'	=> 'hca_ui_items AS i',
				'ON'			=> 'i.id=ci.item_id'
			],
			[
				'INNER JOIN'	=> 'sm_property_db AS p',
				'ON'			=> 'p.id=ch.property_id'
			],
			[
				'INNER JOIN'	=> 'sm_property_units AS un',
				'ON'			=> 'un.id=ch.unit_id'
			],
		],
		'ORDER BY'	=> 'i.display_position'
	];
	$query['WHERE'] = implode(' AND ', $search_query);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);

	$has_mbath = $has_hbath = false;
	$num_pending = $hca_ui_checklist_ids = $hca_ui_checklist_dates = [];
	while($row = $DBLayer->fetch_assoc($result))
	{
		if (isset($num_pending[$row['item_id']]))
			++$num_pending[$row['item_id']];
		else
			$num_pending[$row['item_id']] = 1;

		if (!$has_mbath && $row['mbath'] == 1)
			$has_mbath = true;

		if (!$has_hbath && $row['hbath'] == 1)
			$has_hbath = true;

		$hca_ui_checklist_ids[$row['checklist_id']] = $row['checklist_id'];
		$hca_ui_checklist_dates[$row['date_inspected']] = $row['checklist_id'];
	}

	$query = array(
		'SELECT'	=> 'i.*',
		'FROM'		=> 'hca_ui_items AS i',
		'WHERE'		=> 'i.summary_report=1',
		'ORDER BY'	=> 'i.location_id, i.display_position'
	);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$hca_ui_items = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$hca_ui_items[] = $row;
	}

	// Header
	$HcaUiPDF->addPreTableRow('<p style="text-align:center;font-weight:bold;">LIST OF PENDING ITEMS</p>');

	$HcaUiPDF->addTR();

	$td_css = ['style="font-weight:bold; width:11%; border: 1px solid grey; background:lightgrey; padding:3;margin:0;text-align:center"'];
	$HcaUiPDF->addTD('Part #', $td_css);
	$HcaUiPDF->addTD('Item Name', $td_css);
	$HcaUiPDF->addTD('Quantity', $td_css);

	foreach($hca_ui_items as $cur_info)
	{
		if ($cur_info['location_id'] < 3 || $cur_info['location_id'] == 100 || ($has_mbath && $cur_info['location_id'] == 3) || ($has_hbath && $cur_info['location_id'] == 4))
		{
			if (isset($num_pending[$cur_info['id']]))
			{
				$item_name = $HcaUnitInspection->getLocation($cur_info['location_id']).' -> '.$HcaUnitInspection->getEquipment($cur_info['equipment_id']).' -> '.html_encode($cur_info['item_name']);
				
				$HcaUiPDF->addTR();

				$td_css = ['style="font-weight:bold; width:11%; border: 1px solid grey; padding:3;margin:0;text-align:center"'];
				$HcaUiPDF->addTD($cur_info['part_number'], $td_css);

				$td_css = ['style="font-weight:bold; width:11%; border: 1px solid grey; padding:3;margin:0;"'];
				$HcaUiPDF->addTD($item_name, $td_css);

				$td_css = ['style="font-weight:bold; width:11%; border: 1px solid grey; padding:3;margin:0;text-align:center"'];
				$HcaUiPDF->addTD($num_pending[$cur_info['id']], $td_css);

			}
		}
	}

	$HcaUiPDF->print();
	$file_path = 'files/pending_items_'.$User->get('id').'.pdf';
	$HcaUiPDF->print($file_path);

?>
	<style>#demo_iframe{width: 100%;height: 400px;zoom: 2;}</style>
	<div class="card-header">
		<h6 class="card-title mb-0">PDF preview</h6>
	</div>
	<iframe id="demo_iframe" src="<?php echo BASE_URL ?>/apps/hca_ui/<?=$file_path?>?v=<?=time()?>"></iframe>
<?php
}

else if ($section == 'work_order')
{

	// will be imported from WO page



}

require SITE_ROOT.'footer.php';