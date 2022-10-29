<?php

class GenPDF
{
	public $users_list = [];
	public $work_orders = [];
	public $assignments_info = [];
	public $property_info = [];
	public $group_id = 0;
	public $first_day_of_week = 0;
	public $days_of_week = array(
		1 => 'Monday',
		2 => 'Tuesday',
		3 => 'Wednesday',
		4 => 'Thursday',
		5 => 'Friday',
		6 => 'Saturday',
		7 => 'Sunday'
	);
	public $time_slots = array(
		1 => 'ALL DAY', 
		2 => 'A.M.', 
		3 => 'P.M.', 
		4 => 'DAY OFF', 
		5 => 'SICK DAY', 
		6 => 'VACATION',
		7 => 'STAND BY'
	);
	
	function __construct($work_orders = [], $assignments_info = [])
	{
		$this->work_orders = $work_orders;
		$this->assignments_info = $assignments_info;
	}
	
	function checkDay($day)
	{
		$flag = false;
		if (!empty($this->work_orders))
		{
			foreach($this->work_orders as $work_order_info)
			{
				$day_number = date('N', strtotime($work_order_info['scheduled']));
				if ($day_number == $day)
				{
					$flag = true;
					break;
				}
			}
		}
		
		return $flag;
	}
	
	// Generate In-House Shedule
	function GenWholeShedule()
	{
		global $Config, $Core, $work_orders_info;
		
		if (!empty($work_orders_info))
		{
			if ($this->checkDay(6))
				$this->days_of_week[6] = 'Saturday';
			
			if ($this->checkDay(7))
				$this->days_of_week[7] = 'Sunday';
			
			$css_th = 'style="border:1px solid grey;padding:0;margin:0;text-align:center;text-transform:uppercase;font-size:16px;font-weight:bold;"';
			$css_td = 'style="border: 1px solid grey;padding:0;margin:0;text-align:center"';
			
			$content = '<p style="text-align:center;font-size:16px"><strong>WEEKLY SCHEDULE</strong></p>';
			$content .= '<table cellpadding="0" autosize="1" width="100%" style="border-spacing:0;overflow:wrap;border:1px solid black;font-size:12px">';
			
			$content .= '<tr>';
			$content .= '<td width="11%" '.$css_th.'></td>';
			$header_days = $this->first_day_of_week;
			foreach ($this->days_of_week as $key => $day) {
				$content .=  '<td '.$css_th.'>'.date('l', $header_days).'</td>';
				$header_days = $header_days + 86400;
			}
			$content .= '</tr>';
			
			$content .= '<tr>';
			$content .= '<td width="11%" '.$css_th.'></td>';
			$header_days = $this->first_day_of_week;
			foreach ($this->days_of_week as $key => $day) {
				$content .=  '<td '.$css_th.'>'.date('m/d', $header_days).'</td>';
				$header_days = $header_days + 86400;
			}
			$content .= '</tr>';
			
			foreach($this->users_list as $user_info)
			{
				$content .= '<tr>';
				$content .= '<td '.$css_td.'><strong>'.html_encode($user_info['realname']).'</strong></td>';

				$time_next_date = $this->first_day_of_week;
				foreach ($this->days_of_week as $key => $day)
				{
					$cur_date = date('Ymd', $time_next_date);
					$cur_work_order = array();
					
					foreach($this->work_orders as $work_order_info)
					{
						$day_number = date('N', strtotime($work_order_info['scheduled']));
	
						if ($user_info['id'] == $work_order_info['employee_id'] && $key == $day_number)
						{
							$cur_work_order[] = ($work_order_info['time_slot'] > 3) ? '<strong>'.$this->time_slots[$work_order_info['time_slot']].'</strong>' : '<strong>'.html_encode($work_order_info['pro_name']).'</strong>';
							
							$geo_code = ($work_order_info['geo_code'] != '') ? ', GL Codes: <strong>'.html_encode($work_order_info['geo_code']).'</strong>' : '';
							if ($work_order_info['unit_number'] != '')
								$cur_work_order[] = '<p class="wo-time">Unit#: <strong>'.html_encode($work_order_info['unit_number']).'</strong>'.$geo_code.'</p>';
							
							$time_slot = isset($this->time_slots[$work_order_info['time_slot']]) ? $this->time_slots[$work_order_info['time_slot']] : 'n/a';
							
							if ($work_order_info['time_slot'] < 4)
								$cur_work_order[] = '<p class="wo-time">Time: <strong>'.$time_slot.'</strong></p>';
								
							if ($work_order_info['msg_for_maint'] != '')
								$cur_work_order[] = '<p class="msg-for-maint">'.html_encode($work_order_info['msg_for_maint']).'</p>';
						}
					}
					
					$content .= '<td '.$css_td.'>'.implode('', $cur_work_order).'</td>';
					
					$time_next_date = $time_next_date + 86400;
				}
				
				$content .= '</tr>';
			}
			
			$content .= '</table>';
			
			$mPDF = new \Mpdf\Mpdf();
			$mPDF->WriteHTML($content);
			if ($this->group_id == $Config->get('o_hca_fs_painters'))
				$mPDF->Output('files/painter_schedule.pdf', 'F');
			else
				$mPDF->Output('files/maintenance_schedule.pdf', 'F');
		}
		else
			$Core->add_warning('Cannot create PDF File.');
	}
	
