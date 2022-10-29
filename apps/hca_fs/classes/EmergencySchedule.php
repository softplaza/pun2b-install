<?php

class EmergencySchedule
{
	public $key_id = 0;
	public $time_of_monday = 0;
	public $time_of_friday = 0;


	function getNextAssigned($zone, $cur_day)
	{
		global $emergency_info;
		
		$output = '';
		
		foreach($emergency_info as $key => $cur_info)
		{
			// if check availability and assign
			// Check by last ID
			if ($cur_info['hca_fs_zone'] == $zone)
			{
				$img_setup_user = '<span class="setup-user"><img src="'.BASE_URL.'/img/edit.png"/></span>';
				$output = '<input type="hidden" name="form['.$cur_day.']['.$zone.']" value="'.$cur_info['id'].'">';
				$output .= '<div class="assign-info prob" onclick="openPopUpWindow(\''.$cur_day.'\','.$zone.',0);">'.$img_setup_user.'<strong>'.$cur_info['realname'].'</strong></div>';
				
				//Set used items in the end of array
				$this->key_id = $key;
				
				break;
			}
		}
		
		return $output;
	}
	
	function rebuildArray()
	{
		global $emergency_info;
		
		$output = array();
		$key_id = $this->key_id;
		
		foreach($emergency_info as $key => $cur_info)
		{
			if ($key != $key_id)
				$output[$key] = $cur_info;
		}
		
		if (isset($emergency_info[$key_id]))
			$output[$key_id] = $emergency_info[$key_id];
		
		return $output;
	}
	
	function moveToEnd($uid)
	{
		global $emergency_info;
		
		$output = array();
		
		foreach($emergency_info as $key => $cur_info)
		{
			if ($key != $uid)
				$output[$key] = $cur_info;
		}
		
		if (isset($emergency_info[$uid]))
			$output[$uid] = $emergency_info[$uid];
		
		return $output;
	}

	function update_pdf()
	{
		global $DBLayer, $Config;

		$CurDayOfWeek = new DateTime($_GET['date']);

		$FirstDayOfWeek = clone $CurDayOfWeek;
		$FridayHeadDate = clone $CurDayOfWeek;
		$LastDayOfPeriod = clone $CurDayOfWeek;

		$FirstDayOfWeek->modify('Monday this week');
		$FridayHeadDate->modify('Friday this week');

		$number_of_week = isset($_GET['weeks']) && intval($_GET['weeks'] < 8) ? intval($_GET['weeks']) : intval($Config->get('o_hca_fs_number_of_week'));
		$LastDayOfPeriod->modify('+'.$number_of_week.' weeks');

		// Get DB Data
		$query = array(
			'SELECT'	=> 'es.*, u.realname',
			'FROM'		=> 'hca_fs_emergency_schedule AS es',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'users AS u',
					'ON'			=> 'es.user_id=u.id'
				),
			),
			'WHERE'		=> 'es.date_week_of >= \''.$FirstDayOfWeek->format('Y-m-d').'\' AND es.date_week_of <= \''.$LastDayOfPeriod->format('Y-m-d').'\'',
			'ORDER BY'	=> 'es.date_week_of',
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$es_1 = $es_2 = $es_3 = array();
		while ($row = $DBLayer->fetch_assoc($result)) {
			if ($row['zone'] == 1)
				$es_1[] = $row;
			else if ($row['zone'] == 2)
				$es_2[] = $row;
			else if ($row['zone'] == 3)
				$es_3[] = $row;
		}
		
