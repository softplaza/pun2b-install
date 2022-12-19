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

	$query = [
		'SELECT'	=> 'w.*, p.pro_name, pu.unit_number, u2.realname AS closed_by_name',
		'FROM'		=> 'hca_wom_work_orders AS w',
		'JOINS'		=> [
			[
				'INNER JOIN'	=> 'sm_property_db AS p',
				'ON'			=> 'p.id=w.property_id'
			],
			[
				'INNER JOIN'	=> 'users AS u2',
				'ON'			=> 'u2.id=w.closed_by'
			],
			[
				'LEFT JOIN'		=> 'sm_property_units AS pu',
				'ON'			=> 'pu.id=w.unit_id'
			],
		],
		'WHERE'		=> 'w.id='.$id,
	];
	$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
	$wo_info = $DBLayer->fetch_assoc($result);

	$query = [
		'SELECT'	=> 't.*, i.item_name, i.item_actions, it.type_name, pb.problem_name, u1.realname AS assigned_name',
		'FROM'		=> 'hca_wom_tasks AS t',
		'JOINS'		=> [
			[
				'LEFT JOIN'		=> 'hca_wom_items AS i',
				'ON'			=> 'i.id=t.item_id'
			],
			[
				'LEFT JOIN'		=> 'hca_wom_types AS it',
				'ON'			=> 'it.id=i.item_type'
			],
			[
				'LEFT JOIN'		=> 'hca_wom_problems AS pb',
				'ON'			=> 'pb.id=t.task_action'
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

	$mPDF = new \Mpdf\Mpdf();
	$mPDF->AddPage();

	$content = [];
	$content[] = '<h4 style="text-align:center;margin-bottom:3em;">PROPERTY MAINTENANCE WORK ORDER #'.$wo_info['id'].'</h4>';

	$content[] = '<table cellpadding="2" autosize="1" width="100%" style="border-spacing:0;overflow:wrap;font-size:13px;margin-bottom:3em">';
	$content[] = '<tr>';
	$content[] = '<td><span style="font-weight:bold">Property:</span> '.html_encode($wo_info['pro_name']).'</td>';
	$content[] = '<td style="width:10%;text-align:right"><span style="font-weight:bold">Status:</span></td>';
	$content[] = '<td style="width:10%;">'.($wo_info['wo_status'] == 1 ? 'Open' : 'Closed').'</td>';
	$content[] = '</tr>';

	$content[] = '<tr>';
	$content[] = '<td><span style="font-weight:bold">Unit:</span> '.html_encode($wo_info['unit_number']).'</td>';
	$content[] = '<td style="text-align:right"><span style="font-weight:bold">Priority:</span></td>';
	$content[] = '<td style="">'.(isset($HcaWOM->priority[$wo_info['priority']]) ? $HcaWOM->priority[$wo_info['priority']] : '').'</td>';
	$content[] = '</tr>';

	$content[] = '<tr>';
	$content[] = '<td><span style="font-weight:bold">Permission to Enter:</span> '.($wo_info['enter_permission'] == 1 ? 'Yes' : 'No').'</td>';
	$content[] = '<td style="text-align:right"><span style="font-weight:bold">Pets:</span></td>';
	$content[] = '<td style="">'.($wo_info['has_animal'] == 1 ? 'Yes' : 'No').'</td>';
	$content[] = '</tr>';

	$content[] = '<tr>';
	$content[] = '<td><span style="font-weight:bold">Submitted on:</span> '.format_date($wo_info['dt_created'], 'm/d/Y H:i a').'</td>';
	$content[] = '<td style="text-align:right"></td>';
	$content[] = '</tr>';

	$content[] = '</table>';

	
	if (!empty($tasks_info))
	{
		$content[] = '<table cellpadding="3" autosize="1" width="100%" style="border-spacing:0;overflow:wrap;font-size:14px;">';
		$content[] = '<tr>';
		$content[] = '<td style="font-weight:bold;text-align:center;width:40%">Tasks</td>';
		$content[] = '<td style="font-weight:bold;text-align:center;">Completed by</td>';
		$content[] = '<td style="font-weight:bold;text-align:center;">Completed</td>';
		$content[] = '<td style="font-weight:bold;text-align:center;">Status</td>';
		$content[] = '</tr>';
		$content[] = '</table>';

		$td_style_first_row = 'border: 1px solid #000000;text-align:center;';
		$td_style_last_row = 'padding-bottom:1em';
		foreach($tasks_info as $cur_info)
		{
			$content[] = '<table cellpadding="3" autosize="1" width="100%" style="border-spacing:0;overflow:wrap;font-size:13px;margin-bottom:2em;border:1px solid #000000;">';

			$content[] = '<tr>';
			$content[] = '<td style="border-top: 1px solid #000000;"><span style="font-weight:bold">Type</span>: '.html_encode($cur_info['type_name']).'</td>';
			$content[] = '<td style="'.$td_style_first_row.'width:150px">'.html_encode($cur_info['assigned_name']).'</td>';
			$content[] = '<td style="'.$td_style_first_row.'width:150px">'.format_date($cur_info['dt_completed'], 'm/d/Y').'</td>';
			$content[] = '<td style="'.$td_style_first_row.'width:100px">'.($cur_info['task_status'] == 4 ? 'Closed' : 'Open').'</td>';
			$content[] = '</tr>';

			$content[] = '<tr>';
			$content[] = '<td><span style="font-weight:bold">Item</span>: '.html_encode($cur_info['item_name']).' ('.html_encode($cur_info['problem_name']).')</td>';
			$content[] = '<td></td>';
			$content[] = '<td></td>';
			$content[] = '<td></td>';
			$content[] = '</tr>';

			$content[] = '<tr>';
			$content[] = '<td colspan="4" style="'.$td_style_last_row.'"><span style="font-weight:bold">Description</span>: '.html_encode($cur_info['task_message']).'</td>';
			$content[] = '</tr>';

			$content[] = '<tr>';
			$content[] = '<td style=""></td>';
			$content[] = '<td style="width:150px"></td>';
			$content[] = '<td style="text-align:right;font-weight:bold;width:150px;">Hours:</td>';
			$content[] = '<td style="width:100px">0:00</td>';
			$content[] = '</tr>';

			$content[] = '</table>';
		}
		
	}

	$content[] = '<table cellpadding="2" autosize="1" width="100%" style="border-spacing:0;overflow:wrap;font-size:13px;margin-bottom:2em">';
	$content[] = '<tr>';
	$content[] = '<td></td>';
	$content[] = '<td style="width:20%;text-align:right"><span style="font-weight:bold">Completed Date:</span></td>';
	$content[] = '<td style="width:20%;">'.format_date($wo_info['dt_closed'], 'm/d/Y H:i a').'</td>';
	$content[] = '</tr>';

	$content[] = '<tr>';
	$content[] = '<td></td>';
	$content[] = '<td style="text-align:right"><span style="font-weight:bold">Closed By:</span></td>';
	$content[] = '<td style="">'.html_encode($wo_info['closed_by_name']).'</td>';
	$content[] = '</tr>';

	$content[] = '</table>';


	$mPDF->WriteHTML(implode('', $content));
	$file_path = 'files/work_order_'.$User->get('id').'.pdf';
	$mPDF->Output($file_path, 'F');

?>
	<style>#demo_iframe{width: 100%;height: 400px;zoom: 2;}</style>
	<iframe id="demo_iframe" src="<?php echo BASE_URL ?>/apps/hca_wom/<?=$file_path?>?v=<?=time()?>"></iframe>
<?php
}

require SITE_ROOT.'footer.php';