	function genMainWeelkySchedule()
	{
		global $Config, $Core, $Facility, $work_orders_info, $first_day_of_week, $assignments_info;

		$css_th = 'style="border:1px solid grey;padding:0;margin:0;text-align:center;text-transform:uppercase;font-size:12px;font-weight:bold;"';
		$css_td = 'style="width:18%;border: 1px solid grey;padding:1px;margin:0;vertical-align:top"';
		$css_td_user = 'style="border: 1px solid grey;padding:1px;margin:0;vertical-align:top;text-transform: uppercase;"';

		$content = [];

		$content[] = '<html><head><style>@page {margin:.25in;padding:0;}</style></head><body>';

		if ($this->group_id == $Config->get('o_hca_fs_painters'))
			$content[] = '<p style="text-align:center;font-size:14px"><strong>PAINTER WEEKLY SCHEDULE</strong></p>';
		else
			$content[] = '<p style="text-align:center;font-size:14px"><strong>MAINTENANCE WEEKLY SCHEDULE</strong></p>';

		$content[] = '<table cellpadding="0" autosize="1.6" width="100%" style="border-spacing:0;overflow:wrap;border:1px solid black;font-size:12px">';

		$hasWeekendJob = $Facility->hasWeekendJob();
		$content[] = '<tr>';
		$content[] = '<td width="11%" '.$css_th.'></td>';
		$header_days = $this->first_day_of_week;
		foreach ($this->days_of_week as $key => $day)
		{
			$has_job = (in_array($key, array(6,7)) && $hasWeekendJob) || !in_array($key, array(6,7)) ? true : false;
			
			if ($has_job)
			{
				$content[] = '<td '.$css_th.'>';
				$content[] = '<p>'.date('l', $header_days).'</p>';
				$content[] =  '<p>'.date('m/d', $header_days).'<p>';
				$content[] ='</td>';
				$header_days = $header_days + 86400;
			}
		}
		$content[] = '</tr>';

		foreach ($this->users_list as $user_info) 
		{
			$content[] = '<tr>';
			$content[] = '<td '.$css_td_user.'>'.$user_info['realname'].'</td>';

			//$user_property_days = unserialize($user_info['hca_fs_property_days']);
			
			$time_next_date = $first_day_of_week;
			foreach ($this->days_of_week as $key => $day)
			{
				$cur_date = date('Ymd', $time_next_date);
				$assignment_list = $assignment_ids = array();
				$has_job = (in_array($key, array(6,7)) && $hasWeekendJob) || !in_array($key, array(6,7)) ? true : false;

				if (!empty($assignments_info))
				{
					foreach($assignments_info as $assignment)
					{
						$cur_assignment = array();
						if ($user_info['id'] == $assignment['user_id'] && $key == $assignment['day_of_week'])
						{
							$cur_assignment[] = '<strong>'.$this->property_info[$assignment['property_id']]['pro_name'].'</strong>';
							//$cur_assignment[] = '<p>'.$this->time_slots[$assignment['time_shift']].'</p>';
							$cur_assignment[] = '<p>Time: <strong>'.$this->time_slots[$assignment['time_shift']].'</strong></p>';

							$assignment_list[$assignment['id']] = '<div class="assign-info pending">'.implode('', $cur_assignment).'</div>';
							$assignment_ids[] = $assignment['id'];
						}
					}
				}
				
				$assigned_to_property = implode('', $assignment_list);
				$work_order_list = $work_order_ids = $cur_info = array();
				$day_off_id = $time_next_date + $user_info['id'];
				if (!empty($work_orders_info))
				{
					foreach($work_orders_info as $work_order_info)
					{
						$cur_work_order = array();
						$day_number = date('N', strtotime($work_order_info['scheduled']));

						if ($user_info['id'] == $work_order_info['employee_id'] && $key == $day_number)
						{
							$cur_work_order[] = ($work_order_info['time_slot'] > 3) ? '<strong>'.$this->time_slots[$work_order_info['time_slot']].'</strong>' : '<strong>'.html_encode($work_order_info['pro_name']).'</strong>';
							
							$geo_code = ($work_order_info['geo_code'] != '') ? ', GL Codes: <strong>'.html_encode($work_order_info['geo_code']).'</strong>' : '';
							if ($work_order_info['unit_number'] != '')
								$cur_work_order[] = '<p>Unit#: <strong>'.html_encode($work_order_info['unit_number']).'</strong>'.$geo_code.'</p>';
							
							$time_slot = isset($this->time_slots[$work_order_info['time_slot']]) ? $this->time_slots[$work_order_info['time_slot']] : 'n/a';
							
							if ($work_order_info['time_slot'] < 4)
								$cur_work_order[] = '<p>Time: <strong>'.$time_slot.'</strong></p>';
								
							if ($work_order_info['msg_for_maint'] != '')
								$cur_work_order[] = '<p>'.html_encode($work_order_info['msg_for_maint']).'</p>';
							
							$work_order_list[] = '<div>'.implode('', $cur_work_order).'</div>';
							
							$cur_info = $work_order_info;
							$work_order_ids[] = $work_order_info['id'];
						}
					}
					
				}
				
				if ($has_job)
				{
					if (!empty($work_order_list))
					{
						$content[] =  '<td '.$css_td.'>'.implode('', $work_order_list).'</td>'."\n";
					}
					else
					{
						// IS SCHEDULED PERMANENTLY WORKER ? 
						if ($assigned_to_property != '')
						{
							$content[] =  '<td '.$css_td.'>'.$assigned_to_property.'</td>'."\n";
							// IS UNASSIGNED WORKER ? 
						} else {
							$content[] =  '<td '.$css_td.'></td>'."\n";
						}
					} 
				}

				$time_next_date = $time_next_date + 86400;
			}

				$content[] = '</tr>';

		}

		$content[] = '</tbody>';
		$content[] = '</table>';
		$content[] = '</body></html>';

		$mPDF = new \Mpdf\Mpdf();
		//$mPDF->shrink_tables_to_fit=1.4;

		$mPDF->WriteHTML(implode('', $content));
		if ($this->group_id == $Config->get('o_hca_fs_painters'))
			$mPDF->Output('files/painter_schedule.pdf', 'F');
		else
			$mPDF->Output('files/maintenance_schedule.pdf', 'F');

		
	}




