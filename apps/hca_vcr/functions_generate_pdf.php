<?php

// Generate In-House Shedule
function hca_sp_gen_inhouse_pdf_shedule()
{
	global $DBLayer, $Config, $main_info, $first_day_of_this_week;
	
	$time_slots = array(0 => 'ALL DAY', 1 => 'A.M.', 2 => 'P.M.');
	$gid = isset($_GET['gid']) ? intval($_GET['gid']) : 0;
	$search_by_vendor_id = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0;
	$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
	$output = false;
	
	if (!empty($main_info))
	{
		if ($gid == $Config->get('o_hca_fs_painters'))
			$html_table = '<p style="text-align:center;font-size:20px"><strong>PAINTER SCHEDULE</strong></p>';
		else
			$html_table = '<p style="text-align:center;font-size:20px"><strong>MAINTENANCE SCHEDULE</strong></p>';
		
		$html_table .= '<table cellpadding="2px" autosize="1" width="100%" style="overflow: wrap;border:1px solid black;">';
		
		$html_table .= '<tr><td width="15%"><strong>DATE</strong></td><td width="20%"><strong>PROPERTY</strong></td><td width="8%"><strong>UNIT</strong></td><td width="8%"><strong>SIZE</strong></td><td><strong>REMARKS</strong></td></tr>';
		
		foreach($main_info as $cur_info)
		{
			$html_table .= '<tr>';
			$html_table .= '<td>In House';
			$html_table .= '<p>'.format_time($cur_info['date_time'], 1).'</p>';
			$html_table .= '<p>'.$time_slots[$cur_info['shift']].'</p>';
			$html_table .= '</td>';
			$html_table .= '<td>'.html_encode($cur_info['pro_name']).'</td>';
			$html_table .= '<td>'.html_encode($cur_info['unit_number']).'</td>';
			$html_table .= '<td>'.html_encode($cur_info['unit_size']).'</td>';
			
			$html_table .= '<td>'.html_encode($cur_info['remarks']).'</td></tr>';
		}
		
		$html_table .= '</table>';
		
		$mPDF = new \Mpdf\Mpdf();
		$mPDF->WriteHTML($html_table);
		
		$mPDF->Output('files/weekly_schedule.pdf', 'F');
		$output = true;
	}
	
	return $output;
}

// Gen PDF for each Vendor on separate sheet
function hca_sp_gen_pdf_for_each_vendor()
{
	global $DBLayer, $main_info, $first_day_of_this_week;
	
	$search_by_vendor_id = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0;
	$search_by_property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;
	$output = false;
	$css_td = 'style="border: 1px solid grey;padding:0;margin:0;text-align:center"';
	$css_td_remarks = 'style="border: 1px solid grey;padding:0;margin:0;"';
	
	if (!empty($main_info))
	{
		$mPDF = new \Mpdf\Mpdf();
		
		$vendor_id = 0;
		foreach($main_info as $cur_info)
		{
			if ($vendor_id == 0)
			{
				$html_table = '<p style="text-align:center;color:red;margin-left:5px;font-weight:bold">VENDORS: FOR ANY QUESTIONS CONTACT PROPERTY RENTAL OFFICE DIRECTLY</p>';
				$html_table .= '<p>Week of <strong>'.format_time($first_day_of_this_week, 1).'</strong></p>';
				//$html_table .= '<p>Property Name: <strong>'.html_encode($cur_info['pro_name']).'</strong></p>';
				$html_table .= '<p>Property Name: <strong>HCA COLLEGE AREA</strong></p>';
				$html_table .= '<p>Vendor Name: <strong>'.html_encode($cur_info['vendor_name']).'</strong></p>';
				$html_table .= '<p>Vendor Email: <strong>'.html_encode($cur_info['email']).'</strong></p>';
				
				$html_table .= '<table cellpadding="0" autosize="1" width="100%" style="border-spacing:0;overflow:wrap;border:1px solid black;font-size:12px">';
				$html_table .= '<tr><td style="text-align:center"><strong>VENDOR</strong></td><td width="20%" style="text-align:center"><strong>PROPERTY</strong></td><td width="8%" style="text-align:center"><strong>UNIT</strong></td><td width="7%" style="text-align:center"><strong>SIZE</strong></td><td width="20%" style="text-align:center"><strong>P.O. #</strong></td><td style="text-align:center"><strong>REMARKS</strong></td></tr>';
			}
			else if ($vendor_id != 0 && $vendor_id != $cur_info['vendor_id'])
			{
				$html_table .= '</table>';
				$mPDF->WriteHTML($html_table);
				
				$mPDF->AddPage();
				$html_table = '<p style="text-align:center;color:red;margin-left:5px;font-weight:bold">VENDORS: FOR ANY QUESTIONS CONTACT PROPERTY RENTAL OFFICE DIRECTLY</p>';
				$html_table .= '<p>Week of <strong>'.format_time($first_day_of_this_week, 1).'</strong></p>';
				//$html_table .= '<p>Property Name: <strong>'.html_encode($cur_info['pro_name']).'</strong></p>';
				$html_table .= '<p>Property Name: <strong>HCA COLLEGE AREA</strong></p>';
				$html_table .= '<p>Vendor Name: <strong>'.html_encode($cur_info['vendor_name']).'</strong></p>';
				$html_table .= '<p>Vendor Email: <strong>'.html_encode($cur_info['email']).'</strong></p>';
				
				$html_table .= '<table cellpadding="0" autosize="1" width="100%" style="border-spacing:0;overflow:wrap;border:1px solid black;font-size:12px">';
				$html_table .= '<tr><td style="text-align:center"><strong>VENDOR</strong></td><td width="20%" style="text-align:center"><strong>PROPERTY</strong></td><td width="8%" style="text-align:center"><strong>UNIT</strong></td><td width="7%" style="text-align:center"><strong>SIZE</strong></td><td width="20%" style="text-align:center"><strong>P.O. #</strong></td><td style="text-align:center"><strong>REMARKS</strong></td></tr>';
			}
			
			$html_table .= '<tr><td '.$css_td.'><strong>'.html_encode($cur_info['vendor_name']).'</strong>';
			$html_table .= '<p>'.format_time($cur_info['date_time'], 1).'</p></td>';
			$html_table .= '<td '.$css_td.'>'.html_encode($cur_info['pro_name']).'</td>';
			$html_table .= '<td '.$css_td.'>'.html_encode($cur_info['unit_number']).'</td>';
			$html_table .= '<td '.$css_td.'>'.html_encode($cur_info['unit_size']).'</td>';
			$html_table .= '<td '.$css_td.'>'.html_encode($cur_info['po_number']).'</td>';
			$html_table .= '<td '.$css_td_remarks.'>'.html_encode($cur_info['remarks']).'</td></tr>';
			
			$vendor_id = $cur_info['vendor_id'];
		}
		
		$html_table .= '</table>';
		$mPDF->WriteHTML($html_table);
		
		$mPDF->Output('files/vendors_schedule.pdf', 'F');
		$output = true;
	}
	
	return $output;
}

