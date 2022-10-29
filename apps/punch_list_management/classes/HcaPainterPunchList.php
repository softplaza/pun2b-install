<?php

class HcaPainterPunchList
{
	public $box_checked = '<span style="font-family:helvetica;font-size: 18px;">&#10004;</span>';
	public $field_count = 1;
	public $check_statuses = [
		0 => '',
		1 => 'Partial',
		2 => 'Completed',
		3 => 'Not Painted',
		4 => 'YES',
		5 => 'NO'
	];
	public $css_th = 'border:1px solid black;font-weight:bold;text-align:center';
	public $css_td = 'border:1px solid black;';

	function genPDF($id)
	{
		global $DBLayer;

		$query = [
			'SELECT'	=> 'f.*, u.realname, p.pro_name',
			'FROM'		=> 'punch_list_management_maint_request_form AS f',
			'JOINS'		=> [
				[
					'LEFT JOIN'		=> 'users AS u',
					'ON'			=> 'u.id=f.technician_id'
				],
				[
					'LEFT JOIN'		=> 'sm_property_db AS p',
					'ON'			=> 'p.id=f.property_id'
				],
			],
			'WHERE'		=> 'f.id='.$id
		];
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$form_info = $DBLayer->fetch_assoc($result);

		$query = array(
			'SELECT'	=> 'l.*',
			'FROM'		=> 'punch_list_painter_locations AS l',
			'ORDER BY'	=> 'l.position',
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$locations = [];
		while ($row = $DBLayer->fetch_assoc($result)) {
			$locations[] = $row;
		}

		$query = array(
			'SELECT'	=> 'e.id, e.equipment_name, e.location_id, l.location_name',
			'FROM'		=> 'punch_list_painter_equipments AS e',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'punch_list_painter_locations AS l',
					'ON'			=> 'l.id=e.location_id'
				),
			),
			'ORDER BY'	=> 'l.position, e.equipment_name',
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$equipments_info = [];
		while ($row = $DBLayer->fetch_assoc($result)) {
			$equipments_info[] = $row;
		}
		// OR
		$query = array(
			'SELECT'	=> 'ch.*, e.equipment_name, e.location_id, e.replaced_action',
			'FROM'		=> 'punch_list_painter_check_list AS ch',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'punch_list_painter_equipments AS e',
					'ON'			=> 'e.id=ch.equipment_id'
				),
			),
			'WHERE'		=> 'ch.form_id='.$id,
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$check_list = [];
		while ($row = $DBLayer->fetch_assoc($result)) {
			$check_list[] = $row;
		}

		$mPDF = new \Mpdf\Mpdf();
		$header = $header1 = $output = [];

		$start_time = new DateTime($form_info['start_time']);
		$end_time = new DateTime($form_info['end_time']);
		//$time_spent = new DateTime($form_info['time_spent']);

		$header[] = '<table cellpadding="1px" autosize="1" width="100%" style="border-spacing:0;overflow:wrap;border:1px solid black;font-size:14px">';
		$header[] = '<tr>';
		$header[] = '<td style="border:1px solid black;">Date requested: <span style="text-decoration:underline;">'.($form_info['date_requested'] > 0 ? date('m/d/Y', $form_info['date_requested']) : '').'</span></td>';
		$header[] = '<td style="border:1px solid black;">Property: <span style="text-decoration:underline;">'.html_encode($form_info['pro_name']).'</span></td>';
		$header[] = '<td style="border:1px solid black;">Unit #: <span style="text-decoration:underline;">'.html_encode($form_info['unit_number']).'</span></td>';
		$header[] = '<td style="border:1px solid black;">Date performed: <span style="text-decoration:underline;">'.($form_info['submitted_by_technician'] > 0 ? date('m/d/Y', $form_info['submitted_by_technician']) : '').'</span></td>';
		$header[] = '</tr>';
		$header[] = '<tr>';
		$header[] = '<td style="border:1px solid black;">Time in: <span style="text-decoration:underline;">'.$start_time->format('h:i a').'</span></td>';
		$header[] = '<td style="border:1px solid black;">Time out: <span style="text-decoration:underline;">'.$end_time->format('h:i a').'</span></td>';
		$header[] = '<td style="border:1px solid black;">Total time: <span style="text-decoration:underline;">'.html_encode($form_info['time_spent']).'</span></td>';
		$header[] = '<td style="border:1px solid black;">Performed by: <span style="text-decoration:underline;">'.html_encode($form_info['realname']).'</span></td>';
		$header[] = '</tr>';
		$header[] = '</table>';