	// Generate Separated Shedule in One File
	function GenSeparateShedule()
	{
		global $Config, $Core, $work_orders_info;
		
		if (!empty($work_orders_info))
		{
			$mPDF = new \Mpdf\Mpdf();
			$css_td1 = 'style="border: 1px solid grey;padding:0;margin:0;text-align:center"';
			$css_td2 = 'style="border: 1px solid grey;padding:0;margin:0"';
			
			$first_page = true;
			foreach($this->users_list as $user_info)
			{
				if (!$first_page)
					$mPDF->AddPage();
				
				$content = '<p style="text-align:center;font-size:18px"><strong>Schedule of '.html_encode($user_info['realname']).'</strong></p>';
				$content .= '<table cellpadding="0" autosize="1" width="100%" style="border-spacing:0;overflow:wrap;border:1px solid black;font-size:12px">';
				$content .= '<tr>';
				$content .= '<th width="15%" '.$css_td1.'><strong>Date</strong></th>';
				$content .= '<th '.$css_td2.'><strong>Work Order Information</strong></th>';
				$content .= '</tr>';
				
				$next_date = $this->first_day_of_week;
				foreach ($this->days_of_week as $key => $day)
				{
					$content .= '<tr>';
					$content .=  '<td '.$css_td1.'><p><strong>'.date('l', $next_date).'</strong></p><p>'.date('F, d', $next_date).'</p></td>';
					
					$cur_date = date('Ymd', $next_date);
					$cur_work_order = $cur_assignment = array();
					
					if (!empty($this->assignments_info))
					{
						foreach($this->assignments_info as $assignment)
						{
							if ($user_info['id'] == $assignment['user_id'] && $key == $assignment['day_of_week'])
							{
								$cur_assignment[] = '<strong>'.$this->property_info[$assignment['property_id']]['pro_name'].'</strong>';
								$cur_assignment[] = '<p>'.$this->time_slots[$assignment['time_shift']].'</p>';
							}
						}
					}
					
					foreach($this->work_orders as $work_order_info)
					{
						$day_number = date('N', strtotime($work_order_info['scheduled']));
	
						if ($user_info['id'] == $work_order_info['employee_id'] && $key == $day_number)
						{
							$cur_work_order[] = ($work_order_info['time_slot'] > 3) ? '<strong>'.$this->time_slots[$work_order_info['time_slot']].'</strong>' : 'Property: <strong>'.html_encode($work_order_info['pro_name']).'</strong>';
							
							$geo_code = ($work_order_info['geo_code'] != '') ? ', GL Code: <strong>'.html_encode($work_order_info['geo_code']).'</strong>' : '';
							if ($work_order_info['unit_number'] != '')
								$cur_work_order[] = '<p class="wo-time">Unit: <strong>'.html_encode($work_order_info['unit_number']).'</strong>'.$geo_code.'</p>';
							
							$time_slot = isset($this->time_slots[$work_order_info['time_slot']]) ? $this->time_slots[$work_order_info['time_slot']] : 'n/a';
							
							if ($work_order_info['time_slot'] < 4)
								$cur_work_order[] = '<p class="wo-time">Shift: <strong>'.$time_slot.'</strong></p>';
								
							if ($work_order_info['msg_for_maint'] != '')
								$cur_work_order[] = '<p class="msg-for-maint">'.html_encode($work_order_info['msg_for_maint']).'</p>';
						}
					}
					
					if (!empty($cur_work_order))
						$content .= '<td '.$css_td2.'>'.implode('', $cur_work_order).'</td>';
					else if (!empty($cur_assignment))
						$content .= '<td '.$css_td2.'>'.implode('', $cur_assignment).'</td>';
					else
						$content .= '<td '.$css_td2.'></td>';
					
					$content .= '</tr>';
					
					$next_date = $next_date + 86400;
				}
				
				$content .= '</table>';
				$mPDF->WriteHTML($content);
				
				$first_page = false;
			}
			
			if ($this->group_id == $Config->get('o_hca_fs_painters'))
				$mPDF->Output('files/painter_schedule.pdf', 'F');
			else
				$mPDF->Output('files/maintenance_schedule.pdf', 'F');
		}
		else
			$Core->add_warning('Cannot create PDF File.');
	}
	
