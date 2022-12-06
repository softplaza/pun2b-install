<?php

class HcaWOMPDF
{
	// Pre table rows
	var $pre_table_rows = [];
	
	var $tr = [];
	var $td = [];
	var $td_param = [];

	var $tr_id = 0;
	var $td_id = 0;

	var $css_table = 'border-spacing:0;overflow:wrap;font-size:13px'; //border:1px solid black;

	function addPreTableRow($row){
		$this->pre_table_rows[] = $row;
	}

	function addTR(){
		++$this->tr_id;
		$this->tr[$this->tr_id] = $this->tr_id;
	}

	function addTD($name, $param = []){
		++$this->td_id;
		$this->td[$this->tr_id][$this->td_id] = $name;

		if (!empty($param))
			$this->td_param[$this->td_id] = $param;
	}

	function print($file_path = '')
	{
		$mPDF = new \Mpdf\Mpdf();
		$mPDF->AddPage();

		$content = [];

		// Gen pre table rows
		if (!empty($this->pre_table_rows))
		{
			foreach($this->pre_table_rows as $table_row)
			{
				$content[] = $table_row;
			}
		}

		$content[] = '<table cellpadding="5" autosize="1" width="100%" style="'.$this->css_table.'">';

		// Gen table rows
		if (!empty($this->tr) && !empty($this->td))
		{
			foreach($this->tr as $tr_id)
			{
				if (isset($this->td[$tr_id]))
				{
					$content[] = '<tr>';
					foreach($this->td[$tr_id] as $key => $value)
					{
						$td_param = isset($this->td_param[$key]) ? implode($this->td_param[$key]) : '';
						$content[] = '<td '.$td_param.'>'.$value.'</td>';
					}
					$content[] = '</tr>';
				}
			}
		}

		$content[] = '</table>';

		$mPDF->WriteHTML(implode('', $content));

		if ($file_path != '')
			$mPDF->Output($file_path, 'F');
	}

	function genWorkOrder()
	{
		global $User, $main_info, $checked_items, $HcaUnitInspection;

		$mPDF = new \Mpdf\Mpdf();
		$mPDF->AddPage();

		$css_td = 'border:1px solid black;';

		$content = [];
		$content[] = '<p style="text-align:center;font-size:18px"><strong>WORK ORDER #'.$main_info['id'].'</strong></p>';

		$content[] = '<table cellpadding="5" autosize="1" width="100%" style="border-spacing:0;border:1px solid black;font-size:14px">';

		$content[] = '<tr>';
		$content[] = '<td style="'.$css_td.'">Property: <strong>'.html_encode($main_info['pro_name']).'</strong></td>';
		$content[] = '<td style="'.$css_td.'">Unit#: <strong>'.html_encode($main_info['unit_number']).'</strong></td>';
		$content[] = '<td style="'.$css_td.'">Unit size: <strong>'.html_encode($main_info['unit_type']).'</strong></td>';
		$content[] = '<td style="'.$css_td.'">Status: '.($main_info['inspection_completed'] == 2 ? 'Completed' : 'Not completed').'</td>';
		$content[] = '</tr>';

		$content[] = '<tr>';
		$content[] = '<td style="'.$css_td.'">Started: <strong>'.format_date($main_info['datetime_completion_start'], 'n/j/y g:i a').'</strong></td>';
		$content[] = '<td style="'.$css_td.'">Completed: <strong>'.format_date($main_info['datetime_completion_end'], 'n/j/y g:i a').'</strong></td>';

		$completed_name = ($main_info['work_order_completed'] == 2) ? html_encode($main_info['completed_name']) : html_encode($main_info['updated_name']);

		$content[] = '<td style="'.$css_td.'">Completed by: <strong>'.$completed_name.'</strong></td>';
		$content[] = '<td style="'.$css_td.'"></td>';
		$content[] = '</tr>';

		$content[] = '</table>';
		
		$location_id1 = 0;
		foreach($HcaUnitInspection->locations as $location_id => $location_name)
		{
			if (!empty($checked_items))
			{
				$output = $cur_item = $cur_location = [];
				if ($location_id1 != $location_id)
					$cur_location[] = '<p style="font-size:18px;font-weight:bold">'.html_encode($location_name).'</p>';

				foreach($checked_items as $cur_info)
				{
					if ($cur_info['location_id'] == $location_id)
					{
						$problem_names = $HcaUnitInspection->getItemProblems($cur_info['problem_ids']);
		
						$cur_item[] = '<p>';
						$cur_item[] = '<span>'.html_encode($cur_info['item_name']).'</span>: ';
						$cur_item[] = '<span>'.$HcaUnitInspection->getJobType($cur_info['job_type']).'</span>';
						$cur_item[] = '</p>';

						if ($cur_info['comment'] != '')
							$cur_item[] = '<p class="fst-italic"><span class="text-info">Comment:</span> '.html_encode($cur_info['comment']).'</p>';
					}
				}
		
				if (!empty($cur_item))
				{
					$content[] = implode("\n", $cur_location);
					$content[] = implode("\n", $cur_item);
				}
		
				$location_id1 = $location_id;
			}
		}

		//$content[] = '<p>Work Order Completed? <span style="font-weight:bold">'.($main_info['status'] == 3 ? 'YES' : 'NO').'</span></p>';

		if ($main_info['work_order_comment'] != '')
		{
			$content[] = '<span style="">Comments:</span>';
			$content[] = '<p style="border:1px solid black; padding:3px;margin-top: 0;">'.html_encode($main_info['work_order_comment']).'</p>';
		}

		$mPDF->WriteHTML(implode('', $content));
		$mPDF->Output('files/work_order_'.$User->get('id').'.pdf', 'F');
	}
}