		$header1[] = '<p style="font-weight:bold;text-align:center">APARTMENT PAINTER PUNCH LIST</p>';
		$header1[] = implode('', $header);
		$mPDF->WriteHTML(implode('', $header1));

		$mPDF->SetColumns(2);
		$output[] = '<table cellpadding="1px" style="border-spacing:0;margin-top:5px">';

		$i = 1;
		foreach($locations as $location)
		{
			if ($i == 7)
			{
				$output[] = '</table>';
				$output[] = '<newcolumn />';
				$output[] = '<table cellpadding="1px" style="border-spacing:0;margin-top:5px">';
			}

			$output[] = '<tr>';
			$output[] = '<td style="padding-top:8px"><strong style="text-decoration:underline;font-size:13px">'.$location['location_name'].'</strong></td>';
			$output[] = '<td colspan="4"></td>';
			$output[] = '</tr>';

			foreach($check_list as $cur_item)
			{
				if ($location['id'] == $cur_item['location_id'])
				{
					$status = isset($this->check_statuses[$cur_item['item_status']]) ? $this->check_statuses[$cur_item['item_status']] : '';
					$output[] = '<tr>';
					$output[] = '<td style="font-size:12px">'.$cur_item['equipment_name'].'</td>';

					if ($cur_item['replaced_action'] == 1)
					{
						if ($cur_item['replaced'] == 1)
							$output[] = '<td style="font-size:11px">Replaced</td>';	
						else
							$output[] = '<td style="font-size:11px">Not Replaced</td>';	
					}
					else
						$output[] = '<td style="font-size:11px">'.$status.'</td>';

					$output[] = '</tr>';
				}
			}
			++$i;
		}
		$output[] = '</table>';
		$mPDF->WriteHTML(implode('', $output));

		$completed = ($form_info['completed'] == 1) ? 'YES' : 'NO';

		$mPDF->SetColumns(1);
		$footer = [];
		$footer[] = '<p style="font-size:12px"><span style="text-decoration:underline;">Job Complete?</span> <span style="font-weight:bold">'.$completed.'</span></p>';

		if ($form_info['remarks'] != '')
		{
			$footer[] = '<p style="font-size:12px;margin-bottom:0;font-weight:bold">Comments</p>';
			$footer[] = '<p style="font-size:12px;margin-top:0;border:solid 1px black;padding:2px"><span style="">'.html_encode($form_info['remarks']).'<span></p>';
		}
		
		//$footer[] = '<p style="font-size:14px;"><span style="text-decoration:underline;">Time spent:</span> <strong>'.$form_info['time_spent'].'</strong></p>';
		$mPDF->WriteHTML(implode('', $footer));