// Generate PDF for each property
function hca_vcr_send_schedule_each_property($property_id = 0)
{
	global $DBLayer, $main_info, $first_day_of_this_week;
	
	$output = false;
	$css_td = 'style="border: 1px solid grey;padding:0;margin:0;text-align:center"';
	$css_td_remarks = 'style="border: 1px solid grey;padding:0;margin:0;"';
	
	if (!empty($main_info))
	{
		$mPDF = new \Mpdf\Mpdf();
		
		$header = false;
		foreach($main_info as $cur_info)
		{
			if (isset($cur_info['property_id']) && $property_id == $cur_info['property_id'])	
			{
				if (!$header)
				{
					$html_table = '<p>Week of: <strong>'.format_time($first_day_of_this_week, 1).'</strong></p>';
					$html_table .= '<p>Property Name: <strong>'.html_encode($cur_info['pro_name']).'</strong></p>';
					$html_table .= '<table cellpadding="0" autosize="1" width="100%" style="border-spacing:0;overflow:wrap;border:1px solid black;font-size:12px">';
					$html_table .= '<tr><td style="text-align:center"><strong>VENDOR</strong></td><td width="20%" style="text-align:center"><strong>PROPERTY</strong></td><td width="8%" style="text-align:center"><strong>UNIT</strong></td><td width="7%" style="text-align:center"><strong>SIZE</strong></td><td width="20%" style="text-align:center"><strong>P.O. #</strong></td><td style="text-align:center"><strong>REMARKS</strong></td></tr>';
					
					$header = true;
				}
				
				$html_table .= '<tr><td '.$css_td.'><strong>'.html_encode($cur_info['vendor_name']).'</strong>';
				$html_table .= '<p>'.format_time($cur_info['date_time'], 1).'</p></td>';
				$html_table .= '<td '.$css_td.'>'.html_encode($cur_info['pro_name']).'</td>';
				$html_table .= '<td '.$css_td.'>'.html_encode($cur_info['unit_number']).'</td>';
				$html_table .= '<td '.$css_td.'>'.html_encode($cur_info['unit_size']).'</td>';
				$html_table .= '<td '.$css_td.'>'.html_encode($cur_info['po_number']).'</td>';
				$html_table .= '<td '.$css_td_remarks.'>'.html_encode($cur_info['remarks']).'</td></tr>';
			}
		}
		
		if ($header)
		{
			$html_table .= '</table>';
			$mPDF->WriteHTML($html_table);
			
			$mPDF->Output('files/schedule_for_property.pdf', 'F');
			$output = true;
		}
	}
	
	return $output;	
}


