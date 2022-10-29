<?php

class PunchList
{
	public $css_main = 'font-size:12px;';
	public $css_td = 'border:1px solid black;';
	public $css_th = 'border:1px solid black;';

	public $dropdown_params = [
        0 => 'n/a',
        1 => 'Replaced', 
        2 => 'Repaired', 
        3 => 'Parts on Order',
		4 => 'Re-Keyed',
	];

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

		$locations = [];
		$query = [
			'SELECT'	=> 'l.*',
			'FROM'		=> 'punch_list_management_maint_locations AS l',
			'ORDER BY'	=> 'l.loc_position',
		];
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		while ($row = $DBLayer->fetch_assoc($result)) {
			$locations[] = $row;
		}
	
		$equipments = [];
		$query = [
			'SELECT'	=> 'e.*',
			'FROM'		=> 'punch_list_management_maint_equipments AS e',
			'ORDER BY'	=> 'e.eq_position',
		];
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		while ($row = $DBLayer->fetch_assoc($result)) {
			$equipments[] = $row;
		}

		$request_info = [];
		$query = [
			'SELECT'	=> 'r.*, i.item_name, i.location_id, i.equipment_id, e.equipment_name',
			'FROM'		=> 'punch_list_management_maint_request_items AS r',
			'JOINS'		=> [
				[
					'INNER JOIN'	=> 'punch_list_management_maint_request_form AS f',
					'ON'			=> 'f.id=r.form_id'
				],
				[
					'LEFT JOIN'	=> 'punch_list_management_maint_items AS i',
					'ON'			=> 'i.id=r.item_id'
				],
				[
					'LEFT JOIN'		=> 'punch_list_management_maint_equipments AS e',
					'ON'			=> 'e.id=i.equipment_id'
				],
			],
		//	'ORDER BY'	=> 'l.location_name, i.item_name',
			'WHERE'		=> 'f.id='.$id
		];
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		while ($row = $DBLayer->fetch_assoc($result)) {
			$request_info[] = $row;
		}

		$mPDF = new \Mpdf\Mpdf();
		
		$header = $main_div = $output = [];

		$mPDF->WriteHTML('<p style="font-weight:bold;text-align:center">APARTMENT PUNCH LIST</p>');

		$header[] = '<table cellpadding="1px" autosize="1" width="100%" style="border-spacing:0;overflow:wrap;border:1px solid black;font-size:14px"><tr>';
		$header[] = '<td>Property: <span style="text-decoration:underline;">'.$form_info['pro_name'].'</span></td>';
		$header[] = '<td>Unit #: <span style="text-decoration:underline;">'.$form_info['unit_number'].'</span></td>';
		$header[] = '<td>Technician: <span style="text-decoration:underline;">'.$form_info['realname'].'</span></td>';
		$header[] = '<td>Date: <span style="text-decoration:underline;">'.date('m/d/Y', $form_info['submitted_by_technician']).'</span></td>';
		$header[] = '</tr></table>';

		$mPDF->WriteHTML(implode('', $header));
		
		$mPDF->SetColumns(3);

		$main_div[] = '<div style="'.$this->css_main.'">';

		foreach($locations as $location)
		{
			$locations_items = [];
			foreach($request_info as $request_item) 
			{
				if ($request_item['equipment_id'] == 0 && $location['id'] == $request_item['location_id'])
				{
					$item_status = isset($this->dropdown_params[$request_item['item_status']]) ? $this->dropdown_params[$request_item['item_status']] : 'n/a'; 

					$locations_items[] = '<div>';	
					$locations_items[] = '<span style="width:80px;text-decoration:underline;">'.$item_status.'</span> ';
					$locations_items[] = ' <span>'.$request_item['item_name'].'</span>';
					$locations_items[] = '</div>';
				}
			}

			if (!empty($locations_items))
			{
				$output[] = '<p><strong style="text-decoration:underline;">'.$location['location_name'].'</strong></p>';
				$output[] = implode('', $locations_items);
			}
			
			foreach($equipments as $equipment)
			{
				$equipment_items = [];
				foreach($request_info as $request_item) 
				{
					if ($request_item['equipment_id'] == $equipment['id'] && $location['id'] == $request_item['location_id'])
					{
						$item_status = isset($this->dropdown_params[$request_item['item_status']]) ? $this->dropdown_params[$request_item['item_status']] : 'n/a'; 

						$equipment_items[] = '<div>';	
						$equipment_items[] = '<span style="width:80px;text-decoration:underline;">'.$item_status.'</span> ';
						$equipment_items[] = ' <span>'.$request_item['item_name'].'</span>';
						$equipment_items[] = '</div>';
					}
				}
		
				if (!empty($equipment_items))
				{
					$output[] = '<p><strong style="text-decoration:underline;">'.$location['location_name'].': '.$equipment['equipment_name'].'</strong></p>';
					$output[] = implode('', $equipment_items);
				}
			}
		}

