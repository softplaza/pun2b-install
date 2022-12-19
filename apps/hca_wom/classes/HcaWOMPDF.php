<?php

class HcaWOMPDF
{
	// Pre table rows
	var $pre_table_rows = [];
	
	var $table = [];
	var $tr = [];
	var $td = [];
	var $td_param = [];

	var $tr_id = 0;
	var $td_id = 0;

	var $css_table = 'border-spacing:0;overflow:wrap;font-size:13px'; //border:1px solid black;

	function addPreTableRow($row){
		$this->pre_table_rows[] = $row;
	}

	function addTR()
	{
		++$this->tr_id;
		$this->tr[$this->tr_id] = $this->tr_id;
	}

	function addTD($name, $param = [])
	{
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
}