		$query = array(
			'SELECT'	=> 'p.zone, p.pro_name',
			'FROM'		=> 'sm_property_db AS p',
			'WHERE'		=> 'p.enabled=1 AND p.zone > 0',
			'ORDER BY'	=> 'p.pro_name',
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$property_1 = $property_2 = $property_3 = array();
		while ($row = $DBLayer->fetch_assoc($result)) {
			if ($row['zone'] == 1)
				$property_1[] = $row['pro_name'];
			else if ($row['zone'] == 2)
				$property_2[] = $row['pro_name'];
			else if ($row['zone'] == 3)
				$property_3[] = $row['pro_name'];
		}
		
		$query = array(
			'SELECT'	=> 'u.id, u.realname, u.cell_phone, u.home_phone, p.zone',
			'FROM'		=> 'users AS u',
			'JOINS'		=> array(
				array(
					'LEFT JOIN'		=> 'sm_property_db AS p',
					'ON'			=> 'u.id=p.emergency_uid'
				),
			),
			'WHERE'		=> 'u.group_id='.intval($Config->get('o_hca_fs_maintenance')),
			'ORDER BY'	=> 'u.realname',
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$users_info = array();
		while ($row = $DBLayer->fetch_assoc($result)) {
			if (!isset($users_info[$row['id']]))
				$users_info[$row['id']] = $row;
		}
		
		$html_table = '<p style="text-align:center;text-decoration: underline;"><strong>'.$FridayHeadDate->format('F, Y').'</strong></p>';
		$html_table .= '<p style="text-align:center;text-decoration: underline;"><strong>MAINTENANCE EMERGENCY STAND-BY WEEKENDS</strong></p>';
		$html_table .= '<p style="text-align:center"><strong>Covering Weekends - Friday 5:00 pm through Monday - 8:00 am</strong></p>';
		$html_table .= '<table cellpadding="2px" autosize="1" width="100%" style="overflow: wrap">';
		
		if (!empty($es_1))
		{
			$nw = 0;
			$html_table .= '<tr><td colspan="6" style="text-align:center"><strong>ZONE 1</strong></td></tr>';
			$property_zone1 = array();
			
			foreach($es_1 as $key => $cur_info)
			{
				$property_name = isset($property_1[$key]) ? html_encode($property_1[$key]) : '';
				if ($nw < $number_of_week)
				{
					$DateOfWeek = new DateTime($cur_info['date_week_of']);

					$Friday = clone $DateOfWeek;
					$Saturday = clone $DateOfWeek;
					$Sunday = clone $DateOfWeek;

					$html_table .= '<tr><td width="30%">'.strtoupper($property_name).'</td>';
					$html_table .= '<td>'.$Friday->modify("+4 days")->format("F").'</td>';
					$html_table .= '<td>'.$Friday->format("j").'</td>';
					$html_table .= '<td>'.$Saturday->modify("+5 days")->format("j").'</td>';
					$html_table .= '<td>'.$Sunday->modify("+6 days")->format("j").'</td>';
					$html_table .= '<td style="text-align:right">'.html_encode($cur_info['realname']).'</td></tr>';
				}
				else
					$html_table .= '<tr><td colspan="6">'.strtoupper($property_name).'</td></tr>';
				
				if (isset($property_1[$key]))
					$property_zone1[$key] = $property_1[$key];
				
				++$nw;
			}
			
			//add other Properties
			foreach($property_1 as $key => $property)
			{
				if (!isset($property_zone1[$key]))
					$html_table .= '<tr><td colspan="6">'.strtoupper($property).'</td></tr>';
			}
		}
		
		if (!empty($es_2))
		{
			$nw = 0;
			$html_table .= '<tr><td colspan="6" style="text-align:center"><strong>ZONE 2</strong></td></tr>';
			$property_zone2 = array();
			foreach($es_2 as $key => $cur_info)
			{
				$property_name = isset($property_2[$key]) ? html_encode($property_2[$key]) : '';
				
				if ($nw < $number_of_week)
				{
					$DateOfWeek = new DateTime($cur_info['date_week_of']);

					$Friday = clone $DateOfWeek;
					$Saturday = clone $DateOfWeek;
					$Sunday = clone $DateOfWeek;

					$html_table .= '<tr><td width="30%">'.strtoupper($property_name).'</td>';
					$html_table .= '<td>'.$Friday->modify("+4 days")->format("F").'</td>';
					$html_table .= '<td>'.$Friday->format("j").'</td>';
					$html_table .= '<td>'.$Saturday->modify("+5 days")->format("j").'</td>';
					$html_table .= '<td>'.$Sunday->modify("+6 days")->format("j").'</td>';
					$html_table .= '<td style="text-align:right">'.html_encode($cur_info['realname']).'</td></tr>';
				}
				else
					$html_table .= '<tr><td colspan="6">'.strtoupper($property_name).'</td></tr>';
				
				if (isset($property_2[$key]))
					$property_zone2[$key] = $property_2[$key];
				
				++$nw;
			}
			
			//add other Properties
			foreach($property_2 as $key => $property)
			{
				if (!isset($property_zone2[$key]))
					$html_table .= '<tr><td colspan="6">'.strtoupper($property).'</td></tr>';
			}
		}
		
		if (!empty($es_3))
		{
			$nw = 0;
			$html_table .= '<tr><td colspan="6" style="text-align:center"><strong>ZONE 3</strong></td></tr>';
			$property_zone3 = array();
			foreach($es_3 as $key => $cur_info)
			{
				$property_name = isset($property_3[$key]) ? html_encode($property_3[$key]) : '';

				if ($nw < $number_of_week)
				{
					$DateOfWeek = new DateTime($cur_info['date_week_of']);

					$Friday = clone $DateOfWeek;
					$Saturday = clone $DateOfWeek;
					$Sunday = clone $DateOfWeek;

					$html_table .= '<tr><td width="30%">'.strtoupper($property_name).'</td>';
					$html_table .= '<td>'.$Friday->modify("+4 days")->format("F").'</td>';
					$html_table .= '<td>'.$Friday->format("j").'</td>';
					$html_table .= '<td>'.$Saturday->modify("+5 days")->format("j").'</td>';
					$html_table .= '<td>'.$Sunday->modify("+6 days")->format("j").'</td>';
					$html_table .= '<td style="text-align:right">'.html_encode($cur_info['realname']).'</td></tr>';
				}
				else
					$html_table .= '<tr><td colspan="6">'.strtoupper($property_name).'</td></tr>';
				
				if (isset($property_3[$key]))
					$property_zone3[$key] = $property_3[$key];
				
				++$nw;
			}
			
			//add other Properties
			foreach($property_3 as $key => $property)
			{
				if (!isset($property_zone3[$key]))
					$html_table .= '<tr><td colspan="6">'.strtoupper($property).'</td></tr>';
			}
		}
		
		$html_table .= '</table>';
		
		$mPDF = new \Mpdf\Mpdf();
		$mPDF->WriteHTML($html_table);
		
		if (!empty($users_info))
		{
			$mPDF->AddPage();
			$html_table = '<p style="text-align:center"><strong>MAINTENANCE PERSONNEL</strong></p>';
			$html_table .= '<table cellpadding="2px" autosize="1" width="100%" style="overflow:wrap">';
			$html_table .= '<tr><td width="30%"><strong>NAME</strong></td><td width="30%"><strong>HOME PHONE</strong></td><td width="30%"><strong>CELL PHONE</strong></td></tr>';
			
			foreach($users_info as $user_info)
			{
				$html_table .= '<tr><td width="30%">'.strtoupper(html_encode($user_info['realname'])).'</td><td width="30%">'.html_encode($user_info['home_phone']).'</td><td width="30%">'.html_encode($user_info['cell_phone']).'</td></tr>';
			}
			
			$html_table .= '</table>';
			$mPDF->WriteHTML($html_table);
		}
		
		$query = array(
			'SELECT'	=> 'p.pro_name, u.realname',
			'FROM'		=> 'sm_property_db AS p',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'users AS u',
					'ON'			=> 'u.id=p.emergency_uid'
				),
			),
			'WHERE'		=> 'p.enabled=1 AND u.group_id='.intval($Config->get('o_hca_fs_maintenance')),
			'ORDER BY'	=> 'p.pro_name',
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		$properties_info = array();
		while ($row = $DBLayer->fetch_assoc($result)) {
			$properties_info[] = $row;
		}
		
		// ADD STAND_BY WEEK DAYS SCHEDULE
		if (!empty($properties_info))
		{
			$css_td1 = 'style="border: 1px solid grey;padding:0;margin:0;"';
			$css_td2 = 'style="border: 1px solid grey;padding:0;margin:0;text-align:right"';
			
			$mPDF->AddPage();
			$html_table = '<p style="text-align:center;text-decoration: underline;"><strong>MAINTENANCE EMERGENCY STAND-BY WEEKDAYS</strong></p>';
			$html_table .= '<p style="text-align:center;text-decoration: underline;"><strong>'.strtoupper($FridayHeadDate->format('F, Y')).' (Monday - Thursday)</strong></p>';
			//$html_table .= '<table cellpadding="2px" autosize="1" width="100%" style="overflow:wrap">';
			$html_table .= '<table cellpadding="2px" autosize="1" width="100%" style="border-spacing:0;overflow:wrap;border:1px solid black;font-size:16px">';
			$html_table .= '<tr>';
			$html_table .= '<td '.$css_td1.'><strong>PROPERTY NAME</strong></td>';
			$html_table .= '<td '.$css_td2.'><strong>EMPLOYEE NAME</strong></td>';
			$html_table .= '</tr>';
			
			foreach($properties_info as $cur_info)
			{
				$html_table .= '<tr>';
				$html_table .= '<td '.$css_td1.'>'.strtoupper(html_encode($cur_info['pro_name'])).'</td>';
				$html_table .= '<td '.$css_td2.'>'.html_encode($cur_info['realname']).'</td>';
				$html_table .= '</tr>';
			}
			
			$html_table .= '</table>';
			$mPDF->WriteHTML($html_table);
		}
		
		$mPDF->Output('files/emergency_schedule.pdf', 'F');
	}