	// Generate Separated Shedule in One File by 4 columns
	function GenSeparateSheduleColumns()
	{
		global $Config, $Core, $work_orders_info;
		
		if (!empty($work_orders_info))
		{
			$mPDF = new \Mpdf\Mpdf();
			$css_td1 = 'style="border: 1px solid grey;padding:0;margin:0;text-align:center"';
			$css_td2 = 'style="border: 1px solid grey;padding:0;margin:0"';
			
			$first_page = true;
			foreach($this->users_list as $user_info)
			{
				if (!$first_page)
					$mPDF->AddPage();
				
				$content = '<p style="text-align:center;font-size:18px"><strong>Schedule of '.html_encode($user_info['realname']).'</strong></p>';
				$content .= '<table cellpadding="0" autosize="1" width="100%" style="border-spacing:0;overflow:wrap;border:1px solid black;font-size:12px">';
				$content .= '<tr>';
				$content .= '<th width="15%" '.$css_td1.'><strong>Date</strong></th>';
				$content .= '<th width="15%" '.$css_td1.'><strong>Property</strong></th>';
				$content .= '<th width="15%" '.$css_td1.'><strong>Shift</strong></th>';
				$content .= '<th '.$css_td2.'><strong>Remarks</strong></th>';
				$content .= '</tr>';
				
				$next_date = $this->first_day_of_week;
				foreach ($this->days_of_week as $key => $day)
				{
					$content .= '<tr>';
					$content .=  '<td '.$css_td1.'><p><strong>'.date('l', $next_date).'</strong></p><p>'.date('F, d', $next_date).'</p></td>';
					
					$cur_date = date('Ymd', $next_date);
					$cur_work_order = $cur_assignment = array();
					
					if (!empty($this->assignments_info))
					{
						foreach($this->assignments_info as $assignment)
						{
							if ($user_info['id'] == $assignment['user_id'] && $key == $assignment['day_of_week'])
							{
								$cur_assignment[] = '<td '.$css_td2.'><strong>'.$this->property_info[$assignment['property_id']]['pro_name'].'</strong></td>';
								$cur_assignment[] = '<td '.$css_td2.'><p>'.$this->time_slots[$assignment['time_shift']].'</p></td>';
								$cur_assignment[] = '<td '.$css_td2.'></td>';
							}
						}
					}
					
					foreach($this->work_orders as $work_order_info)
					{
						$day_number = date('N', strtotime($work_order_info['scheduled']));
						
						if ($user_info['id'] == $work_order_info['employee_id'] && $key == $day_number)
						{
							$geo_code = ($work_order_info['geo_code'] != '') ? ', GL Code: <strong>'.html_encode($work_order_info['geo_code']).'</strong>' : '';
							
							$unit_number = ($work_order_info['unit_number'] != '') ? '<p>Unit: <strong>'.html_encode($work_order_info['unit_number']).'</strong>'.$geo_code.'</p>' : '<p class="wo-time">'.$geo_code.'</p>';
							
							$cur_work_order[] = '<td '.$css_td2.'>'.(($work_order_info['time_slot'] > 3) ? '<strong>'.$this->time_slots[$work_order_info['time_slot']].'</strong>' : '<strong>'.html_encode($work_order_info['pro_name']).'</strong>').$unit_number.'</td>';
							
							$time_slot = isset($this->time_slots[$work_order_info['time_slot']]) ? $this->time_slots[$work_order_info['time_slot']] : 'n/a';
							
							if ($work_order_info['time_slot'] < 4)
								$cur_work_order[] = '<td '.$css_td2.'><p><strong>'.$time_slot.'</strong></p></td>';
							else
								$cur_work_order[] = '<td '.$css_td2.'></td>';
							
							if ($work_order_info['msg_for_maint'] != '')
								$cur_work_order[] = '<td '.$css_td2.'><p>'.html_encode($work_order_info['msg_for_maint']).'</p></td>';
							else
								$cur_work_order[] = '<td '.$css_td2.'></td>';
						}
					}
					
					if (!empty($cur_work_order))
						$content .= implode('', $cur_work_order);
					else if (!empty($cur_assignment))
						$content .= implode('', $cur_assignment);
					else
						$content .= '<td '.$css_td2.' colspan="3"></td>';
					
					$content .= '</tr>';
					
					$next_date = $next_date + 86400;
				}
				
				
				
				$content .= '</table>';
				$mPDF->WriteHTML($content);
				
				$first_page = false;
			}
			
			if ($this->group_id == $Config->get('o_hca_fs_painters'))
				$mPDF->Output('files/painter_schedule.pdf', 'F');
			else
				$mPDF->Output('files/maintenance_schedule.pdf', 'F');
		}
		else
			$Core->add_warning('Cannot create PDF File.');
	}
	