		$other_items = [];
		foreach($request_info as $request_item) 
		{
			if ($request_item['item_description'] != '')
			{
				$item_status = isset($this->dropdown_params[$request_item['item_status']]) ? $this->dropdown_params[$request_item['item_status']] : 'n/a'; 

				$other_items[] = '<div>';	
				$other_items[] = '<span style="width:80px;text-decoration:underline;">'.$item_status.'</span> ';
				$other_items[] = '<span>'.$request_item['item_name'].'</span>';
				$other_items[] = '</div>';
			}
		}

		if (!empty($other_items))
		{
			$output[] = '<p><strong style="text-decoration:underline;">OTHER</strong></p>';
			$output[] = implode('', $other_items);
		}

		$main_div[] = implode('', $output);
		$main_div[] = '</div>';

		$mPDF->WriteHTML(implode('', $main_div));

		$completed = ($form_info['completed'] == 1) ? 'YES' : 'NO';
		$mPDF->SetColumns(1);

		$footer = [];
		$footer[] = '<p style="font-size:11px"><span style="text-decoration:underline;">Job Complete?</span> <span style="font-weight:bold">'.$completed.'</span></p>';

		if ($form_info['remarks'] != '')
		{
			$footer[] = '<p style="font-size:11px;margin-bottom:0;font-weight:bold">Comments</p>';
			$footer[] = '<p style="font-size:11px;margin-top:0;border:solid 1px black;padding:2px"><span style="">'.html_encode($form_info['remarks']).'<span></p>';
		}
		
		$footer[] = '<p style="font-size:11px;"><span style="text-decoration:underline;">Time spent:</span> <strong>'.$form_info['time_spent'].'</strong></p>';
		$mPDF->WriteHTML(implode('', $footer));


		// PAGE 2 MOISTURE CHECK LIST
		$moisture_items = [];
		$query = [
			'SELECT'	=> 'ch.*, m.moisture_name, m.location_id, l.location_name',
			'FROM'		=> 'punch_list_management_maint_moisture_check_list AS ch',
			'JOINS'		=> [
				[
					'INNER JOIN'	=> 'punch_list_management_maint_request_form AS f',
					'ON'			=> 'f.id=ch.form_id'
				],
				[
					'INNER JOIN'	=> 'punch_list_management_maint_moisture AS m',
					'ON'			=> 'm.id=ch.moisture_id'
				],
				[
					'INNER JOIN'	=> 'punch_list_management_maint_locations AS l',
					'ON'			=> 'l.id=m.location_id'
				],
			],
		//	'ORDER BY'	=> 'l.location_name, i.item_name',
			'WHERE'		=> 'f.id='.$id
		];
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		while ($row = $DBLayer->fetch_assoc($result)) {
			$moisture_items[] = $row;
		}

