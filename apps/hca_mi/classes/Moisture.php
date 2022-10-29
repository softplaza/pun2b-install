<?php

/**
 * @author SwiftManager.Org
 * @copyright (C) 2021 SwiftManager license GPL
 * @package Moisture
**/

class Moisture
{
	// in future add main func in Calendar
	function get_events($events_info, $pid)
	{
		$output = array();
		
		if (!empty($events_info))
		{
			foreach($events_info as $cur_info)
			{
				$css = array();
				if ($cur_info['project_id'] == $pid)
				{
					$date_now = date('Ymd', time());
					$tomorrow = date('Ymd', ($cur_info['time'] - 86400));
					$today = date('Ymd', $cur_info['time']);
					
					if ($date_now == $today) $css[] = 'alert-danger mb-1 p-1';
					else if ($date_now == $tomorrow) $css[] = 'alert-warning mb-1 p-1';

					$output[] = '<div class="'.implode(' ', $css).'">';
					$output[] = '<p style="float:right" onclick="getEvent('.$pid.', '.$cur_info['id'].')" data-bs-toggle="modal" data-bs-target="#modalWindow"><i class="fas fa-edit fa-lg"></i></p>';
					$output[] = '<p class="text-decoration-underline">'.format_time($cur_info['time']).'</p>';
					$output[] = '<p>'.html_encode($cur_info['message']).'</p>';
					$output[] = '</div>';
				}
			}
		}
		
		return $output;
	}
	
	function get_upcoming_work()
	{
		global $DBLayer, $User;

		$count = 0;
		$query = array(
			'SELECT'	=> 'mois_inspection_date, asb_test_date, rem_start_date, rem_end_date, cons_start_date, cons_end_date',
			'FROM'		=> 'hca_5840_projects',
			//'WHERE'		=> 'job_status=1'
			'WHERE'		=> 'job_status=1 AND mois_performed_by=\''.$DBLayer->escape($User->get('realname')).'\''
		);
		$result = $DBLayer->query_build($query) or error(__FILE__, __LINE__);
		while ($row = $DBLayer->fetch_assoc($result))
		{
			if (sm_is_today($row['mois_inspection_date']) || sm_is_today($row['asb_test_date']) || sm_is_today($row['rem_start_date']) || sm_is_today($row['rem_end_date']) || sm_is_today($row['cons_start_date']) || sm_is_today($row['cons_end_date']))
				++$count;
		}
		
		return $count;
	}
	
	function get_email_details($array = [])
	{
		$main_details = array(
			'property_name'			=> 'Property',
			'unit_number'			=> 'Unit Number',
			'location'				=> 'Location',
	//		'mois_report_date'		=> 'Reported Date',
	//		'mois_performed_by'		=> 'Performed by',
	//		'mois_inspection_date'	=> 'Inspection Date',
			'mois_source'			=> 'Source',
			'symptoms'				=> 'Symptoms',
			'action'				=> 'Action',
			'remarks'				=> 'Remarks',
			'total_cost'			=> 'Total Cost',
		);
		
		return (!empty($array)) ? array_merge($main_details, $array) : $main_details;
	}
	