// Generate PDF for each Inspector
function hca_vcr_gen_schedule_of_final_walk_inspection($inspector_name = '')
{
	global $DBLayer, $main_info;
	
	$week_of = isset($_GET['week_of']) && $_GET['week_of'] != '' ? strtotime($_GET['week_of']) : 0;
	$first_day_of_this_week = isset($_GET['week_of']) ? strtotime('Monday this week', $week_of) : strtotime('Monday this week');
	
	$header = $output = false;
	$css_td = 'style="border: 1px solid grey;padding:0;margin:0;text-align:center"';
	$css_td_remarks = 'style="border: 1px solid grey;padding:0;margin:0;"';
	
	if (!empty($main_info))
	{
		// SORTING BY DATE
//		$main_info = array_msort($main_info, array('walk'=>SORT_ASC));
		
		$mPDF = new \Mpdf\Mpdf();

		foreach($main_info as $cur_info)
		{
			if ($inspector_name == $cur_info['walk'] && $cur_info['walk'] != '')	
			{
				if (!$header)
				{
					$html_table = '';
					if ($week_of > 0)
						$html_table .= '<p>Week of: <strong>'.format_time($first_day_of_this_week, 1).'</strong></p>';
					
					$html_table .= '<p>Inspector Name: <strong>'.html_encode($cur_info['walk']).'</strong></p>';
					$html_table .= '<table cellpadding="0" autosize="1" width="100%" style="border-spacing:0;overflow:wrap;border:1px solid black;font-size:12px">';
					
					$html_table .= '<tr><td width="20%" style="text-align:center;padding:5px"><strong>Property</strong></td>';
					$html_table .= '<td width="8%" style="text-align:center"><strong>Unit#</strong></td>';
					$html_table .= '<td width="20%" style="text-align:center"><strong>Date of Inspection</strong></td>
					<td style="text-align:center"><strong>Remarks</strong></td></tr>';
					
					$header = true;
				}
				
				$html_table .= '<tr><td '.$css_td.'><strong>'.html_encode($cur_info['pro_name']).'</strong></td>';
				$html_table .= '<td '.$css_td.'>'.html_encode($cur_info['unit_number']).'</td>';
				$html_table .= '<td '.$css_td.'>'.format_time($cur_info['walk_date'], 1).'</td>';
				$html_table .= '<td '.$css_td_remarks.'>'.html_encode($cur_info['remarks']).'</td></tr>';
			}
		}
		
		if ($header)
		{
			$html_table .= '</table>';
			$mPDF->WriteHTML($html_table);
			
			$mPDF->Output('files/final_walk_inspections.pdf', 'F');
			$output = true;
		}
	}
	
	return $output;	
}

// Generate PDF for Pre Walk Inspections
function hca_vcr_gen_schedule_of_pre_walk_inspection($inspector_name = '')
{
	global $DBLayer, $main_info;
	
	$week_of = isset($_GET['week_of']) && $_GET['week_of'] != '' ? strtotime($_GET['week_of']) : 0;
	$first_day_of_this_week = isset($_GET['week_of']) ? strtotime('Monday this week', $week_of) : strtotime('Monday this week');
	
	$output = false;
	$css_td = 'style="border: 1px solid grey;padding:0;margin:0;text-align:center"';
	$css_td_remarks = 'style="border: 1px solid grey;padding:0;margin:0;"';
	
	if (!empty($main_info))
	{
		// SORTING BY DATE
		//$main_info = array_msort($main_info, array('pre_walk_name'=>SORT_ASC));
		
		$mPDF = new \Mpdf\Mpdf();
		
		$header = false;
		foreach($main_info as $cur_info)
		{
			if ($inspector_name == $cur_info['pre_walk_name'])	
			{
				if (!$header)
				{
					$html_table = '';
					if ($week_of > 0)
						$html_table .= '<p>Week of: <strong>'.format_time($first_day_of_this_week, 1).'</strong></p>';
					
					$html_table .= '<p>Inspector Name: <strong>'.html_encode($cur_info['pre_walk_name']).'</strong></p>';
					$html_table .= '<table cellpadding="0" autosize="1" width="100%" style="border-spacing:0;overflow:wrap;border:1px solid black;font-size:12px">';
					
					$html_table .= '<tr><td width="20%" style="text-align:center;padding:5px"><strong>Property</strong></td>';
					$html_table .= '<td width="8%" style="text-align:center"><strong>Unit#</strong></td>';
					$html_table .= '<td width="20%" style="text-align:center"><strong>Date of Inspection</strong></td>
					<td style="text-align:center"><strong>Remarks</strong></td></tr>';
					
					$header = true;
				}
				
				$html_table .= '<tr><td '.$css_td.'><strong>'.html_encode($cur_info['pro_name']).'</strong></td>';
				$html_table .= '<td '.$css_td.'>'.html_encode($cur_info['unit_number']).'</td>';
				$html_table .= '<td '.$css_td.'>'.format_time($cur_info['pre_walk_date'], 1).'</td>';
				$html_table .= '<td '.$css_td_remarks.'>'.html_encode($cur_info['remarks']).'</td></tr>';
			}
		}
		
		if ($header)
		{
			$html_table .= '</table>';
			$mPDF->WriteHTML($html_table);
			
			$mPDF->Output('files/pre_walk_inspections.pdf', 'F');
			$output = true;
		}
	}
	
	return $output;	
}