		if (!empty($moisture_items))
		{
			$page_header2 = $page2 = [];
			$page_header2[] = '<p style="font-weight:bold;text-align:center">MOISTURE CHECK LIST</p>';
			$page_header2[] = implode('', $header);
			$page_header2[] = '<p></p>';
			
			$mPDF->AddPage();
			$mPDF->WriteHTML(implode('', $page_header2));

			$mPDF->SetColumns(2);

			$page2[] = '<div style="'.$this->css_main.'">';
			$location_id = 0;

			$check_statuses = [
				1 => '&nbsp;&nbsp;OK&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
				2 => '&nbsp;&nbsp;YES&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
				3 => '&nbsp;&nbsp;NO&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
				4 => 'Repaired',
				5 => 'Replaced',
			];

			foreach($moisture_items as $cur_info)
			{
				if ($location_id != $cur_info['location_id'])
				{
					if ($location_id) {
						$page2[] = '</div>';
					}
					$page2[] = '<div style="'.$this->css_main.'">';
					$page2[] = '<strong>'.html_encode($cur_info['location_name']).'</strong>';
					$location_id = $cur_info['location_id'];
				}
		
				$check_status = isset($check_statuses[$cur_info['check_status']]) ? $check_statuses[$cur_info['check_status']] : '&nbsp;&nbsp;OK&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				$page2[] = '<p style="margin:2px;padding:2px"><span style="text-decoration:underline;margin-right:5px">'. $check_status.'</span>&nbsp;<span style="margin-left:5px">'.$cur_info['moisture_name'].'<span></p>';
			}
			$page2[] = '</div>';

			if ($form_info['moisture_comment'] != '')
			{
				$page2[] = '<p style="font-size:11px;margin-bottom:0;font-weight:bold">Comments</p>';
				$page2[] = '<p style="font-size:11px;margin-top:0;border:solid 1px black;padding:2px"><span style="">'.html_encode($form_info['moisture_comment']).'<span></p>';
			}

			$mPDF->WriteHTML(implode('', $page2));
		}

		// PAGE 3
		$materials_info = $DBLayer->select_all('punch_list_management_maint_request_materials', 'form_id='.$id);
		if (!empty($materials_info))
		{
			$mPDF->SetColumns(1);
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
			$materias[] = '<th style="'.$this->css_th.'">Part #</th>';
			$materias[] = '<th style="'.$this->css_th.'">Part Description</th>';
			$materias[] = '<th style="'.$this->css_th.'">Performed</th>';
			$materias[] = '<th style="'.$this->css_th.'">Quantity</th>';
			$materias[] = '<th style="'.$this->css_th.'">Cost per</th>';
			$materias[] = '<th style="'.$this->css_th.'">Total Cost</th>';
			$materias[] = '</tr>';
			$materias[] = '</thead>';
			$materias[] = '<tbody>';

			$materials_info = $DBLayer->select_all('punch_list_management_maint_request_materials', 'form_id='.$id);
			if (!empty($materials_info ))
			{
				$i = 1;
				foreach($materials_info as $cur_info)
				{
					$type_work = ($cur_info['type_work'] == 1) ? 'Replaced' : 'Repaired';

					$materias[] = '<tr>';
					$materias[] = '<td style="'.$this->css_td.'">'.$i.'</td>';
					$materias[] = '<td style="'.$this->css_td.' text-align:right">'.html_encode($cur_info['part_number']).'</td>';
					$materias[] = '<td style="'.$this->css_td.'">'.html_encode($cur_info['part_description']).'</td>';
					$materias[] = '<td style="'.$this->css_td.'">'.$type_work.'</td>';
					$materias[] = '<td style="'.$this->css_td.' text-align:center">'.html_encode($cur_info['part_quantity']).'</td>';
					$materias[] = '<td style="'.$this->css_td.' text-align:center">'.html_encode($cur_info['cost_per']).'</td>';
					$materias[] = '<td style="'.$this->css_td.' text-align:center">'.html_encode($cur_info['cost_total']).'</td>';
					$materias[] = '</tr>';
					++$i;
				}

				$total_cost = ($form_info['total_cost'] != '') ? html_encode($form_info['total_cost']) : '0.00';
				$materias[] = '<tr><td colspan="6" style="'.$this->css_td.'"><strong>TOTAL:</strong></td><td style="'.$this->css_td.' text-align:center"><strong>'.$total_cost.'</strong></td></tr>';
			}

			$materias[] = '</tbody>';
			$materias[] = '</table>';

			if ($form_info['materials_comment'] != '')
			{
				$materias[] = '<p style="font-size:11px;margin-bottom:0;font-weight:bold">Comments</p>';
				$materias[] = '<p style="font-size:11px;margin-top:0;border:solid 1px black;padding:2px"><span style="">'.html_encode($form_info['materials_comment']).'<span></p>';
			}

			$mPDF->WriteHTML(implode('', $materias));
		}

		if ($form_info['file_path'] != '')
		{
			$path = $form_info['file_path'];
		}
		else
		{
			$swirftUploader = new SwiftUploader;
			$path = $swirftUploader->checkPath('punch_list_forms');

			$DBLayer->update('punch_list_management_maint_request_form', ['file_path' => $path], $id);
		}

