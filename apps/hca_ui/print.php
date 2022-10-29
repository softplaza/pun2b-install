<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$is_guest = $User->is_guest() ? true : false;

if ($is_guest)
	message($lang_common['No permission']);

$section = isset($_GET['section']) ? $_GET['section'] : '';

if ($section == 'pending_items')
{
	$Core->set_page_id('print', 'hca_ui');
	require SITE_ROOT.'header.php';

	$HcaUnitInspection = new HcaUnitInspection;
	$HcaUiPDF = new HcaUiPDF;

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

	$HcaUiPDF->addHeadTD('Part #');
	$HcaUiPDF->addHeadTD('Item Name');

	foreach($hca_ui_items as $cur_info)
	{
		$HcaUiPDF->addBodyTR();
		$HcaUiPDF->addBodyTD($cur_info['part_number']);
		$HcaUiPDF->addBodyTD($cur_info['part_number']);
	}

	$HcaUiPDF->print();

?>
	<style>#demo_iframe{width: 100%;height: 400px;zoom: 2;}</style>
	<div class="card-header">
		<h6 class="card-title mb-0">PDF preview</h6>
	</div>
	<iframe id="demo_iframe" src="<?php echo BASE_URL ?>/apps/hca_ui/files/pending_items_<?=$User->get('id')?>'.pdf?v=<?=time()?>"></iframe>
<?php

	require SITE_ROOT.'footer.php';
}