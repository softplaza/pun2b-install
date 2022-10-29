<?php

define('SITE_ROOT', '../../');
require SITE_ROOT.'include/common.php';

$is_guest = $User->is_guest() ? true : false;

if ($is_guest)
	message($lang_common['No permission']);

$section = isset($_GET['section']) ? $_GET['section'] : '';
$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;

$Core->set_page_id('print', 'hca_ui');
require SITE_ROOT.'header.php';

if ($section == 'alarms' && $search_by_property_id > 0)
{
	$HcaHVACInspectionsAlarms = new HcaHVACInspectionsAlarms;
	$HcaHVACPDF = new HcaHVACPDF;

	$query = [
		'SELECT'	=> 'p.*',
		'FROM'		=> 'sm_property_db AS p',
		'WHERE'		=> 'p.id='.$search_by_property_id,
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$sm_property_db = $DBLayer->fetch_assoc($result);

	$search_query = $sm_property_units = [];
	if ($search_by_property_id > 0)
		$search_query[] = 'pu.property_id='.$search_by_property_id;

	$query = [
		'SELECT'	=> 'pu.*',
		'FROM'		=> 'sm_property_units AS pu',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'sm_property_db AS pn',
				'ON'			=> 'pn.id=pu.property_id'
			],
		],
		'ORDER BY'	=> 'LENGTH(pu.unit_number), pu.unit_number',
	];
	$query['WHERE'] = implode(' AND ', $search_query);
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	while($row = $DBLayer->fetch_assoc($result))
	{
		$sm_property_units[$row['id']] = $row;
	}
	
	if (!empty($sm_property_units))
	{
		$HcaHVACPDF->addPreTableRow('<p style="text-align:center;font-weight:bold;">OWNER/MANAGER MULTI-FAMILY SMOKE ALARM AND CARBON MONOXIDE TEST LOG</p>');
		$HcaHVACPDF->addPreTableRow('<p style="text-align:center;font-weight:bold;">Calendar Year 2022</p>');
		// text-decoration: underline;
		$HcaHVACPDF->addPreTableRow('<p style="padding-top:0;margin:3px;">Property: <span style="">'.html_encode($sm_property_db['pro_name']).'</span></p>');
		$HcaHVACPDF->addPreTableRow('<p style="padding-top:0;margin:3px;">Address: <span style="">'.html_encode($sm_property_db['office_address']).'</span></p>');
		$HcaHVACPDF->addPreTableRow('<p style="padding-top:0;margin:3px;">Phone: <span style="">'.html_encode($sm_property_db['office_phone']).'</span></p>');
		$HcaHVACPDF->addPreTableRow('<p style="padding-top:0;margin:3px;">Fax: <span style="">'.html_encode($sm_property_db['office_fax']).'</span></p>');

		$td_css = ['style="font-weight:bold; border: 1px solid grey; text-align:center"', 'colspan="5"'];
		$HcaHVACPDF->addTR();
		$HcaHVACPDF->addTD('SMOKE ALARMS', $td_css);
		$HcaHVACPDF->addTD('CARBON MONOXIDE ALARMS', $td_css);

		$td_css = ['style="font-weight:bold; width:11%; border: 1px solid grey; padding:3;margin:0;text-align:center"'];
		$HcaHVACPDF->addTR();
		$HcaHVACPDF->addTD('Unit #', $td_css);
		$HcaHVACPDF->addTD('Date Tested', $td_css);
		$HcaHVACPDF->addTD('Tested by', $td_css);
		$HcaHVACPDF->addTD('Working', $td_css);
		$HcaHVACPDF->addTD('Date corrected', $td_css);

		$td_css = ['style="font-weight:bold; width:11%; border: 1px solid grey; background:lightgrey; padding:3;margin:0;text-align:center"'];
		$HcaHVACPDF->addTD('Unit #', $td_css);
		$HcaHVACPDF->addTD('Date Tested', $td_css);
		$HcaHVACPDF->addTD('Tested by', $td_css);
		$HcaHVACPDF->addTD('Working', $td_css);
		$HcaHVACPDF->addTD('Date corrected', $td_css);
	
		$td_css = ['style="border: 1px solid grey;padding:3;margin:0;text-align:center"'];
		foreach($sm_property_units as $cur_info)
		{
			$smoke_info = $HcaHVACInspectionsAlarms->getSmokeTest($cur_info['id']);
			$carbon_info = $HcaHVACInspectionsAlarms->getCarbonTest($cur_info['id']);

			$HcaHVACPDF->addTR();

			$HcaHVACPDF->addTD($cur_info['unit_number'], $td_css);
			$HcaHVACPDF->addTD(format_date($smoke_info['date_tested'], 'n/j/y'), $td_css);
			$HcaHVACPDF->addTD($smoke_info['tested_by'], $td_css);
			$HcaHVACPDF->addTD($smoke_info['working'], $td_css);
			$HcaHVACPDF->addTD(format_date($smoke_info['date_corrected'], 'n/j/y'), $td_css);

			$HcaHVACPDF->addTD($cur_info['unit_number'], $td_css);
			$HcaHVACPDF->addTD(format_date($carbon_info['date_tested'], 'n/j/y'), $td_css);
			$HcaHVACPDF->addTD($carbon_info['tested_by'], $td_css);
			$HcaHVACPDF->addTD($carbon_info['working'], $td_css);
			$HcaHVACPDF->addTD(format_date($carbon_info['date_corrected'], 'n/j/y'), $td_css);
		}

		$file_path = 'files/alarm_'.$User->get('id').'.pdf';
		$HcaHVACPDF->print($file_path);
	}
?>
<style>#demo_iframe{width: 100%;height: 400px;zoom: 2;}</style>
<iframe id="demo_iframe" src="<?php echo BASE_URL ?>/apps/hca_hvac_inspections/<?=$file_path?>?v=<?=time()?>"></iframe>
<?php

	require SITE_ROOT.'footer.php';
}

?>
<div class="alert alert-warning" role="alert">All checkboxes must be marked "Yes" or "No". Action from dropdown must be selected.</div>
<?php

require SITE_ROOT.'footer.php';