        // Upload/update file to path without slash in end
		$file_path = SITE_ROOT . $path;
		$mPDF->Output($file_path.'/maintenance_form_'.$id.'.pdf', 'F');
	}

	function checkPart($part_desc, $part_number = '')
	{
		global $DBLayer;
		
		$query = array(
			'SELECT'	=> 'p.*, g.group_name',
			'FROM'		=> 'punch_list_management_maint_parts AS p',
			'JOINS'		=> array(
				array(
					'LEFT JOIN'		=> 'punch_list_management_maint_parts_group AS g',
					'ON'			=> 'g.id=p.group_id'
				),
			),
			//'WHERE'		=> '(UPPER(p.part_name)=UPPER(\''.$DBLayer->escape($part_desc).'\') OR UPPER(p.part_number)=UPPER(\''.$DBLayer->escape($part_number).'\'))'
			'WHERE' => 'p.part_name LIKE \''.$DBLayer->escape('%'.$part_desc.'%').'\'',

		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$part_info = $DBLayer->fetch_assoc($result);

		// if has nubmer
		if ($part_number == '' && $part_info['part_number'] != '')
		{
			return $part_info['part_number'];
		}
		// if no number
		else if ($part_number != '' && $part_info['part_number'] == '' && $part_info['part_name'] != '')
		{
			$data = [
				'part_number'		=> $part_number,
			];
			$DBLayer->update('punch_list_management_maint_parts', $data, $part_info['id']);

			return $part_number;
		}
		// if no number and no descr
		else if ($part_info['part_number'] == '' && $part_info['part_name'] == '')
		{
			$data = [
				'group_id'			=> 0,
				'part_number'		=> $part_number,
				'part_name'			=> $part_desc,
			];
			$DBLayer->insert_values('punch_list_management_maint_parts', $data);
		}
	}

	function createMaintForm($data)
	{
		global $DBLayer;

		$hash_key = random_key(5, true, true);
		$form_data = [
			'date_requested'	=> isset($data['start_date']) ? $data['start_date'] : time(),
			'property_id'		=> isset($data['property_id']) ? $data['property_id'] : 0,
			'unit_number'		=> isset($data['unit_number']) ? $data['unit_number'] : '',
			'technician_id'		=> isset($data['employee_id']) ? $data['employee_id'] : 0,
			'hash_key'			=> $hash_key,
			'form_type'			=> 1
		];
		$form_id = $DBLayer->insert_values('punch_list_management_maint_request_form', $form_data);

		// FILL OUT CHECK LIST FORM
		$query = array(
			'SELECT'	=> 'm.*, l.location_name',
			'FROM'		=> 'punch_list_management_maint_moisture AS m',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'punch_list_management_maint_locations AS l',
					'ON'			=> 'l.id=m.location_id'
				),
			),
			'ORDER BY'	=> 'l.location_name, m.moisture_name',
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$moisture_info = [];
		while ($row = $DBLayer->fetch_assoc($result)) {
			$moisture_info[] = $row;
		}

		if (!empty($moisture_info) && $form_id > 0)
		{
			foreach($moisture_info as $cur_info)
			{
				$form_data = [
					'moisture_id'		=> $cur_info['id'],
					'check_status'		=> 0,
					'form_id'			=> $form_id,
				];
				$new_id = $DBLayer->insert_values('punch_list_management_maint_moisture_check_list', $form_data);
			}
		}

		return $form_id;
	}

	function createPainterForm($data)
	{
		global $DBLayer;

		$hash_key = random_key(5, true, true);
		$form_data = [
			'date_requested'	=> isset($data['start_date']) ? $data['start_date'] : time(),
			'property_id'		=> isset($data['property_id']) ? $data['property_id'] : 0,
			'unit_number'		=> isset($data['unit_number']) ? $data['unit_number'] : '',
			'technician_id'		=> isset($data['employee_id']) ? $data['employee_id'] : 0,
			'hash_key'			=> $hash_key,
			'form_type'			=> 2
		];
		$form_id = $DBLayer->insert_values('punch_list_management_maint_request_form', $form_data);

		return $form_id;
	}
}
