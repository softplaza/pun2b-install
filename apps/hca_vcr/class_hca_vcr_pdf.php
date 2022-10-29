<?php

class HcaVcrPdf
{
	public $main_info = [];
	public $vendors_schedule = [];
	public $schedule = false;
	public $final_walk = false;
	public $servises = [
		0 => '',
		1 => 'Urine Scan',
		2 => 'Painter',
		3 => 'Vinyl Service',
		4 => 'Carpet Service',
		5 => 'Pest Control',
		6 => 'Cleaning Service',
		7 => 'Refinish Service',
		8 => 'Maintenance',
		9 => 'Carpet Clean',
	];
	
	
	function __construct($main_info=[])
	{
		$this->main_info = $main_info;
		
	}
	
	function AddMainInfo($main_info=[])
	{
		$this->main_info = $main_info;
	}
	
	function AdVendorsSchedule($vendors_schedule=[])
	{
		$this->vendors_schedule = $vendors_schedule;
	}
	
	function Schedule($val=false)
	{
		$this->schedule = $val;
	}
	
	function FinalWalk($val=false)
	{
		$this->final_walk = $val;
	}
	
	function GenProject()
	{
		$css_td = 'style="border: 1px solid grey;padding:0;margin:0;text-align:center"';
		
		if (!empty($this->main_info))
		{
			$content = '<p style="text-align:center"><strong>PROJECT INFORMATION</strong></p>';
			$content .= '<p style="margin:2px">Property name: <strong>'.html_encode($this->main_info['pro_name']).'</strong></p>';
			$content .= '<p style="margin:2px">Unit number: <strong>'.html_encode($this->main_info['unit_number']).'</strong></p>';
			$content .= '<p style="margin:2px">Unit size: <strong>'.html_encode($this->main_info['unit_size']).'</strong></p>';
			
			$content .= '<table cellpadding="0" autosize="1" width="100%" style="border-spacing:0;overflow:wrap;border:1px solid black;font-size:12px">';
			
			$content .= '<tr>';
			$content .= '<td width="15%" '.$css_td.'><strong>SERVICE PERFORMED</strong></td>';
			$content .= '<td width="15%" '.$css_td.'><strong>VENDOR - DATE</strong></td>';
			$content .= '<td '.$css_td.'><strong>COMMENT</strong></td>';
			$content .= '</tr>';

			if ($this->schedule)
			{
				if (!empty($this->vendors_schedule))
				{
					foreach($this->vendors_schedule as $cur_info)
					{
						$service_name = $this->servises[$cur_info['vendor_group_id']];
						$content .= '<tr>';
						$content .= '<td '.$css_td.'><p>'.$service_name.'</p></td>';
						$content .= '<td '.$css_td.'><p>'.format_time($cur_info['date_time'], 1).'</p><p>'.html_encode($cur_info['vendor_name']).'</p></td>';
						$content .= '<td '.$css_td.'>'.html_encode($cur_info['remarks']).'</td>';
						$content .= '</tr>';	
					}
				}
			}
			
			if ($this->main_info['move_in_date'] > 0 && $this->schedule)
			{
				$content .= '<tr>';
				$content .= '<td '.$css_td.'>MoveIn</td>';
				$content .= '<td '.$css_td.'>'.format_time($this->main_info['move_in_date'], 1).'</td>';
				$content .= '<td '.$css_td.'>'.html_encode($this->main_info['move_in_comment']).'</td>';
				$content .= '</tr>';
			}
			
			$content .= '</table>';
			
			if ($this->main_info['walk_date'] > 0 && $this->final_walk)
			{
				$content .= '<p style="text-align:center;"><strong>GIG LIST INFORMATION</strong></p>';
				$content .= '<p style="margin:2px">Name of inspector: <strong>'.html_encode($this->main_info['walk']).'</strong></p>';
				$content .= '<p style="margin:2px">Date of inspection: <strong>'.format_time($this->main_info['walk_date'], 1).'</strong></p>';
				
				$content .= '<table cellpadding="0" autosize="1" width="100%" style="border-spacing:0;overflow:wrap;border:1px solid black;font-size:12px">';
				
				$content .= '<tr>';
				$content .= '<td '.$css_td.'><strong>COMMENT</strong></td>';
				$content .= '</tr>';
				
				$content .= '<tr>';
				$content .= '<td style="padding-left:7px">'.line_break_to_br($this->main_info['walk_comment']).'</td>';
				$content .= '</tr>';
				
				$content .= '</table>';
			}
			
			$mPDF = new \Mpdf\Mpdf();
			$mPDF->WriteHTML($content);
			$mPDF->Output('files/project_information.pdf', 'F');
		}
	}
}

$HcaVcrPdf = new HcaVcrPdf;