	function getZone($new_array)
	{
		$zone = 0;
		$flag = false;
		if (!empty($new_array))
		{
			foreach($new_array as $new_info)
			{
				if ($new_info['hca_fs_zone'] > 0 && !$flag) {
					$zone = $new_info['hca_fs_zone'];
					$flag = true;
				}
			}
		}
		
		return $zone;
	}

	function createTableFromArray($property_info, $new_array)
	{
		$output = $table = array();
		$zone = $this->getZone($new_array);
		if (!empty($property_info) && !empty($new_array)){
			foreach($property_info as $cur_info){
				if ($cur_info['zone'] == $zone)
					$output[] = $cur_info['pro_name'];
			}
			
			$table[] = '<table>';
			$switch = true;
			foreach($output as $name){
				if ($switch)
					$table[] = '<tr><td style="border: solid 1px #000000">'.$name.'</td>';
				else
					$table[] = '<td style="border: solid 1px #000000">'.$name.'</td></tr>';
				
				$switch = ($switch) ? false : true;
			}
			
			if (!$switch)
				$table[] = '<td style="border: solid 1px #000000"></td></tr>';
			
			$table[] = '</table>';
		}
		
		return implode('', $table);
	}
	
	function getPropertiesByZone($array, $zone = 0)
	{
		$new_array = array();
		if (!empty($array)) {
			foreach($array as $cur_info) {
				if ($cur_info['zone'] > 0 && $cur_info['zone'] == $zone) {
					$new_array[] = $cur_info['pro_name'];
				}
			}
		}
		return $new_array;
	}
}