	// Generate Appendix-B PDF file
	function gen_appendix_b($form_info, $project_info)
	{
		global $property_info;
		
		$box_not_checked = '';
		$box_checked = '<span style="font-family:helvetica;font-size: 16px;">&#10004;</span>';
		
		$style_table = 'margin-bottom:20px;font-size: 10px;border-spacing:0;';
		$style_p = 'font-size:1.2em;';
		$style_pdf_content = 'display:flex;color:red;font-weight:bold;';
		$td1 = 'min-width:300px;width:300px;background: white;padding: .2em .417em;';
		$td2 = 'width:30px;background: white;padding: .2em .417em;';
		$td3 = 'width:30px;text-align:center;background: white;padding: .2em .417em;';
		$td4 = 'width:30px;text-align:center;background: white;padding: .2em .417em;';
		$td5 = 'width:30px;text-align:center;background: white;padding: .2em .417em;';
		$td6 = 'width: 5px;background: white;padding: .2em .417em;';
		$td7 = 'width: 100px;text-align:center;background: white;padding: .2em .417em;';
		$td8 = 'width: 150px;background: white;padding: .2em .417em;';
		$css_txt_input = 'font-weight: bold;';
		$css_loc_txt_input = 'width:20%;';
		$css_border = 'border:1px solid #6a6565;border-spacing:0';

		$output = '<div style="font-size:10px;margin:5px;padding:5px;">';
		$output .= '<div class="pdf-title">';
		$output .= '<h2 style="text-align:center;font-weight: bold;font-size: 1.4em;"><span>Appendix B - Internal Moisture Intrusion Checklist</span></h2>';
		$output .= '</div>';
		$output .= '<strong>Perform as soon as possible after moisture intrusion problems are reported.</strong>';
		
		$output .= '<table style="'.$style_table.'">';
		$output .= '<tbody>';
		$output .= '<tr>';
		$output .= '<td style="border: 1px solid #6a6565;width:30%">Property Name: <strong>'.($project_info['pro_name'] != '' ? html_encode($project_info['pro_name']) : html_encode($project_info['property_name'])).'</strong></td>';
		$output .= '<td style="width:10%"></td>';
		$output .= '<td style="border: 1px solid #6a6565;width:40%">Unit# <strong>'.$project_info['unit_number'].'</strong></td>';
		$output .= '</tr>';
		$output .= '<tr>';
		$output .= '<td style="border: 1px solid #6a6565;">Date Reported: <strong>'.format_time($form_info['reported_time'], 1, 'm/d/y').'</strong></td>';
		$output .= '<td></td>';
		$output .= '<td style="border: 1px solid #6a6565;">Inspector\'s Name: <strong>'.($project_info['mois_performed_name'] != '' ? html_encode($project_info['mois_performed_name']) : html_encode($project_info['mois_performed_by'])).'</strong></td>';
		$output .= '</tr>';
		$output .= '<tr>';
		$output .= '<td style="border: 1px solid #6a6565;">Time Reported: <strong>'.format_time($form_info['reported_time'], 2, 'H:i').'</strong></td>';
		$output .= '<td></td>';
		$output .= '<td></td>';
		$output .= '</tr>';
		$output .= '</tbody>';
		$output .= '</table>';
		
		$output .= '<strong>Type of moisture intrusion (clear, grey, black water):</strong>';
		
		$output .= '<table style="'.$style_table.'">';
		$output .= '<tbody>';
		
		$output .= '<tr>';
		$output .= '<td style="'.$td1.'"></td>';
		$output .= '<td style="'.$td2.'"></td>';
		$output .= '<td style="'.$td3.'"></td>';
		//$output .= '<td style="'.$td4.'"></td>';
		$output .= '<td style="'.$td5.'"></td>';
		$output .= '<td style="'.$td6.'"></td>';
		$output .= '<td style="'.$td7.' '.$css_border.'">Inspector\'s Initials</td>';
		$output .= '<td style="'.$td8.' '.$css_border.'">Comments/Follow-up</td>';
		$output .= '</tr>';
		
		$output .= '<tr>';
		$output .= '<td style="'.$td1.'"></td>';
		$output .= '<td style="'.$td2.'"></td>';
		$output .= '<td style="'.$td3.' '.$css_border.'">Clear</td>';
		//$output .= '<td style="'.$td4.'"></td>';
		$output .= '<td style="'.$td5.' '.$css_border.'">'.(isset($form_info['mois_type_clear']) && ($form_info['mois_type_clear'] == 1) ? $box_checked : $box_not_checked).'</td>';
		$output .= '<td style="'.$td6.'"></td>';
		$output .= '<td style="'.$td7.' '.$css_border.' '.$css_txt_input.'">'.html_encode($form_info['mois_type_clear_init']).'</td>';
		$output .= '<td style="'.$td8.' '.$css_border.' '.$css_txt_input.'">'.html_encode($form_info['mois_type_clear_desc']) .'</td>';
		$output .= '</tr>';
		
		$output .= '<tr>';
		$output .= '<td style="'.$td1.' text-align:center">Check Only One</td>';
		$output .= '<td style="'.$td2.'"></td>';
		$output .= '<td style="'.$td3.' '.$css_border.'">Grey</td>';
		//$output .= '<td style="'.$td4.'"></td>';
		$output .= '<td style="'.$td5.' '.$css_border.'">'.(isset($form_info['mois_type_grey']) && ($form_info['mois_type_grey'] == 1) ? $box_checked : $box_not_checked).'</td>';
		$output .= '<td style="'.$td6.'"></td>';
		$output .= '<td style="'.$td7.' '.$css_border.' '.$css_txt_input.'">'.html_encode($form_info['mois_type_grey_init']).'</td>';
		$output .= '<td style="'.$td8.' '.$css_border.' '.$css_txt_input.'">'.html_encode($form_info['mois_type_grey_desc']).'</td>';
		$output .= '</tr>';
		
		$output .= '<tr>';
		$output .= '<td style="'.$td1.'"></td>';
		$output .= '<td style="'.$td2.'"></td>';
		$output .= '<td style="'.$td3.' '.$css_border.'">Black</td>';
		//$output .= '<td style="'.$td4.'"></td>';
		$output .= '<td style="'.$td5.' '.$css_border.'">'.(isset($form_info['mois_type_black']) && ($form_info['mois_type_black'] == 1) ? $box_checked : $box_not_checked).'</td>';
		$output .= '<td style="'.$td6.'"></td>';
		$output .= '<td style="'.$td7.' '.$css_border.' '.$css_txt_input.'">'.html_encode($form_info['mois_type_black_init']).'</td>';
		$output .= '<td style="'.$td8.' '.$css_border.' '.$css_txt_input.'">'.html_encode($form_info['mois_type_black_desc']).'</td>';
		$output .= '</tr>';
		
		$output .= '</tbody>';
		$output .= '</table>';
		
		$output .= '<p style="'.$style_p.'"><strong>Inspection Item:</strong></p>';
		$output .= '<strong>Staining/discoloration observed on building materials:</strong>';
		
		$output .= '<table style="'.$style_table.'">';
		$output .= '<tbody>';
		$output .= '<tr>';
		$output .= '<td style="'.$td1.'"></td>';
		$output .= '<td style="'.$td2.'"></td>';
		$output .= '<td style="'.$td3.' '.$css_border.'">Yes</td>';
		//$output .= '<td style="'.$td4.'"></td>';
		$output .= '<td style="'.$td5.' '.$css_border.'">No</td>';
		$output .= '<td style="'.$td6.'"></td>';
		$output .= '<td style="'.$td7.' '.$css_border.'">Inspector\'s Initials</td>';
		$output .= '<td style="'.$td8.' '.$css_border.'">Comments/Follow-up</td>';
		$output .= '</tr>';
		
		if ($property_info['attics'] == 1 && isset($form_info['disc_bldg_attics']))
		{
			$output .= '<tr>';
			$output .= '<td style="'.$td1.'">Attics</td>';
			$output .= '<td style="'.$td2.'"></td>';
			$output .= '<td style="'.$td3.' '.$css_border.'">'.(($form_info['disc_bldg_attics'] == 1) ? $box_checked : $box_not_checked).'</td>';
			//$output .= '<td style="'.$td4.'"></td>';
			$output .= '<td style="'.$td5.' '.$css_border.'">'.(($form_info['disc_bldg_attics'] == 0) ? $box_checked : $box_not_checked).'</td>';
			$output .= '<td style="'.$td6.'"></td>';
			$output .= '<td style="'.$td7.' '.$css_border.' '.$css_txt_input.'">'.html_encode($form_info['disc_bldg_attics_init']).'</td>';
			$output .= '<td style="'.$td8.' '.$css_border.' '.$css_txt_input.'">'.html_encode($form_info['disc_bldg_attics_desc']).'</td>';
			$output .= '</tr>';
		}
		
		if (isset($form_info['disc_bldg_ceilings']))
		{
			$output .= '<tr>';
			$output .= '<td style="'.$td1.'">Ceiling</td>';
			$output .= '<td style="'.$td2.'"></td>';
			$output .= '<td style="'.$td3.' '.$css_border.'">'.(($form_info['disc_bldg_ceilings'] == 1) ? $box_checked : $box_not_checked).'</td>';
			//$output .= '<td style="'.$td4.'"></td>';
			$output .= '<td style="'.$td5.' '.$css_border.'">'.(($form_info['disc_bldg_ceilings'] == 0) ? $box_checked : $box_not_checked).'</td>';
			$output .= '<td style="'.$td6.'"></td>';
			$output .= '<td style="'.$td7.' '.$css_border.' '.$css_txt_input.'">'.html_encode($form_info['disc_bldg_ceilings_init']).'</td>';
			$output .= '<td style="'.$td8.' '.$css_border.' '.$css_txt_input.'">'.html_encode($form_info['disc_bldg_ceilings_desc']).'</td>';
			$output .= '</tr>';
		}
		
		if (isset($form_info['disc_bldg_walls']))
		{
			$output .= '<tr>';
			$output .= '<td style="'.$td1.'">Walls</td>';
			$output .= '<td style="'.$td2.'"></td>';
			$output .= '<td style="'.$td3.' '.$css_border.'">'.(($form_info['disc_bldg_walls'] == 1) ? $box_checked : $box_not_checked).'</td>';
			//$output .= '<td style="'.$td4.'"></td>';
			$output .= '<td style="'.$td5.' '.$css_border.'">'.(($form_info['disc_bldg_walls'] == 0) ? $box_checked : $box_not_checked).'</td>';
			$output .= '<td style="'.$td6.'"></td>';
			$output .= '<td style="'.$td7.' '.$css_border.' '.$css_txt_input.'">'.html_encode($form_info['disc_bldg_walls_init']).'</td>';
			$output .= '<td style="'.$td8.' '.$css_border.' '.$css_txt_input.'">'.html_encode($form_info['disc_bldg_walls_desc']).'</td>';
			$output .= '</tr>';
		}
		
		if (isset($form_info['disc_bldg_windows']))
		{
			$output .= '<tr>';
			$output .= '<td style="'.$td1.'">Windows</td>';
			$output .= '<td style="'.$td2.'"></td>';
			$output .= '<td style="'.$td3.' '.$css_border.'">'.(($form_info['disc_bldg_windows'] == 1) ? $box_checked : $box_not_checked).'</td>';
			//$output .= '<td style="'.$td4.'"></td>';
			$output .= '<td style="'.$td5.' '.$css_border.'">'.(($form_info['disc_bldg_windows'] == 0) ? $box_checked : $box_not_checked).'</td>';
			$output .= '<td style="'.$td6.'"></td>';
			$output .= '<td style="'.$td7.' '.$css_border.' '.$css_txt_input.'">'.html_encode($form_info['disc_bldg_windows_init']).'</td>';
			$output .= '<td style="'.$td8.' '.$css_border.' '.$css_txt_input.'">'.html_encode($form_info['disc_bldg_windows_desc']).'</td>';
			$output .= '</tr>';
		}
		
		if (isset($form_info['disc_bldg_floors']))
		{
			$output .= '<tr>';
			$output .= '<td style="'.$td1.'">Floor/tack strips</td>';
			$output .= '<td style="'.$td2.'"></td>';
			$output .= '<td style="'.$td3.' '.$css_border.'">'.(isset($form_info['disc_bldg_floors']) && ($form_info['disc_bldg_floors'] == 1) ? $box_checked : $box_not_checked).'</td>';
			//$output .= '<td style="'.$td4.'"></td>';
			$output .= '<td style="'.$td5.' '.$css_border.'">'.(isset($form_info['disc_bldg_floors']) && ($form_info['disc_bldg_floors'] == 0) ? $box_checked : $box_not_checked).'</td>';
			$output .= '<td style="'.$td6.'"></td>';
			$output .= '<td style="'.$td7.' '.$css_border.' '.$css_txt_input.'">'.html_encode($form_info['disc_bldg_floors_init']).'</td>';
			$output .= '<td style="'.$td8.' '.$css_border.' '.$css_txt_input.'">'.html_encode($form_info['disc_bldg_floors_desc']).'</td>';
			$output .= '</tr>';
		}
		$output .= '</tbody>';
		$output .= '</table>';
		
		$output .= '<strong>Staining/discoloration observed near utilities:</strong>';
		
		$output .= '<table style="'.$style_table.'">';
		$output .= '<tbody>';
		
		$output .= '<tr>';
		$output .= '<td style="'.$td1.'"></td>';
		$output .= '<td style="'.$td2.'"></td>';
		$output .= '<td style="'.$td3.' '.$css_border.'">Yes</td>';
		//$output .= '<td style="'.$td4.'"></td>';
		$output .= '<td style="'.$td5.' '.$css_border.'">No</td>';
		$output .= '<td style="'.$td6.'"></td>';
		$output .= '<td style="'.$td7.' '.$css_border.'">Inspector\'s Initials</td>';
		$output .= '<td style="'.$td8.' '.$css_border.'">Comments/Follow-up</td>';
		$output .= '</tr>';
		
		if (isset($form_info['disc_utilit_toilets']))
		{
			$output .= '<tr>';
			$output .= '<td style="'.$td1.'">Toilets</td>';
			$output .= '<td style="'.$td2.'"></td>';
			$output .= '<td style="'.$td3.' '.$css_border.'">'.(($form_info['disc_utilit_toilets'] == 1) ? $box_checked : $box_not_checked).'</td>';
			//$output .= '<td style="'.$td4.'"></td>';
			$output .= '<td style="'.$td5.' '.$css_border.'">'.(($form_info['disc_utilit_toilets'] == 0) ? $box_checked : $box_not_checked).'</td>';
			$output .= '<td style="'.$td6.'"></td>';
			$output .= '<td style="'.$td7.' '.$css_border.' '.$css_txt_input.'">'.html_encode($form_info['disc_utilit_toilets_init']).'</td>';
			$output .= '<td style="'.$td8.' '.$css_border.' '.$css_txt_input.'">'.html_encode($form_info['disc_utilit_toilets_desc']).'</td>';
			$output .= '</tr>';
		}
		
		if ($property_info['washers'] == 1 && isset($form_info['disc_utilit_washers']))
		{
			$output .= '<tr>';
			$output .= '<td style="'.$td1.'">Washers</td>';
			$output .= '<td style="'.$td2.'"></td>';
			$output .= '<td style="'.$td3.' '.$css_border.'">'.(($form_info['disc_utilit_washers'] == 1) ? $box_checked : $box_not_checked).'</td>';
			//$output .= '<td style="'.$td4.'"></td>';
			$output .= '<td style="'.$td5.' '.$css_border.'">'.(($form_info['disc_utilit_washers'] == 0) ? $box_checked : $box_not_checked).'</td>';
			$output .= '<td style="'.$td6.'"></td>';
			$output .= '<td style="'.$td7.' '.$css_border.' '.$css_txt_input.'">'.html_encode($form_info['disc_utilit_washers_init']).'</td>';
			$output .= '<td style="'.$td8.' '.$css_border.' '.$css_txt_input.'">'.html_encode($form_info['disc_utilit_washers_desc']).'</td>';
			$output .= '</tr>';
		}
		
		if ($property_info['water_heater'] == 1 && isset($form_info['disc_utilit_heaters']))
		{
			$output .= '<tr>';
			$output .= '<td style="'.$td1.'">Water heaters</td>';
			$output .= '<td style="'.$td2.'"></td>';
			$output .= '<td style="'.$td3.' '.$css_border.'">'.(($form_info['disc_utilit_heaters'] == 1) ? $box_checked : $box_not_checked).'</td>';
			//$output .= '<td style="'.$td4.'"></td>';
			$output .= '<td style="'.$td5.' '.$css_border.'">'.(($form_info['disc_utilit_heaters'] == 0) ? $box_checked : $box_not_checked).'</td>';
			$output .= '<td style="'.$td6.'"></td>';
			$output .= '<td style="'.$td7.' '.$css_border.' '.$css_txt_input.'">'.html_encode($form_info['disc_utilit_heaters_init']).'</td>';
			$output .= '<td style="'.$td8.' '.$css_border.' '.$css_txt_input.'">'.html_encode($form_info['disc_utilit_heaters_desc']).'</td>';
			$output .= '</tr>';
		}
		
		if (isset($form_info['disc_utilit_sinks']))
		{
			$output .= '<tr>';
			$output .= '<td style="'.$td1.'">Sinks</td>';
			$output .= '<td style="'.$td2.'"></td>';
			$output .= '<td style="'.$td3.' '.$css_border.'">'.(($form_info['disc_utilit_sinks'] == 1) ? $box_checked : $box_not_checked).'</td>';
			//$output .= '<td style="'.$td4.'"></td>';
			$output .= '<td style="'.$td5.' '.$css_border.'">'.(($form_info['disc_utilit_sinks'] == 0) ? $box_checked : $box_not_checked).'</td>';
			$output .= '<td style="'.$td6.'"></td>';
			$output .= '<td style="'.$td7.' '.$css_border.' '.$css_txt_input.'">'.html_encode($form_info['disc_utilit_sinks_init']).'</td>';
			$output .= '<td style="'.$td8.' '.$css_border.' '.$css_txt_input.'">'.html_encode($form_info['disc_utilit_sinks_desc']).'</td>';
			$output .= '</tr>';
		}
		
		if (isset($form_info['disc_utilit_potable']))
		{
			$output .= '<tr>';
			$output .= '<td style="'.$td1.'">Potable water lines</td>';
			$output .= '<td style="'.$td2.'"></td>';
			$output .= '<td style="'.$td3.' '.$css_border.'">'.(($form_info['disc_utilit_potable'] == 1) ? $box_checked : $box_not_checked).'</td>';
			//$output .= '<td style="'.$td4.'"></td>';
			$output .= '<td style="'.$td5.' '.$css_border.'">'.(($form_info['disc_utilit_potable'] == 0) ? $box_checked : $box_not_checked).'</td>';
			$output .= '<td style="'.$td6.'"></td>';
			$output .= '<td style="'.$td7.' '.$css_border.' '.$css_txt_input.'">'.html_encode($form_info['disc_utilit_potable_init']).'</td>';
			$output .= '<td style="'.$td8.' '.$css_border.' '.$css_txt_input.'">'.html_encode($form_info['disc_utilit_potable_desc']).'</td>';
			$output .= '</tr>';
		}
		
		if (isset($form_info['disc_utilit_drain']))
		{
			$output .= '<tr>';
			$output .= '<td style="'.$td1.'">Drain lines</td>';
			$output .= '<td style="'.$td2.'"></td>';
			$output .= '<td style="'.$td3.' '.$css_border.'">'.(($form_info['disc_utilit_drain'] == 1) ? $box_checked : $box_not_checked).'</td>';
			//$output .= '<td style="'.$td4.'"></td>';
			$output .= '<td style="'.$td5.' '.$css_border.'">'.(($form_info['disc_utilit_drain'] == 0) ? $box_checked : $box_not_checked).'</td>';
			$output .= '<td style="'.$td6.'"></td>';
			$output .= '<td style="'.$td7.' '.$css_border.' '.$css_txt_input.'">'.html_encode($form_info['disc_utilit_drain_init']).'</td>';
			$output .= '<td style="'.$td8.' '.$css_border.' '.$css_txt_input.'">'.html_encode($form_info['disc_utilit_drain_desc']).'</td>';
			$output .= '</tr>';
		}
		
		if ($property_info['hvac'] == 1 && isset($form_info['disc_utilit_hvac']))
		{
			$output .= '<tr>';
			$output .= '<td style="'.$td1.'">HVAC condensate pans/lines</td>';
			$output .= '<td style="'.$td2.'"></td>';
			$output .= '<td style="'.$td3.' '.$css_border.'">'.(($form_info['disc_utilit_hvac'] == 1) ? $box_checked : $box_not_checked).'</td>';
			//$output .= '<td style="'.$td4.'"></td>';
			$output .= '<td style="'.$td5.' '.$css_border.'">'.(($form_info['disc_utilit_hvac'] == 0) ? $box_checked : $box_not_checked).'</td>';
			$output .= '<td style="'.$td6.'"></td>';
			$output .= '<td style="'.$td7.' '.$css_border.' '.$css_txt_input.'">'.html_encode($form_info['disc_utilit_hvac_init']).'</td>';
			$output .= '<td style="'.$td8.' '.$css_border.' '.$css_txt_input.'">'.html_encode($form_info['disc_utilit_hvac_desc']).'</td>';
			$output .= '</tr>';
		}
		$output .= '</tbody>';
		$output .= '</table>';
		
		$output .= '<strong>Building material impacted by moisture intrusion:</strong>';
		
		$output .= '<table style="'.$style_table.'">';
		$output .= '<tbody style="loc-info">';
		$output .= '<tr><td style="'.$td1.'">Location - which room / room(s) etc.</td>';

		$locations_array = explode(',', $project_info['location']);
		for($i = 0; $i < count($locations_array); ++$i){
			if (isset($locations_array[$i]))
				$output .= '<td style="td-txt '.$css_border.'">'.html_encode($locations_array[$i]).'</td>'."\n";
		}
		$output .= '</tr>';
		
		$output .= '<tr><td '.$td1.'>Square Footages</td>';
		for($i = 0; $i < count($locations_array); ++$i){
			if (isset($locations_array[$i]))
				$output .= '<td style="'.$css_loc_txt_input.' '.$css_border.'">'.html_encode($form_info['square_footages'.($i + 1)]).'</td>';
		}
		$output .= '</tr>';
		
		$output .= '<tr><td '.$td1.'>Wood moisture meter results</td>';
		for($i = 0; $i < count($locations_array); ++$i){
			if (isset($locations_array[$i]))
				$output .= '<td style="'.$css_loc_txt_input.' '.$css_border.'">'.html_encode($form_info['wood_results'.($i + 1)]).'</td>';
		}
		$output .= '</tr>';
		
		$output .= '<tr><td style="'.$td1.'">Concrete moisture meter results</td>';
		for($i = 0; $i < count($locations_array); ++$i){
			if (isset($locations_array[$i]))
				$output .= '<td style="'.$css_loc_txt_input.' '.$css_border.'">'.html_encode($form_info['concrete_results'.($i + 1)]).'</td>';
		}
		$output .= '</tr>';
		
		$output .= '</tbody>';
		$output .= '</table>';
		
		$action = html_encode($form_info['action']);
		$output .= '<div style="margin-top:15px;color:#0f33df;">';
		$output .= '<div style="float:right;width:86%;white-space:pre-line;font-style:italic;font-size:12px;padding:5px;font-weight:bold;border: 2px solid #0f33df;border: 2px solid #6a6565;padding-left: 15px;font-style: italic;font-size: 1.2em;width:95%;">'.nl2br($action).'</div>';
		$output .= '<div style="float:left;width:60px;font-weight:bold;font-size:14px">';
			Action:
		$output .= '</div>';
		$output .= '</div>';
		$output .= '</div>';
		
		return $output;
	}
}
