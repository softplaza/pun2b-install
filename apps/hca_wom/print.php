<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

if ($User->is_guest())
	message($lang_common['No permission']);

$section = isset($_GET['section']) ? $_GET['section'] : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$Core->set_page_id('print', 'hca_wom');
require SITE_ROOT.'header.php';

if ($section == 'work_order')
{
	$HcaWOM = new HcaWOM;
	$HcaWOMPDF = new HcaWOMPDF;

	$wo_info = $HcaWOM->getWorkOrderInfo($id);

	$query = [
		'SELECT'	=> 't.*, i.item_name, i.item_actions, u1.realname AS assigned_name',
		'FROM'		=> 'hca_wom_tasks AS t',
		'JOINS'		=> [
			[
				'LEFT JOIN'		=> 'hca_wom_items AS i',
				'ON'			=> 'i.id=t.item_id'
			],
			[
				'LEFT JOIN'		=> 'users AS u1',
				'ON'			=> 'u1.id=t.assigned_to'
			],
		],
		'WHERE'		=> 't.work_order_id='.$id,
		'ORDER BY'	=> 't.id',
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$tasks_info = [];
	while ($row = $DBLayer->fetch_assoc($result)) {
		$tasks_info[] = $row;
	}


	// Header
	$HcaWOMPDF->addPreTableRow('<p style="text-align:center;font-weight:bold;">LIST OF PENDING ITEMS</p>');

	$HcaWOMPDF->addTR();

	$td_css = ['style="font-weight:bold; width:11%; border: 1px solid grey; background:lightgrey; padding:3;margin:0;text-align:center"'];
	$HcaWOMPDF->addTD('Part #', $td_css);
	$HcaWOMPDF->addTD('Item Name', $td_css);
	$HcaWOMPDF->addTD('Quantity', $td_css);

	foreach($hca_ui_items as $cur_info)
	{
		if ($cur_info['location_id'] < 3 || $cur_info['location_id'] == 100 || ($has_mbath && $cur_info['location_id'] == 3) || ($has_hbath && $cur_info['location_id'] == 4))
		{
			if (isset($num_pending[$cur_info['id']]))
			{
				$item_name = $HcaUnitInspection->getLocation($cur_info['location_id']).' -> '.$HcaUnitInspection->getEquipment($cur_info['equipment_id']).' -> '.html_encode($cur_info['item_name']);
				
				$HcaWOMPDF->addTR();

				$td_css = ['style="font-weight:bold; width:11%; border: 1px solid grey; padding:3;margin:0;text-align:center"'];
				$HcaWOMPDF->addTD($cur_info['part_number'], $td_css);

				$td_css = ['style="font-weight:bold; width:11%; border: 1px solid grey; padding:3;margin:0;"'];
				$HcaWOMPDF->addTD($item_name, $td_css);

				$td_css = ['style="font-weight:bold; width:11%; border: 1px solid grey; padding:3;margin:0;text-align:center"'];
				$HcaWOMPDF->addTD($num_pending[$cur_info['id']], $td_css);

			}
		}
	}

	$HcaWOMPDF->print();
	$file_path = 'files/pending_items_'.$User->get('id').'.pdf';
	$HcaWOMPDF->print($file_path);

?>
	<style>#demo_iframe{width: 100%;height: 400px;zoom: 2;}</style>
	<div class="card-header">
		<h6 class="card-title mb-0">PDF preview</h6>
	</div>
	<iframe id="demo_iframe" src="<?php echo BASE_URL ?>/apps/hca_ui/<?=$file_path?>?v=<?=time()?>"></iframe>
<?php
}

require SITE_ROOT.'footer.php';