		// Materials Used
		$materials_info = $DBLayer->select_all('punch_list_painter_materials', 'form_id='.$id);
		if (!empty($materials_info))
		{
			//$mPDF->SetColumns(1);
			$page_header3 = [];
			$page_header3[] = '<p style="font-weight:bold;text-align:center">MATERIAL(S) USED</p>';
			$page_header3[] = implode('', $header);
			$page_header3[] = '<p></p>';
			
			$mPDF->AddPage();
			$mPDF->WriteHTML(implode('', $page_header3));

			$materias = [];
			$materias[] = '<table cellpadding="2" autosize="1" width="100%" style="border-spacing:0;overflow:wrap;font-size:13px">';
			$materias[] = '<thead>';
			$materias[] = '<tr>';
			$materias[] = '<th style="'.$this->css_th.'"></th>';
			$materias[] = '<th style="'.$this->css_th.'">Material Name</th>';
			$materias[] = '<th style="'.$this->css_th.'">Quantity</th>';
			$materias[] = '<th style="'.$this->css_th.'">Cost per</th>';
			$materias[] = '<th style="'.$this->css_th.'">Total Cost</th>';
			$materias[] = '</tr>';
			$materias[] = '</thead>';
			$materias[] = '<tbody>';

			$i = 1;
			foreach($materials_info as $cur_info)
			{
				$materias[] = '<tr>';
				$materias[] = '<td style="'.$this->css_td.'">'.$i.'</td>';
				$materias[] = '<td style="'.$this->css_td.'">'.html_encode($cur_info['part_description']).'</td>';
				$materias[] = '<td style="'.$this->css_td.' text-align:center">'.html_encode($cur_info['part_quantity']).'</td>';
				$materias[] = '<td style="'.$this->css_td.' text-align:center">'.html_encode($cur_info['cost_per']).'</td>';
				$materias[] = '<td style="'.$this->css_td.' text-align:center">'.number_format($cur_info['cost_total'], 2, '.', '').'</td>';
				$materias[] = '</tr>';
				++$i;
			}

			$materias[] = '<tr>';
			$materias[] = '<td style="'.$this->css_td.' font-weight:bold;text-align:right" colspan="4">TOTAL:</td>';
			$materias[] = '<td style="'.$this->css_td.' font-weight:bold;text-align:center">'.number_format($form_info['total_cost'], 2, '.', '').'</td>';
			$materias[] = '</tr>';

			$materias[] = '</tbody>';
			$materias[] = '</table>';

			if ($form_info['materials_comment'] != '')
			{
				$materias[] = '<p style="font-size:12px;margin-bottom:0;font-weight:bold">Comments</p>';
				$materias[] = '<p style="font-size:12px;margin-top:0;border:solid 1px black;padding:2px"><span style="">'.html_encode($form_info['materials_comment']).'<span></p>';
			}

			$mPDF->WriteHTML(implode('', $materias));
		}

		if ($form_info['file_path'] != '')
		{
			$path = $form_info['file_path'];
		}
		else
		{
			$SwirftUploader = new SwiftUploader;
			$path = $SwirftUploader->checkPath('painter_punch_list');

			$DBLayer->update('punch_list_management_maint_request_form', ['file_path' => $path], $id);
		}

        // Upload/update file to path without slash in end
		$file_path = SITE_ROOT . $path;
		$mPDF->Output($file_path.'/painter_form_'.$id.'.pdf', 'F');
	}

	function rowExists($check_list, $equipment_id)
	{
		$output = [];
		$existing_status = false;

		foreach($check_list as $cur_info)
		{
			if ($cur_info['equipment_id'] == $equipment_id)
			{
				$output[] = '<input name="existing_status['.$cur_info['id'].']" type="hidden" value="'.$cur_info['id'].'">';
				$existing_status = true;
			}
		}

		if (!$existing_status)
			$output[] = '<input name="new_status['.$equipment_id.']" type="hidden" value="'.$equipment_id.'">';

		return implode("\n", $output);
	}

	function getCheckListField($value, $check_list, $equipment_id)
	{
		$output = [];
		$existing_status = false;

		foreach($check_list as $cur_info)
		{
			if ($cur_info['equipment_id'] == $equipment_id)
			{
				$output[] = '<input name="item_status['.$cur_info['id'].']" class="form-check-input" type="radio" id="field'.++$this->field_count.'" value="'.$value.'" '.($cur_info['item_status'] == $value ? 'checked' : '').'>';
				$existing_status = true;
			}
		}

		if (!$existing_status)
		{
			$output[] = '<input name="item_status['.$equipment_id.']" class="form-check-input" type="radio" id="field'.++$this->field_count.'" value="'.$value.'">';
		}

		return implode("\n", $output);
	}

	function getReplaced($check_list, $equipment_id)
	{
		$output = [];
		$existing_status = false;

		foreach($check_list as $cur_info)
		{
			if ($cur_info['equipment_id'] == $equipment_id)
			{
				$output[] = '<input type="checkbox" name="replaced['.$cur_info['id'].']" value="1" '.($cur_info['replaced'] == 1 ? 'checked' : '').' id="field'.++$this->field_count.'" class="form-check-input">';
				$existing_status = true;
			}
		}

		if (!$existing_status)
			$output[] = '<input type="checkbox" name="replaced['.$equipment_id.']" value="1" id="field'.++$this->field_count.'" class="form-check-input">';

		return implode("\n", $output);
	}
}