	// Generate Separated Shedule for each page
	function GenSeparatedUserShedule($id = 0)
	{
		global $Config, $Core, $work_orders_info;
		
		if (!empty($work_orders_info))
		{
			$css_td1 = 'style="border: 1px solid grey;padding:0;margin:0;text-align:center"';
			$css_td2 = 'style="border: 1px solid grey;padding:0;margin:0"';
			
			foreach($this->users_list as $user_info)
			{
				if ($user_info['id'] == $id) 
				{
					$mPDF = new \Mpdf\Mpdf();
					$content = '<p style="text-align:center;font-size:18px"><strong>Schedule of '.html_encode($user_info['realname']).'</strong></p>';
					$content .= '<table cellpadding="0" autosize="1" width="100%" style="border-spacing:0;overflow:wrap;border:1px solid black;font-size:12px">';
					$content .= '<tr>';
					$content .= '<th width="15%" '.$css_td1.'><strong>Date</strong></th>';
					$content .= '<th '.$css_td2.'><strong>Work Orders</strong></th>';
					$content .= '</tr>';
					
					$next_date = $this->first_day_of_week;
					foreach ($this->days_of_week as $key => $day)
					{
						$content .= '<tr>';
						$content .=  '<td '.$css_td1.'><p><strong>'.date('l', $next_date).'</strong></p><p>'.date('F, d', $next_date).'</p></td>';
						
						$cur_date = date('Ymd', $next_date);
						$cur_work_order = $cur_assignment = array();
						
						if (!empty($this->assignments_info))
						{
							foreach($this->assignments_info as $assignment)
							{
								if ($user_info['id'] == $assignment['user_id'] && $key == $assignment['day_of_week'])
								{
									$cur_assignment[] = '<strong>'.$this->property_info[$assignment['property_id']]['pro_name'].'</strong>';
									$cur_assignment[] = '<p>'.$this->time_slots[$assignment['time_shift']].'</p>';
								}
							}
						}
						
						$next_work_order = false;
						foreach($this->work_orders as $work_order_info)
						{
							$day_number = date('N', strtotime($work_order_info['scheduled']));
							
							if ($user_info['id'] == $work_order_info['employee_id'] && $key == $day_number)
							{
								if ($next_work_order)
									$cur_work_order[] = '<p><hr noshade="noshade" align="center" style="border-color: #CFCFCF; border-style: dashed; color: #CFCFCF; height: 1px; margin-top: 5px; text-align: center;"></p>';

								$cur_work_order[] = ($work_order_info['time_slot'] > 3) ? '<strong>'.$this->time_slots[$work_order_info['time_slot']].'</strong>' : 'Property: <strong>'.html_encode($work_order_info['pro_name']).'</strong>';
								
								$geo_code = ($work_order_info['geo_code'] != '') ? ', GL Code: <strong>'.html_encode($work_order_info['geo_code']).'</strong>' : '';
								if ($work_order_info['unit_number'] != '')
									$cur_work_order[] = '<p>Unit: <strong>'.html_encode($work_order_info['unit_number']).'</strong>'.$geo_code.'</p>';
								
								$time_slot = isset($this->time_slots[$work_order_info['time_slot']]) ? $this->time_slots[$work_order_info['time_slot']] : 'n/a';
								
								if ($work_order_info['time_slot'] < 4)
									$cur_work_order[] = '<p>Shift: <strong>'.$time_slot.'</strong></p>';
								
								if ($work_order_info['msg_for_maint'] != '')
									$cur_work_order[] = '<p>'.html_encode($work_order_info['msg_for_maint']).'</p>';

								$next_work_order = true;
							}
						}
						
						if (!empty($cur_work_order))
							$content .= '<td '.$css_td2.'>'.implode('', $cur_work_order).'</td>';
						else if (!empty($cur_assignment))
							$content .= '<td '.$css_td2.'>'.implode('', $cur_assignment).'</td>';
						else
							$content .= '<td '.$css_td2.'></td>';
						
						$content .= '</tr>';
						
						$next_date = $next_date + 86400;
					}
					
					$content .= '</table>';
					$mPDF->WriteHTML($content);
					
					if ($this->group_id == $Config->get('o_hca_fs_painters'))
						$mPDF->Output('files/painter_schedule_'.$user_info['id'].'.pdf', 'F');
					else
						$mPDF->Output('files/maintenance_schedule_'.$user_info['id'].'.pdf', 'F');

				}
			}
		}
		else
			$Core->add_warning('Cannot create PDF File.');
	}


}
